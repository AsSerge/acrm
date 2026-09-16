<?php
/**
 * Страница входа для пользователей сайта
 */

require_once __DIR__ . '/base/config.php';
require_once __DIR__ . '/classes.php';

$auth = new Auth();

// Если уже залогинен — редирект на главную
if ($auth->isLoggedIn()) {
    header('Location: /');
    exit;
}

$error = '';

// Обработка формы входа
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Пожалуйста, заполните все поля';
    } else {
        if ($auth->login($username, $password)) {
            header('Location: /');
            exit;
        } else {
            $error = 'Неверное имя пользователя или пароль';
        }
    }
}

// Подключаем шапку
require_once __DIR__ . '/layout/header.php';
?>

<div class="login-page">
    <div class="login-container">
        <h1>Вход на сайт</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" class="login-form">
            <div class="form-group">
                <label for="username">Имя пользователя или Email</label>
                <input type="text" id="username" name="username" class="form-control" 
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">Пароль</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Войти</button>
        </form>
        
        <p class="login-links">
            <a href="/">Вернуться на главную</a>
        </p>
    </div>
</div>

<style>
.login-page {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 60vh;
    padding: 40px 20px;
}

.login-container {
    max-width: 400px;
    width: 100%;
    background: var(--color-white);
    padding: 40px;
    border-radius: 12px;
    box-shadow: 0 4px 30px rgba(0,0,0,0.1);
    border: 1px solid var(--color-border);
}

.login-container h1 {
    font-size: 24px;
    font-weight: 600;
    color: var(--color-primary);
    margin-bottom: 24px;
    text-align: center;
}

.login-form .form-group {
    margin-bottom: 16px;
}

.login-form .form-group label {
    display: block;
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 4px;
    color: var(--color-text);
}

.login-form .form-control {
    width: 100%;
    padding: 10px 14px;
    font-family: var(--font-family);
    font-size: 15px;
    border: 2px solid var(--color-border);
    border-radius: 6px;
    transition: border-color 0.2s ease;
    background: var(--color-white);
    color: var(--color-text);
}

.login-form .form-control:focus {
    outline: none;
    border-color: var(--color-primary);
}

.btn-block {
    width: 100%;
    padding: 12px;
    font-size: 16px;
    font-weight: 600;
}

.login-links {
    text-align: center;
    margin-top: 16px;
    font-size: 14px;
}

.login-links a {
    color: var(--color-accent);
    text-decoration: none;
}

.login-links a:hover {
    text-decoration: underline;
}
</style>

<?php
require_once __DIR__ . '/layout/footer.php';