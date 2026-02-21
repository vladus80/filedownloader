<?php
/**
 * Обработчик загрузки файлов
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/database.php';

// Проверяем авторизацию
requireAuth();

// Инициализируем БД при первом запуске
initDatabase();

// Проверяем метод запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Проверяем CSRF токен
if (!validateCSRF($_POST['csrf_token'] ?? '')) {
    if ($isAjax) {
        uploadRespondJson(false, ['error' => 'Недопустимый запрос']);
    } else {
        header('Location: index.php?error=' . urlencode('Недопустимый запрос'));
    }
    exit;
}

$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

function uploadRespondRedirect($url) {
    header('Location: ' . $url);
    exit;
}
function uploadRespondJson($ok, $data = []) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['ok' => $ok], $data));
    exit;
}

// Проверяем наличие файла
if (!isset($_FILES['file'])) {
    if ($isAjax) uploadRespondJson(false, ['error' => 'Файл не был отправлен']);
    uploadRespondRedirect('index.php?error=' . urlencode('Файл не был отправлен'));
}

// Проверяем наличие проекта
$project = trim($_POST['project'] ?? '');
$activeProjects = getActiveProjects();
if (empty($project) || !in_array($project, $activeProjects)) {
    if ($isAjax) uploadRespondJson(false, ['error' => 'Необходимо выбрать проект']);
    uploadRespondRedirect('index.php?error=' . urlencode('Необходимо выбрать проект'));
}

$file = $_FILES['file'];

// Валидируем файл
$validation = validateUploadedFile($file);
if (!$validation['valid']) {
    if ($isAjax) uploadRespondJson(false, ['error' => $validation['error']]);
    uploadRespondRedirect('index.php?error=' . urlencode($validation['error']));
}

// Сохраняем файл
$saveResult = saveUploadedFile($file);
if (!$saveResult['success']) {
    if ($isAjax) uploadRespondJson(false, ['error' => $saveResult['error']]);
    uploadRespondRedirect('index.php?error=' . urlencode($saveResult['error']));
}

// Генерируем URL и формулу
$fileUrl = generateFileUrl($saveResult['filename']);
$originalFileName = $file['name'];
$formula = generateHyperlinkFormula($fileUrl, $originalFileName);

// Сохраняем запись в базу данных
$ext = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));
try {
    addUploadRecord($originalFileName, $saveResult['filename'], getCurrentUser(), $project, (int) $file['size'], $ext, $formula);
} catch (Exception $e) {
    error_log('Ошибка записи в БД: ' . $e->getMessage());
}

if ($isAjax) {
    uploadRespondJson(true, ['formula' => $formula, 'url' => $fileUrl]);
}
header('Location: index.php?success=1&formula=' . urlencode($formula) . '&url=' . urlencode($fileUrl));
exit;
