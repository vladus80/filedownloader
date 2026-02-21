<?php
/**
 * Страница администрирования (только для пользователя admin)
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/database.php';

requireAuth();

if (!isAdmin()) {
    header('Location: index.php');
    exit;
}

initDatabase();

$adminMessage = '';
$adminMessageType = '';

// Обработка success сообщения из GET
if (isset($_GET['success'])) {
    $adminMessage = urldecode($_GET['success']);
    $adminMessageType = 'success';
}

// Обработка переключения вкладок
$activeTab = $_GET['tab'] ?? 'files';

// Опасные операции по POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверяем CSRF токен
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        die('Недопустимый запрос');
    }
    
    $action = $_POST['action'] ?? '';
    $confirm = isset($_POST['confirm']) && $_POST['confirm'] === '1';
    
    if ($action === 'reinit_db') {
        if (!$confirm) {
            $adminMessage = 'Подтвердите операцию, установив галочку.';
            $adminMessageType = 'error';
        } else {
            try {
                reinitDatabase();
                logAdminAction('reinit_db', 'База данных пересоздана');
                $adminMessage = 'База данных успешно пересоздана. Все записи об загрузках удалены.';
                $adminMessageType = 'success';
            } catch (Exception $e) {
                $adminMessage = 'Ошибка: ' . htmlspecialchars($e->getMessage());
                $adminMessageType = 'error';
            }
        }
    } elseif ($action === 'clear_accounts') {
        if (!$confirm) {
            $adminMessage = 'Подтвердите операцию, установив галочку.';
            $adminMessageType = 'error';
        } else {
            $deleted = 0;
            if (is_dir(UPLOAD_DIR)) {
                foreach (glob(UPLOAD_DIR . '*') as $path) {
                    if (is_file($path) && @unlink($path)) {
                        $deleted++;
                    }
                }
            }
            logAdminAction('clear_accounts', "Удалено файлов: $deleted");
            $adminMessage = "Очистка папки Accounts завершена. Удалено файлов: $deleted.";
            $adminMessageType = 'success';
        }
    } elseif ($action === 'recalc_formulas') {
        try {
            $updated = recalcAllHyperlinkFormulas();
            logAdminAction('recalc_formulas', "Обновлено записей: {$updated}");
            $adminMessage = "Формулы пересчитаны. Обновлено записей: {$updated}.";
            $adminMessageType = 'success';
        } catch (Exception $e) {
            $adminMessage = 'Ошибка: ' . htmlspecialchars($e->getMessage());
            $adminMessageType = 'error';
        }
    }
}

// CRUD операции для пользователей и проектов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF-проверка
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        die('Недопустимый запрос');
    }
    
    $action = $_POST['action'] ?? '';
    
    // Управление пользователями
    if ($action === 'create_user') {
        $fullName = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $company = trim($_POST['company'] ?? '');
        $comment = trim($_POST['comment'] ?? '');
        
        if (empty($fullName) || empty($username) || empty($password)) {
            $adminMessage = 'ФИО, логин и пароль являются обязательными полями';
            $adminMessageType = 'error';
        } elseif (strlen($username) < 3) {
            $adminMessage = 'Логин должен содержать минимум 3 символа';
            $adminMessageType = 'error';
        } elseif (strlen($password) < 6) {
            $adminMessage = 'Пароль должен содержать минимум 6 символов';
            $adminMessageType = 'error';
        } else {
            try {
                createUser($fullName, $username, $password, $company, $comment);
                logAdminAction('create_user', "Логин: $username, ФИО: $fullName");
                $adminMessage = 'Пользователь успешно создан';
                $adminMessageType = 'success';
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
                    $adminMessage = 'Пользователь с таким логином уже существует';
                    $adminMessageType = 'error';
                } else {
                    $adminMessage = 'Ошибка создания пользователя: ' . $e->getMessage();
                    $adminMessageType = 'error';
                }
            }
        }
    } elseif ($action === 'update_user') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $fullName = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $company = trim($_POST['company'] ?? '');
        $comment = trim($_POST['comment'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($fullName) || empty($username)) {
            $adminMessage = 'ФИО и логин являются обязательными полями';
            $adminMessageType = 'error';
        } elseif (strlen($username) < 3) {
            $adminMessage = 'Логин должен содержать минимум 3 символа';
            $adminMessageType = 'error';
        } elseif (!empty($password) && strlen($password) < 6) {
            $adminMessage = 'Пароль должен содержать минимум 6 символов';
            $adminMessageType = 'error';
        } else {
            try {
                updateUser($userId, $fullName, $username, $company, $comment, $isActive, $password ?: null);
                logAdminAction('update_user', "ID: $userId, Логин: $username");
                $adminMessage = 'Пользователь успешно обновлен';
                $adminMessageType = 'success';
                
                // Редирект чтобы закрыть форму редактирования
                header('Location: admin.php?tab=users&success=' . urlencode($adminMessage));
                exit;
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
                    $adminMessage = 'Пользователь с таким логином уже существует';
                    $adminMessageType = 'error';
                } else {
                    $adminMessage = 'Ошибка обновления пользователя: ' . $e->getMessage();
                    $adminMessageType = 'error';
                }
            }
        }
    } elseif ($action === 'delete_user') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $user = getUserById($userId);
        
        if ($user && $user['username'] === getCurrentUser()) {
            $adminMessage = 'Нельзя удалить текущего пользователя';
            $adminMessageType = 'error';
        } elseif ($user && $user['username'] === 'admin') {
            $adminMessage = 'Нельзя удалить администратора';
            $adminMessageType = 'error';
        } else {
            try {
                deleteUser($userId);
                logAdminAction('delete_user', "ID: $userId, Логин: " . $user['username']);
                $adminMessage = 'Пользователь успешно удален';
                $adminMessageType = 'success';
            } catch (PDOException $e) {
                $adminMessage = 'Ошибка удаления пользователя: ' . $e->getMessage();
                $adminMessageType = 'error';
            }
        }
    }
    
    // Управление проектами
    elseif ($action === 'create_project') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if (empty($name)) {
            $adminMessage = 'Название проекта является обязательным полем';
            $adminMessageType = 'error';
        } else {
            try {
                createProject($name, $description);
                logAdminAction('create_project', "Название: $name");
                $adminMessage = 'Проект успешно создан';
                $adminMessageType = 'success';
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
                    $adminMessage = 'Проект с таким названием уже существует';
                    $adminMessageType = 'error';
                } else {
                    $adminMessage = 'Ошибка создания проекта: ' . $e->getMessage();
                    $adminMessageType = 'error';
                }
            }
        }
    } elseif ($action === 'update_project') {
        $projectId = (int)($_POST['project_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($name)) {
            $adminMessage = 'Название проекта является обязательным полем';
            $adminMessageType = 'error';
        } else {
            try {
                updateProject($projectId, $name, $description, $isActive);
                logAdminAction('update_project', "ID: $projectId, Название: $name");
                $adminMessage = 'Проект успешно обновлен';
                $adminMessageType = 'success';
                
                // Редирект чтобы закрыть форму редактирования
                header('Location: admin.php?tab=projects&success=' . urlencode($adminMessage));
                exit;
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
                    $adminMessage = 'Проект с таким названием уже существует';
                    $adminMessageType = 'error';
                } else {
                    $adminMessage = 'Ошибка обновления проекта: ' . $e->getMessage();
                    $adminMessageType = 'error';
                }
            }
        }
    } elseif ($action === 'delete_project') {
        $projectId = (int)($_POST['project_id'] ?? 0);
        $project = getProjectById($projectId);
        
        // Проверяем, есть ли файлы с этим проектом
        $db = getDbConnection();
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM uploads WHERE project = ?");
        $stmt->execute([$project['name']]);
        $fileCount = $stmt->fetch()['count'];
        
        if ($fileCount > 0) {
            $adminMessage = "Нельзя удалить проект, так как с ним связано $fileCount файлов";
            $adminMessageType = 'error';
        } else {
            try {
                deleteProject($projectId);
                logAdminAction('delete_project', "ID: $projectId, Название: " . $project['name']);
                $adminMessage = 'Проект успешно удален';
                $adminMessageType = 'success';
            } catch (PDOException $e) {
                $adminMessage = 'Ошибка удаления проекта: ' . $e->getMessage();
                $adminMessageType = 'error';
            }
        }
    }
}

// Скачивание копии БД
if (isset($_GET['download_db']) && $_GET['download_db'] === '1') {
    logAdminAction('download_db', 'Скачана копия БД');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="filedownloader_backup_' . date('Y-m-d_His') . '.db"');
    readfile(DB_PATH);
    exit;
}

// Экспорт в CSV (текущая выборка с учётом фильтров, без пагинации)
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $csvFilters = [
    'username' => trim($_GET['username'] ?? ''),
    'project' => trim($_GET['project'] ?? ''),
    'date_from' => trim($_GET['date_from'] ?? ''),
    'date_to' => trim($_GET['date_to'] ?? ''),
    'search' => trim($_GET['search'] ?? ''),
];
    $sort = $_GET['sort'] ?? 'upload_time';
    $order = $_GET['order'] ?? 'DESC';
    $all = getAdminUploads($csvFilters, $sort, $order, 1, 999999);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="uploads_' . date('Y-m-d_His') . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, "\xEF\xBB\xBF"); // BOM for Excel
    fputcsv($out, ['Дата', 'Пользователь', 'Проект', 'Оригинальное имя', 'Уникальное имя', 'Размер', 'Формула HYPERLINK', 'URL'], ';');
    foreach ($all['files'] as $r) {
        $url = generateFileUrl($r['unique_name']);
        $formula = !empty($r['hyperlink_formula']) ? $r['hyperlink_formula'] : generateHyperlinkFormula($url, $r['original_name']);
        fputcsv($out, [
            $r['upload_time'],
            $r['username'],
            $r['project'],
            $r['original_name'],
            $r['unique_name'],
            $r['file_size'] ?? 0,
            $formula,
            $url
        ], ';');
    }
    fclose($out);
    exit;
}

// Скачивание файла
if (isset($_GET['download'])) {
    $fileId = (int)$_GET['download'];
    $file = getFileByUniqueNameFromId($fileId);
    
    if (!$file) {
        $adminMessage = 'Файл не найден';
        $adminMessageType = 'error';
    } else {
        $filePath = UPLOAD_DIR . $file['unique_name'];
        if (!file_exists($filePath)) {
            $adminMessage = 'Файл не найден на сервере';
            $adminMessageType = 'error';
        } else {
            logAdminAction('download_file', "ID: {$fileId}, Имя: {$file['original_name']}");
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . htmlspecialchars($file['original_name']) . '"');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit;
        }
    }
}

// Удаление файла
if (isset($_POST['action']) && $_POST['action'] === 'delete_file') {
    // CSRF-проверка
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        die('Недопустимый запрос');
    }
    
    $fileId = (int)($_POST['file_id'] ?? 0);
    $file = getFileByUniqueNameFromId($fileId);
    
    if (!$file) {
        $adminMessage = 'Файл не найден';
        $adminMessageType = 'error';
    } else {
        try {
            $db = getDbConnection();
            $db->beginTransaction();
            
            // Удаляем запись из БД
            $stmt = $db->prepare("DELETE FROM uploads WHERE id = ?");
            $stmt->execute([$fileId]);
            
            // Удаляем файл с сервера
            $filePath = UPLOAD_DIR . $file['unique_name'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            $db->commit();
            logAdminAction('delete_file', "ID: {$fileId}, Имя: {$file['original_name']}");
            $adminMessage = 'Файл успешно удален';
            $adminMessageType = 'success';
        } catch (Exception $e) {
            $db->rollBack();
            $adminMessage = 'Ошибка при удалении файла: ' . $e->getMessage();
            $adminMessageType = 'error';
        }
    }
}

// Параметры таблицы и фильтров
$page = max(1, intval($_GET['page'] ?? 1));
$sortField = $_GET['sort'] ?? 'upload_time';
$sortOrder = $_GET['order'] ?? 'DESC';
$filters = [
    'username' => trim($_GET['username'] ?? ''),
    'project' => trim($_GET['project'] ?? ''),
    'date_from' => trim($_GET['date_from'] ?? ''),
    'date_to' => trim($_GET['date_to'] ?? ''),
    'search' => trim($_GET['search'] ?? ''),
];

$uploads = getAdminUploads($filters, $sortField, $sortOrder, $page);
$stats = getAdminStats();
$userList = getAdminUserList();

$pageTitle = 'Администрирование - FileDownloader';
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Делаем функцию глобальной
    window.showTab = function(tabName) {
        console.log('showTab called with:', tabName);
        
        // Сохраняем активную вкладку в localStorage
        localStorage.setItem('activeAdminTab', tabName);
        
        // Скрываем все вкладки
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.add('hidden');
        });
        
        // Убираем активный класс у всех кнопок
        document.querySelectorAll('.tab-button').forEach(button => {
            button.classList.remove('border-blue-500', 'text-blue-600', 'dark:text-blue-400');
            button.classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'dark:text-gray-400', 'dark:hover:text-gray-300');
        });
        
        // Показываем выбранную вкладку
        const tabContent = document.getElementById('tab-content-' + tabName);
        console.log('Looking for element:', 'tab-content-' + tabName);
        console.log('Found element:', tabContent);
        if (tabContent) {
            tabContent.classList.remove('hidden');
            console.log('Tab content shown');
        } else {
            console.error('Tab content not found!');
        }
        
        // Активируем кнопку
        const activeButton = document.getElementById('tab-' + tabName);
        if (activeButton) {
            activeButton.classList.remove('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'dark:text-gray-400', 'dark:hover:text-gray-300');
            activeButton.classList.add('border-blue-500', 'text-blue-600', 'dark:text-blue-400');
        }
    };
    
    // Восстанавливаем активную вкладку после загрузки страницы
    window.restoreActiveTab = function() {
        // Приоритет: GET параметр > localStorage > по умолчанию 'files'
        const urlParams = new URLSearchParams(window.location.search);
        const tabFromUrl = urlParams.get('tab');
        const savedTab = localStorage.getItem('activeAdminTab');
        const targetTab = tabFromUrl || savedTab || 'files';
        
        if (targetTab !== 'files') {
            // Если нужно переключиться на вкладку не "Файлы"
            setTimeout(() => {
                showTab(targetTab);
            }, 100);
        }
    };
});
</script>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="text-center mb-8">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">Администрирование</h2>
            <p class="text-lg text-gray-600 dark:text-gray-400">Управление файлами, пользователями и проектами</p>
        </div>

        <?php if ($adminMessage): ?>
            <div class="mb-6 p-4 rounded-lg border <?php echo $adminMessageType === 'success' ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800 text-green-800 dark:text-green-200' : 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800 text-red-800 dark:text-red-200'; ?>">
                <?php echo $adminMessage; ?>
            </div>
        <?php endif; ?>

        <!-- Вкладки -->
        <div class="border-b border-gray-200 dark:border-gray-700 mb-8">
            <nav class="-mb-px flex space-x-8">
                <button onclick="showTab('admin')" id="tab-admin" class="tab-button border-b-2 border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 px-1 py-4 text-sm font-medium">
                    ⚙️ Администрирование
                </button>
                <button onclick="showTab('files')" id="tab-files" class="tab-button active border-b-2 border-blue-500 text-blue-600 dark:text-blue-400 px-1 py-4 text-sm font-medium">
                    📁 Файлы
                </button>
                <button onclick="showTab('users')" id="tab-users" class="tab-button border-b-2 border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 px-1 py-4 text-sm font-medium">
                    👥 Пользователи
                </button>
                <button onclick="showTab('projects')" id="tab-projects" class="tab-button border-b-2 border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 px-1 py-4 text-sm font-medium">
                    📁 Проекты
                </button>
            </nav>
        </div>

        <!-- Содержимое вкладок -->
        <div id="tab-content-admin" class="tab-content hidden">
            <?php include __DIR__ . '/includes/admin/admin_system.php'; ?>
        </div>

        <div id="tab-content-files" class="tab-content">
            <?php 
            echo "<!-- DEBUG: Starting files tab -->";
            // Явно передаем переменные в include
            $uploads_for_include = $uploads;
            $filters_for_include = $filters;
            $userList_for_include = $userList;
            $stats_for_include = $stats;
            echo "<!-- DEBUG: Variables set, including file -->";
            
            // Включаем отображение ошибок
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
            
            try {
                include __DIR__ . '/includes/admin/admin_files.php';
                echo "<!-- DEBUG: admin_files.php included successfully -->";
            } catch (ParseError $e) {
                echo "<!-- PARSE ERROR in admin_files.php: " . $e->getMessage() . " on line " . $e->getLine() . " -->";
            } catch (Error $e) {
                echo "<!-- FATAL ERROR in admin_files.php: " . $e->getMessage() . " on line " . $e->getLine() . " -->";
            } catch (Exception $e) {
                echo "<!-- ERROR in admin_files.php: " . $e->getMessage() . " -->";
            }
            echo "<!-- DEBUG: Files tab finished -->";
            ?>
        </div>

        <!-- DEBUG: After files tab -->
        <?php echo "<!-- DEBUG: After files tab, before users tab -->"; ?>

        <div id="tab-content-users" class="tab-content hidden">
            <?php 
            echo "цццццц<!-- DEBUG: Starting users tab -->";
            try {
                include __DIR__ . '/includes/admin/admin_users.php';
                echo "<!-- DEBUG: Users tab included successfully -->";
            } catch (Exception $e) {
                echo "<!-- DEBUG: Error in users tab: " . $e->getMessage() . " -->";
            }
            ?>
        </div>

        <div id="tab-content-projects" class="tab-content hidden">
            <?php 
            echo "<!-- DEBUG: Starting projects tab -->";
            $adminMessage_for_include = $adminMessage ?? '';
            $adminMessageType_for_include = $adminMessageType ?? 'success';
            try {
                include __DIR__ . '/includes/admin/admin_projects.php';
                echo "<!-- DEBUG: Projects tab included successfully -->";
            } catch (Exception $e) {
                echo "<!-- DEBUG: Error in projects tab: " . $e->getMessage() . " -->";
            }
            ?>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>

<script>
// Восстанавливаем активную вкладку после загрузки страницы
document.addEventListener('DOMContentLoaded', function() {
    restoreActiveTab();
});
</script>
