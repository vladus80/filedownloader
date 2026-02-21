<?php
/**
 * Функции авторизации
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

// Запускаем сессию
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// Инициализация CSRF токена
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Проверяет, авторизован ли пользователь
 * @return bool
 */
function isAuthenticated() {
    return isset($_SESSION['user']) && !empty($_SESSION['user']);
}

/**
 * Получает имя текущего пользователя
 * @return string|null
 */
function getCurrentUser() {
    return $_SESSION['user'] ?? null;
}

/**
 * Получает данные текущего пользователя из БД
 * @return array|null
 */
function getCurrentUserData() {
    $username = getCurrentUser();
    if (!$username) {
        return null;
    }
    
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
    $stmt->execute([$username]);
    return $stmt->fetch();
}

/**
 * Проверяет логин и пароль
 * @param string $username
 * @param string $password
 * @return bool
 */
function checkCredentials($username, $password) {
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if (!$user) {
        return false;
    }
    
    return password_verify($password, $user['password_hash']);
}

/**
 * Выполняет вход пользователя
 * @param string $username
 * @return bool
 */
function login($username) {
    session_regenerate_id(true); // Защита от Session Fixation
    $_SESSION['user'] = $username;
    $_SESSION['login_time'] = time();
    return true;
}

/**
 * Выполняет выход пользователя
 */
function logout() {
    $_SESSION = [];
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    session_destroy();
}

/**
 * Проверяет, является ли текущий пользователь администратором
 * @return bool
 */
function isAdmin() {
    $username = getCurrentUser();
    if (!$username) {
        return false;
    }
    
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT is_admin FROM users WHERE username = ? AND is_active = 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    return $user && (int)$user['is_admin'] === 1;
}

/**
 * Проверяет CSRF токен
 * @param string $token Токен из формы
 * @return bool
 */
function validateCSRF($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Получает CSRF токен
 * @return string
 */
function getCSRFToken() {
    return $_SESSION['csrf_token'] ?? '';
}

/**
 * Проверяет авторизацию и перенаправляет на страницу входа, если не авторизован
 */
function requireAuth() {
    if (!isAuthenticated()) {
        header('Location: login.php');
        exit;
    }
    
    // Проверка времени жизни сессии
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > SESSION_LIFETIME) {
        logout();
        header('Location: login.php?expired=1');
        exit;
    }
}
