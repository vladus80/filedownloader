<?php
/**
 * Конфигурация приложения FileDownloader
 */

// Часовой пояс (для корректного отображения даты/времени загрузки)
date_default_timezone_set('Europe/Moscow');

// Базовый URL для ссылок на файлы
define('BASE_URL', 'http://10.0.1.1:8064/filedownloader/Accounts/');

// Путь к директории для сохранения файлов (относительно корня проекта)
define('UPLOAD_DIR', __DIR__ . '/Accounts/');

// Максимальный размер файла в байтах (10 МБ)
define('MAX_FILE_SIZE', 20 * 1024 * 1024);

// Разрешенные типы файлов (расширения)
define('ALLOWED_EXTENSIONS', [
    'pdf',
    'doc',
    'docx',
    'xls',
    'xlsx',
    'jpg',
    'jpeg',
    'png',
    'gif',
    'txt',
    'zip',
    'rar',
    '7z'
]);

// Настройки сессии
define('SESSION_NAME', 'filedownloader_session');
define('SESSION_LIFETIME', 3600); // 1 час

// Настройки базы данных
define('DB_PATH', __DIR__ . '/database.db');

// Настройки пагинации
define('FILES_PER_PAGE', 10); // Количество файлов на странице в истории
define('ADMIN_FILES_PER_PAGE', 10); // Количество файлов на странице в админке

// Лог действий администратора
define('ADMIN_LOG_PATH', __DIR__ . '/admin.log');
