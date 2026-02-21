<?php
/**
 * Установочный файл для FileDownloader
 * Генерирует случайные пароли и создает начальную структуру БД
 */

// Определяем абсолютный путь к директории
$basePath = dirname(__FILE__);

require_once $basePath . '/config.php';
require_once $basePath . '/database.php';

// Проверяем, что БД еще не инициализирована
if (file_exists(DB_PATH)) {
    die("База данных уже существует. Удалите файл " . DB_PATH . " для переустановки.");
}

// Генерируем случайные пароли с обработкой ошибок
try {
    $adminPassword = bin2hex(random_bytes(8)); // 16 символов
    $userPassword = bin2hex(random_bytes(6));  // 12 символов
} catch (Exception $e) {
    // Если random_bytes не доступен, используем альтернативный метод
    $adminPassword = substr(md5(time() . rand()), 0, 16);
    $userPassword = substr(md5(time() . rand() . uniqid()), 0, 12);
}

// Создаем функцию для инициализации с нашими паролями
function initDatabaseWithPasswords($adminPass, $userPass) {
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
    
    // Создаем индексы
    $db->exec("CREATE INDEX IF NOT EXISTS idx_username ON uploads(username)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_upload_time ON uploads(upload_time)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_project ON uploads(project)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_extension ON uploads(extension)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_users_username ON users(username)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_projects_name ON projects(name)");
    
    // Добавляем пользователей с сгенерированными паролями
    $stmt = $db->prepare("INSERT INTO users (full_name, username, password_hash, company, comment, is_admin) VALUES (?, ?, ?, ?, ?, ?)");
    
    $defaultUsers = [
        ['Администратор', 'admin', password_hash($adminPass, PASSWORD_DEFAULT), '', 'Системный администратор', 1],
        ['Лена', 'lena', password_hash($userPass, PASSWORD_DEFAULT), '', 'Базовый пользователь', 0]
    ];
    
    foreach ($defaultUsers as $user) {
        $stmt->execute($user);
    }
    
    // Добавляем проекты
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

// Запускаем установку
try {
    initDatabaseWithPasswords($adminPassword, $userPassword);
} catch (Exception $e) {
    die("Ошибка при создании базы данных: " . $e->getMessage());
}

// Создаем папку для загрузок
if (!is_dir(UPLOAD_DIR)) {
    if (!mkdir(UPLOAD_DIR, 0755, true)) {
        die("Не удалось создать директорию для загрузок: " . UPLOAD_DIR);
    }
}

// Выводим результаты
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Установка завершена - FileDownloader</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg p-8 max-w-md w-full">
        <div class="text-center">
            <svg class="w-16 h-16 text-green-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <h1 class="text-2xl font-bold text-gray-900 mb-4">Установка завершена!</h1>
            
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <h2 class="text-lg font-semibold text-yellow-800 mb-3">⚠️ Сохраните эти данные!</h2>
                <div class="text-left space-y-2">
                    <div class="bg-white rounded p-3">
                        <p class="text-sm font-medium text-gray-700">Администратор:</p>
                        <p class="text-xs text-gray-600">Логин: <code class="bg-gray-100 px-1 rounded">admin</code></p>
                        <p class="text-xs text-gray-600">Пароль: <code class="bg-gray-100 px-1 rounded text-red-600 font-bold"><?php echo htmlspecialchars($adminPassword); ?></code></p>
                    </div>
                    <div class="bg-white rounded p-3">
                        <p class="text-sm font-medium text-gray-700">Пользователь:</p>
                        <p class="text-xs text-gray-600">Логин: <code class="bg-gray-100 px-1 rounded">lena</code></p>
                        <p class="text-xs text-gray-600">Пароль: <code class="bg-gray-100 px-1 rounded text-red-600 font-bold"><?php echo htmlspecialchars($userPassword); ?></code></p>
                    </div>
                </div>
                <p class="text-xs text-yellow-700 mt-3">⚠️ После входа в систему сразу смените пароли!</p>
            </div>
            
            <div class="space-y-3">
                <a href="index.php" class="block w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">
                    Перейти к входу в систему
                </a>
                <button onclick="if(confirm('Удалить этот установочный файл?')) { window.location.href='install.php?delete=1'; }" 
                        class="w-full bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">
                    Удалить установочный файл
                </button>
            </div>
        </div>
    </div>
</body>
</html>

<?php
// Обработка удаления установочного файла
if (isset($_GET['delete']) && $_GET['delete'] == '1') {
    unlink(__FILE__);
    header('Location: index.php');
    exit;
}
?>
