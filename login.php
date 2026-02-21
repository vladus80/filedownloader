<?php
/**
 * Страница авторизации
 */

require_once __DIR__ . '/auth.php';

// Если уже авторизован, перенаправляем на главную
if (isAuthenticated()) {
    header('Location: index.php');
    exit;
}

$error = '';

// Обработка формы входа
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Введите логин и пароль';
    } else {
        // Проверка на брутфорс
        $attempts = $_SESSION['login_attempts'] ?? 0;
        $lastAttempt = $_SESSION['login_attempt_time'] ?? 0;
        
        if ($attempts >= 5 && (time() - $lastAttempt) < 300) { // 5 минут блокировка
            $waitTime = 300 - (time() - $lastAttempt);
            $error = "Слишком много попыток. Подождите " . ceil($waitTime / 60) . " минут.";
        } elseif (checkCredentials($username, $password)) {
            login($username);
            // Сбрасываем счетчик попыток при успешном входе
            unset($_SESSION['login_attempts']);
            unset($_SESSION['login_attempt_time']);
            header('Location: index.php');
            exit;
        } else {
            // Увеличиваем счетчик неудачных попыток
            $_SESSION['login_attempts'] = $attempts + 1;
            $_SESSION['login_attempt_time'] = time();
            
            $remainingAttempts = 5 - $_SESSION['login_attempts'];
            if ($remainingAttempts > 0) {
                $error = "Неверный логин или пароль. Осталось попыток: $remainingAttempts";
            } else {
                $error = "Слишком много попыток. Аккаунт заблокирован на 5 минут.";
            }
        }
    }
}

// Проверка истекшей сессии
if (isset($_GET['expired'])) {
    $error = 'Сессия истекла. Пожалуйста, войдите снова.';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в систему - FileDownloader</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background-color: #f5f5f5;
        }
        .login-container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: bold;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 14px;
        }
        input[type="submit"] {
            width: 100%;
            padding: 12px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #0056b3;
        }
        .error {
            color: #dc3545;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Вход в систему</h1>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Логин:</label>
                <input type="text" id="username" name="username" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">Пароль:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <input type="submit" value="Войти">
        </form>
    </div>
</body>
</html>
