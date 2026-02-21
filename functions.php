<?php
/**
 * Вспомогательные функции
 */

require_once __DIR__ . '/config.php';

/**
 * Генерирует безопасное имя файла для сохранения на сервере
 * @param string $originalName Оригинальное имя файла
 * @return string Безопасное имя файла
 */
function generateSafeFileName($originalName) {
    // Получаем расширение файла
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    
    // Генерируем уникальное имя: timestamp_random.extension
    $timestamp = time();
    $random = bin2hex(random_bytes(8));
    $safeName = $timestamp . '_' . $random . '.' . $extension;
    
    return $safeName;
}

/**
 * Проверяет, разрешен ли тип файла
 * @param string $filename Имя файла
 * @return bool
 */
function isAllowedFileType($filename) {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($extension, ALLOWED_EXTENSIONS);
}

/**
 * Проверяет MIME-тип файла
 * @param string $tmpName Временное имя файла
 * @param string $filename Оригинальное имя файла
 * @return array ['valid' => bool, 'error' => string]
 */
function validateFileMime($tmpName, $filename) {
    // Список разрешенных MIME-типов
    $allowedMimes = [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'xls' => ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'gif' => ['image/gif'],
        'txt' => ['text/plain'],
        'zip' => ['application/zip', 'application/x-zip-compressed'],
        'rar' => ['application/x-rar-compressed'],
        '7z' => ['application/x-7z-compressed'],
    ];
    
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    // Если расширение не в списке разрешенных, отклоняем
    if (!isset($allowedMimes[$extension])) {
        return ['valid' => false, 'error' => 'Запрещенное расширение файла'];
    }
    
    // Проверяем MIME-тип
    if (!extension_loaded('fileinfo')) {
        // Если fileinfo не доступен, пропускаем проверку (но логируем)
        error_log('Warning: fileinfo extension not loaded, MIME validation skipped');
        return ['valid' => true, 'error' => ''];
    }
    
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmpName);
    
    if (!in_array($mime, $allowedMimes[$extension])) {
        return ['valid' => false, 'error' => 'MIME-тип файла не соответствует расширению'];
    }
    
    return ['valid' => true, 'error' => ''];
}

/**
 * Валидирует загруженный файл
 * @param array $file Элемент из $_FILES
 * @return array ['valid' => bool, 'error' => string]
 */
function validateUploadedFile($file) {
    // Проверка наличия файла
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['valid' => false, 'error' => 'Файл не был загружен'];
    }
    
    // Проверка ошибок загрузки
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'Файл превышает максимальный размер, разрешенный сервером',
            UPLOAD_ERR_FORM_SIZE => 'Файл превышает максимальный размер, указанный в форме',
            UPLOAD_ERR_PARTIAL => 'Файл был загружен частично',
            UPLOAD_ERR_NO_TMP_DIR => 'Отсутствует временная папка',
            UPLOAD_ERR_CANT_WRITE => 'Не удалось записать файл на диск',
            UPLOAD_ERR_EXTENSION => 'Загрузка файла была остановлена расширением PHP',
        ];
        
        $error = $errorMessages[$file['error']] ?? 'Неизвестная ошибка загрузки файла';
        return ['valid' => false, 'error' => $error];
    }
    
    // Проверка размера файла
    if ($file['size'] > MAX_FILE_SIZE) {
        $maxSizeMB = MAX_FILE_SIZE / (1024 * 1024);
        return ['valid' => false, 'error' => "Размер файла превышает {$maxSizeMB} МБ"];
    }
    
    // Проверка типа файла
    if (!isAllowedFileType($file['name'])) {
        $allowed = implode(', ', ALLOWED_EXTENSIONS);
        return ['valid' => false, 'error' => "Разрешенные типы файлов: {$allowed}"];
    }
    
    // Проверка MIME-типа файла
    $mimeCheck = validateFileMime($file['tmp_name'], $file['name']);
    if (!$mimeCheck['valid']) {
        return ['valid' => false, 'error' => $mimeCheck['error']];
    }
    
    // Проверка, что это действительно загруженный файл
    if (!is_uploaded_file($file['tmp_name'])) {
        return ['valid' => false, 'error' => 'Файл не был загружен через форму'];
    }
    
    return ['valid' => true, 'error' => ''];
}

/**
 * Сохраняет загруженный файл
 * @param array $file Элемент из $_FILES
 * @return array ['success' => bool, 'filename' => string, 'error' => string]
 */
function saveUploadedFile($file) {
    // Создаем директорию, если её нет
    if (!is_dir(UPLOAD_DIR)) {
        if (!mkdir(UPLOAD_DIR, 0755, true)) {
            return ['success' => false, 'filename' => '', 'error' => 'Не удалось создать директорию для загрузки'];
        }
    }
    
    // Генерируем безопасное имя файла
    $safeFileName = generateSafeFileName($file['name']);
    $destination = UPLOAD_DIR . $safeFileName;
    
    // Перемещаем файл
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => true, 'filename' => $safeFileName, 'error' => ''];
    } else {
        return ['success' => false, 'filename' => '', 'error' => 'Не удалось сохранить файл на сервере'];
    }
}

/**
 * Генерирует URL для файла
 * @param string $filename Имя файла на сервере
 * @return string URL файла
 */
function generateFileUrl($filename) {
    return BASE_URL . urlencode($filename);
}

/**
 * Генерирует формулу HYPERLINK для Google Sheets
 * @param string $url URL файла
 * @param string $displayText Текст ссылки (обычно оригинальное имя файла)
 * @return string Формула HYPERLINK
 */
function generateHyperlinkFormula($url, $displayText) {
    // Экранируем кавычки в тексте ссылки
    $escapedText = str_replace('"', '""', $displayText);
    return '=HYPERLINK("' . $url . '";"' . $escapedText . '")';
}

/**
 * Format file size
 * @param int $bytes
 * @return string
 */
function formatFileSize($bytes) {
    if ($bytes === 0) return '0 Bytes';
    
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

/**
 * Get file type icon
 * @param string $filename
 * @return string
 */
function getFileIcon($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $iconMap = [
        'pdf' => '📄',
        'doc' => '📝', 'docx' => '📝',
        'xls' => '📊', 'xlsx' => '📊',
        'jpg' => '🖼️', 'jpeg' => '🖼️', 'png' => '🖼️', 'gif' => '🖼️',
        'txt' => '📄',
        'zip' => '📦', 'rar' => '📦', '7z' => '📦'
    ];
    
    return $iconMap[$ext] ?? '📎';
}

/**
 * Проверяет, существует ли файл в папке Accounts
 * @param string $uniqueName Уникальное имя файла
 * @return bool
 */
function fileExistsInAccounts($uniqueName) {
    $path = UPLOAD_DIR . $uniqueName;
    return is_file($path);
}

/**
 * Записывает действие администратора в лог
 * @param string $action Название действия
 * @param string $details Дополнительные сведения
 */
function logAdminAction($action, $details = '') {
    if (!defined('ADMIN_LOG_PATH')) {
        return;
    }
    $user = function_exists('getCurrentUser') ? getCurrentUser() : '?';
    $line = date('Y-m-d H:i:s') . ' | ' . $user . ' | ' . $action . ($details !== '' ? ' | ' . $details : '') . "\n";
    @file_put_contents(ADMIN_LOG_PATH, $line, FILE_APPEND | LOCK_EX);
}
