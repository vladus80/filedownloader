<?php
/**
 * Функции для работы с базой данных SQLite
 */

require_once __DIR__ . '/config.php';

/** @var PDO|null Подключение к БД (для возможности закрытия при пересоздании) */
$dbConnection = null;

/**
 * Получает подключение к базе данных
 * @return PDO
 */
function getDbConnection() {
    global $dbConnection;
    
    if ($dbConnection === null) {
        try {
            $dbConnection = new PDO('sqlite:' . DB_PATH);
            $dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $dbConnection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die('Ошибка подключения к базе данных: ' . $e->getMessage());
        }
    }
    
    return $dbConnection;
}

/**
 * Закрывает подключение к БД (нужно перед пересозданием файла БД)
 */
function closeDbConnection() {
    global $dbConnection;
    $dbConnection = null;
}

/**
 * Инициализирует базу данных (создает таблицы, если их нет)
 */
function initDatabase() {
    // Проверяем флаг инициализации
    $flagFile = __DIR__ . '/.db_initialized';
    if (file_exists($flagFile)) return;
    
    $db = getDbConnection();
    
    // Таблица загрузок файлов
    $sql = "CREATE TABLE IF NOT EXISTS uploads (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        original_name TEXT NOT NULL,
        unique_name TEXT NOT NULL UNIQUE,
        username TEXT NOT NULL,
        upload_time DATETIME NOT NULL,
        project TEXT NOT NULL,
        file_size INTEGER DEFAULT 0,
        extension TEXT DEFAULT '',
        hyperlink_formula TEXT DEFAULT ''
    )";
    
    $db->exec($sql);
    
    // Таблица пользователей
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        full_name TEXT NOT NULL,
        username TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL,
        company TEXT DEFAULT '',
        comment TEXT DEFAULT '',
        is_active INTEGER DEFAULT 1,
        is_admin INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    
    $db->exec($sql);
    
    // Таблица проектов
    $sql = "CREATE TABLE IF NOT EXISTS projects (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL UNIQUE,
        description TEXT DEFAULT '',
        is_active INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    
    $db->exec($sql);
    
    // Миграция: добавить колонки, если их ещё нет (старые БД)
    try {
        $db->exec("ALTER TABLE uploads ADD COLUMN file_size INTEGER DEFAULT 0");
    } catch (PDOException $e) { /* колонка уже есть */ }
    try {
        $db->exec("ALTER TABLE uploads ADD COLUMN extension TEXT DEFAULT ''");
    } catch (PDOException $e) { /* колонка уже есть */ }
    try {
        $db->exec("ALTER TABLE uploads ADD COLUMN hyperlink_formula TEXT DEFAULT ''");
    } catch (PDOException $e) { /* колонка уже есть */ }
    try {
        $db->exec("ALTER TABLE users ADD COLUMN is_admin INTEGER DEFAULT 0");
    } catch (PDOException $e) { /* колонка уже есть */ }
    
    // Создаем индексы для быстрого поиска
    $db->exec("CREATE INDEX IF NOT EXISTS idx_username ON uploads(username)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_upload_time ON uploads(upload_time)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_project ON uploads(project)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_extension ON uploads(extension)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_users_username ON users(username)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_projects_name ON projects(name)");
    
    // Инициализация начальных данных
    initDefaultData();
    
    // Создаем флаг успешной инициализации
    touch($flagFile);
}

/**
 * Пересоздаёт базу данных (удаляет файл БД и создаёт заново). Опасная операция.
 */
function reinitDatabase() {
    closeDbConnection();
    if (file_exists(DB_PATH)) {
        unlink(DB_PATH);
    }
    // Удаляем флаг инициализации
    $flagFile = __DIR__ . '/.db_initialized';
    if (file_exists($flagFile)) {
        unlink($flagFile);
    }
    initDatabase();
}

/**
 * Добавляет запись о загруженном файле
 * @param string $originalName Оригинальное имя файла
 * @param string $uniqueName Уникальное имя файла на сервере
 * @param string $username Логин пользователя
 * @param string $project Название проекта
 * @param int $fileSize Размер файла в байтах
 * @param string $extension Расширение файла
 * @param string $hyperlinkFormula Формула HYPERLINK для Google Таблиц
 * @return int ID созданной записи
 */
function addUploadRecord($originalName, $uniqueName, $username, $project, $fileSize = 0, $extension = '', $hyperlinkFormula = '') {
    $db = getDbConnection();
    
    $sql = "INSERT INTO uploads (original_name, unique_name, username, upload_time, project, file_size, extension, hyperlink_formula) 
            VALUES (:original_name, :unique_name, :username, :upload_time, :project, :file_size, :extension, :hyperlink_formula)";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':original_name' => $originalName,
        ':unique_name' => $uniqueName,
        ':username' => $username,
        ':upload_time' => date('Y-m-d H:i:s'),
        ':project' => $project,
        ':file_size' => $fileSize,
        ':extension' => $extension,
        ':hyperlink_formula' => $hyperlinkFormula
    ]);
    
    return $db->lastInsertId();
}


/**
 * Получает историю загрузок пользователя с фильтрацией и пагинацией
 * @param string $username Логин пользователя
 * @param array $filters Фильтры ['project' => string, 'date_from' => string, 'date_to' => string, 'search' => string]
 * @param int $page Номер страницы (начиная с 1)
 * @param int $perPage Количество записей на странице
 * @return array ['files' => array, 'total' => int, 'pages' => int]
 */
function getUploadHistory($username, $filters = [], $page = 1, $perPage = FILES_PER_PAGE) {
    $db = getDbConnection();
    
    $where = ['username = :username'];
    $params = [':username' => $username];
    
    // Фильтр по проекту
    if (!empty($filters['project'])) {
        $where[] = 'project = :project';
        $params[':project'] = $filters['project'];
    }
    
    // Фильтр по дате от
    if (!empty($filters['date_from'])) {
        $where[] = 'DATE(upload_time) >= :date_from';
        $params[':date_from'] = $filters['date_from'];
    }
    
    // Фильтр по дате до
    if (!empty($filters['date_to'])) {
        $where[] = 'DATE(upload_time) <= :date_to';
        $params[':date_to'] = $filters['date_to'];
    }
    
    // Поиск по имени файла (оригинальное или уникальное)
    if (!empty($filters['search'])) {
        $where[] = '(original_name LIKE :search OR unique_name LIKE :search)';
        $params[':search'] = '%' . $filters['search'] . '%';
    }
    
    $whereClause = implode(' AND ', $where);
    
    // Получаем общее количество записей
    $countSql = "SELECT COUNT(*) as total FROM uploads WHERE {$whereClause}";
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];
    
    // Вычисляем пагинацию
    $pages = ceil($total / $perPage);
    $offset = ($page - 1) * $perPage;
    
    // Получаем записи с сортировкой по дате (новые сначала)
    $sql = "SELECT id, original_name, unique_name, upload_time, project, hyperlink_formula 
            FROM uploads 
            WHERE {$whereClause} 
            ORDER BY upload_time DESC 
            LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $files = $stmt->fetchAll();
    
    return [
        'files' => $files,
        'total' => $total,
        'pages' => $pages,
        'current_page' => $page
    ];
}

/**
 * Получает файл по уникальному имени
 * @param string $uniqueName Уникальное имя файла
 * @return array|null Данные файла или null, если не найден
 */
function getFileByUniqueName($uniqueName) {
    $db = getDbConnection();
    
    $sql = "SELECT * FROM uploads WHERE unique_name = :unique_name LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([':unique_name' => $uniqueName]);
    
    $result = $stmt->fetch();
    return $result ? $result : null;
}

/**
 * Получает файл по ID
 * @param int $id ID файла
 * @return array|null Данные файла или null, если не найден
 */
function getFileByUniqueNameFromId($id) {
    $db = getDbConnection();
    
    $sql = "SELECT * FROM uploads WHERE id = :id LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $id]);
    
    $result = $stmt->fetch();
    return $result ? $result : null;
}

/**
 * Список допустимых полей для сортировки в админке
 */
define('ADMIN_SORT_FIELDS', ['upload_time', 'username', 'project', 'original_name', 'file_size']);

/**
 * Получает все загрузки для админ-страницы с фильтрацией, сортировкой и пагинацией
 * @param array $filters ['username' => string, 'project' => string, 'date_from' => string, 'date_to' => string, 'search' => string]
 * @param string $sortField Поле сортировки
 * @param string $sortOrder ASC|DESC
 * @param int $page Номер страницы
 * @param int $perPage Записей на странице (если не задано — ADMIN_FILES_PER_PAGE или FILES_PER_PAGE)
 * @return array ['files' => array, 'total' => int, 'pages' => int, 'current_page' => int]
 */
function getAdminUploads($filters = [], $sortField = 'upload_time', $sortOrder = 'DESC', $page = 1, $perPage = null) {
    if ($perPage === null) {
        $perPage = defined('ADMIN_FILES_PER_PAGE') ? ADMIN_FILES_PER_PAGE : FILES_PER_PAGE;
    }
    $db = getDbConnection();
    
    $where = ['1=1'];
    $params = [];
    
    if (!empty($filters['username'])) {
        $where[] = 'username = :username';
        $params[':username'] = $filters['username'];
    }
    if (!empty($filters['project'])) {
        $where[] = 'project = :project';
        $params[':project'] = $filters['project'];
    }
    if (!empty($filters['date_from'])) {
        $where[] = 'DATE(upload_time) >= :date_from';
        $params[':date_from'] = $filters['date_from'];
    }
    if (!empty($filters['date_to'])) {
        $where[] = 'DATE(upload_time) <= :date_to';
        $params[':date_to'] = $filters['date_to'];
    }
    if (!empty($filters['search'])) {
        $where[] = '(original_name LIKE :search OR unique_name LIKE :search)';
        $params[':search'] = '%' . $filters['search'] . '%';
    }
    
    $whereClause = implode(' AND ', $where);
    
    if (!in_array($sortField, ADMIN_SORT_FIELDS, true)) {
        $sortField = 'upload_time';
    }
    $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';
    
    $countSql = "SELECT COUNT(*) as total FROM uploads WHERE {$whereClause}";
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $total = (int) $stmt->fetch()['total'];
    
    $pages = $total > 0 ? (int) ceil($total / $perPage) : 0;
    $offset = ($page - 1) * $perPage;
    
    $sql = "SELECT id, original_name, unique_name, username, upload_time, project, file_size, hyperlink_formula 
            FROM uploads 
            WHERE {$whereClause} 
            ORDER BY {$sortField} {$sortOrder} 
            LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $files = $stmt->fetchAll();
    
    return [
        'files' => $files,
        'total' => $total,
        'pages' => $pages,
        'current_page' => $page
    ];
}

/**
 * Статистика по загрузкам для админки: общее количество, общий размер, разбивка по расширениям
 * @return array ['total_count' => int, 'total_size' => int, 'by_extension' => [ext => ['count' => int, 'size' => int]]]
 */
function getAdminStats() {
    $db = getDbConnection();
    
    $stmt = $db->query("SELECT COUNT(*) as cnt, COALESCE(SUM(file_size), 0) as total_size FROM uploads");
    $row = $stmt->fetch();
    $totalCount = (int) $row['cnt'];
    $totalSize = (int) $row['total_size'];
    
    $stmt = $db->query("
        SELECT LOWER(COALESCE(NULLIF(extension, ''), 'без расширения')) as ext, 
               COUNT(*) as cnt, 
               COALESCE(SUM(file_size), 0) as sz 
        FROM uploads 
        GROUP BY LOWER(COALESCE(NULLIF(extension, ''), 'без расширения'))
        ORDER BY cnt DESC
    ");
    $byExtension = [];
    while ($r = $stmt->fetch()) {
        $byExtension[$r['ext']] = ['count' => (int) $r['cnt'], 'size' => (int) $r['sz']];
    }
    
    return [
        'total_count' => $totalCount,
        'total_size' => $totalSize,
        'by_extension' => $byExtension
    ];
}

/**
 * Возвращает список уникальных пользователей из БД (для фильтра в админке)
 * @return array
 */
function getAdminUserList() {
    $db = getDbConnection();
    $stmt = $db->query("SELECT DISTINCT username FROM uploads ORDER BY username");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Пересчитывает и сохраняет формулы HYPERLINK для всех записей в БД (по текущему BASE_URL)
 * @return int Количество обновлённых записей
 */
function recalcAllHyperlinkFormulas() {
    require_once __DIR__ . '/functions.php';
    $db = getDbConnection();
    $stmt = $db->query("SELECT id, original_name, unique_name FROM uploads");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $updated = 0;
    foreach ($rows as $row) {
        $url = generateFileUrl($row['unique_name']);
        $formula = generateHyperlinkFormula($url, $row['original_name']);
        $upd = $db->prepare("UPDATE uploads SET hyperlink_formula = :formula WHERE id = :id");
        $upd->execute([':formula' => $formula, ':id' => $row['id']]);
        $updated += $upd->rowCount();
    }
    return $updated;
}

// ========== CRUD функции для пользователей ==========

/**
 * Получение всех пользователей
 */
function getAllUsers() {
    $db = getDbConnection();
    $stmt = $db->query("SELECT id, full_name, username, company, comment, is_active, created_at, updated_at FROM users ORDER BY username");
    return $stmt->fetchAll();
}

/**
 * Получение пользователя по ID
 */
function getUserById($id) {
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Создание нового пользователя
 */
function createUser($fullName, $username, $password, $company = '', $comment = '') {
    $db = getDbConnection();
    $stmt = $db->prepare("INSERT INTO users (full_name, username, password_hash, company, comment) VALUES (?, ?, ?, ?, ?)");
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    return $stmt->execute([$fullName, $username, $passwordHash, $company, $comment]);
}

/**
 * Обновление пользователя
 */
function updateUser($id, $fullName, $username, $company = '', $comment = '', $isActive = 1, $password = null) {
    $db = getDbConnection();
    
    if ($password) {
        $stmt = $db->prepare("UPDATE users SET full_name = ?, username = ?, company = ?, comment = ?, is_active = ?, password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        return $stmt->execute([$fullName, $username, $company, $comment, $isActive, $passwordHash, $id]);
    } else {
        $stmt = $db->prepare("UPDATE users SET full_name = ?, username = ?, company = ?, comment = ?, is_active = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        return $stmt->execute([$fullName, $username, $company, $comment, $isActive, $id]);
    }
}

/**
 * Удаление пользователя
 */
function deleteUser($id) {
    $db = getDbConnection();
    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    return $stmt->execute([$id]);
}

// ========== CRUD функции для проектов ==========

/**
 * Получение всех проектов
 */
function getAllProjects() {
    $db = getDbConnection();
    $stmt = $db->query("SELECT id, name, description, is_active, created_at, updated_at FROM projects ORDER BY name");
    return $stmt->fetchAll();
}

/**
 * Получение активных проектов (для выпадающего списка)
 */
function getActiveProjects() {
    $db = getDbConnection();
    $stmt = $db->query("SELECT name FROM projects WHERE is_active = 1 ORDER BY name");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Получение проекта по ID
 */
function getProjectById($id) {
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Создание нового проекта
 */
function createProject($name, $description = '') {
    $db = getDbConnection();
    $stmt = $db->prepare("INSERT INTO projects (name, description) VALUES (?, ?)");
    return $stmt->execute([$name, $description]);
}

/**
 * Обновление проекта
 */
function updateProject($id, $name, $description = '', $isActive = 1) {
    $db = getDbConnection();
    $stmt = $db->prepare("UPDATE projects SET name = ?, description = ?, is_active = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    return $stmt->execute([$name, $description, $isActive, $id]);
}

/**
 * Удаление проекта
 */
function deleteProject($id) {
    $db = getDbConnection();
    $stmt = $db->prepare("DELETE FROM projects WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Инициализация начальных данных (пользователи и проекты)
 * ВНИМАНИЕ: Эта функция больше не создает пользователей по умолчанию
 * Используйте install.php для начальной установки
 */
function initDefaultData() {
    $db = getDbConnection();
    
    // Проверяем, есть ли уже проекты
    $stmt = $db->query("SELECT COUNT(*) as count FROM projects");
    $projectCount = $stmt->fetch()['count'];
    
    if ($projectCount == 0) {
        // Добавляем проекты по умолчанию
        $stmt = $db->prepare("INSERT INTO projects (name, description) VALUES (?, ?)");
        
        $defaultProjects = [
            ['Белфорель', 'Проект Белфорель'],
            ['Геопроект', 'Геологический проект'],
            ['Бета', 'Тестовый проект Бета']
        ];
        
        foreach ($defaultProjects as $project) {
            $stmt->execute($project);
        }
    }
    
    // Пользователи больше не создаются автоматически
    // Используйте install.php для создания начальных пользователей
}
