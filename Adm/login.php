<?php
/**
 * Страница входа в админ-панель
 */

require_once __DIR__ . '/../base/config.php';
require_once __DIR__ . '/classes.php';

// Если уже авторизован - редирект на дашборд
$auth = new AdminAuth();
if ($auth->isAdmin()) {
    header('Location: /Adm/');
    exit;
}

$error = '';
$debug = [];

// Обработка формы входа
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $debug['username'] = $username;
    $debug['password_length'] = strlen($password);
    
    if (empty($username) || empty($password)) {
        $error = 'Пожалуйста, заполните все поля';
    } else {
        // Прямая проверка в БД
        $db = Database::getInstance();
        $user = $db->fetchOne(
            "SELECT u.*, ug.group_name, ug.group_type 
             FROM users u 
             JOIN user_groups ug ON u.group_id = ug.id 
             WHERE u.username = ? AND u.is_active = 1",
            [$username]
        );
        
        if ($user) {
            $debug['user_found'] = '✅ Да';
            $debug['group_type'] = $user['group_type'];
            $debug['group_id'] = $user['group_id'];
            $debug['password_hash'] = $user['password_hash'];
            
            if (password_verify($password, $user['password_hash'])) {
                $debug['password_match'] = '✅ Да';
                
                if ($user['group_type'] === 'admin') {
                    $debug['is_admin'] = '✅ Да';
                    
                    // Выполняем вход через AdminAuth
                    if ($auth->adminLogin($username, $password)) {
                        $debug['login_success'] = '✅ Да';
                        header('Location: /Adm/');
                        exit;
                    } else {
                        $debug['login_success'] = '❌ Ошибка в AdminAuth';
                        $error = 'Ошибка входа в систему';
                    }
                } else {
                    $debug['is_admin'] = '❌ Нет (группа: ' . $user['group_type'] . ')';
                    $error = 'У вас нет прав администратора';
                }
            } else {
                $debug['password_match'] = '❌ Нет';
                $error = 'Неверный пароль';
            }
        } else {
            $debug['user_found'] = '❌ Нет';
            $error = 'Пользователь не найден';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= DEFAULT_LANGUAGE ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в админ-панель - <?= SITE_NAME ?></title>
    
    <link rel="stylesheet" href="/assets/css/grid.css">
    <link rel="stylesheet" href="/Adm/assets/css/style.css">
    
    <style>
        .login-page {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: var(--admin-bg-primary);
            padding: 20px;
        }
        
        .login-box {
            background: var(--admin-bg-card);
            border: 1px solid var(--admin-border);
            border-radius: 12px;
            padding: 40px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
        }
        
        .login-logo {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .login-logo .icon {
            font-size: 48px;
            display: block;
            margin-bottom: 8px;
        }
        
        .login-logo h1 {
            font-size: 24px;
            font-weight: 600;
            color: var(--admin-text-primary);
            margin: 0;
        }
        
        .login-logo p {
            color: var(--admin-text-muted);
            font-size: 14px;
            margin: 4px 0 0 0;
        }
        
        .login-error {
            background: rgba(239, 83, 80, 0.15);
            border: 1px solid var(--admin-danger);
            color: var(--admin-danger);
            padding: 10px 14px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 13px;
        }
        
        .login-form .form-group {
            margin-bottom: 20px;
        }
        
        .login-form label {
            display: block;
            font-weight: 600;
            color: var(--admin-text-secondary);
            font-size: 13px;
            margin-bottom: 4px;
        }
        
        .login-form input[type="text"],
        .login-form input[type="password"] {
            width: 100%;
            padding: 10px 14px;
            font-family: var(--font-family);
            font-size: 15px;
            border: 1px solid var(--admin-border);
            border-radius: 4px;
            background: var(--admin-bg-primary);
            color: var(--admin-text-primary);
            transition: border-color 0.2s ease;
        }
        
        .login-form input:focus {
            outline: none;
            border-color: var(--admin-accent);
        }
        
        .login-form .btn-login {
            width: 100%;
            padding: 12px;
            font-family: var(--font-family);
            font-size: 16px;
            font-weight: 600;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s ease;
            background: var(--admin-accent);
            color: var(--admin-bg-primary);
        }
        
        .login-form .btn-login:hover {
            background: var(--admin-accent-hover);
        }
        
        .login-footer {
            text-align: center;
            margin-top: 20px;
            color: var(--admin-text-muted);
            font-size: 13px;
        }
        
        .login-footer a {
            color: var(--admin-accent);
            text-decoration: none;
        }
        
        .login-footer a:hover {
            text-decoration: underline;
        }
        
        .debug-box {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--admin-border);
            border-radius: 4px;
            padding: 12px;
            margin-top: 16px;
            font-size: 12px;
            color: var(--admin-text-muted);
            font-family: monospace;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .debug-box .key {
            color: var(--admin-accent);
        }
        
        .debug-box .value {
            color: var(--admin-text-secondary);
        }
        
        .debug-box .success {
            color: var(--admin-success);
        }
        
        .debug-box .error {
            color: var(--admin-danger);
        }
        
        .debug-box .warn {
            color: var(--admin-warning);
        }
    </style>
</head>
<body>
    <div class="login-page">
        <div class="login-box">
			<div class="login-logo">
				<span class="login-icon">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 48px; height: 48px; stroke: var(--admin-accent);">
						<path d="M12 15a3 3 0 100-6 3 3 0 000 6z"/>
						<path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/>
					</svg>
				</span>
				<h1><?= SITE_NAME ?></h1>
				<p>Вход в админ-панель</p>
			</div>
            
            <?php if ($error): ?>
                <div class="login-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" class="login-form" autocomplete="off">
                <div class="form-group">
                    <label for="username">Имя пользователя</label>
                    <input type="text" id="username" name="username" placeholder="Введите имя пользователя" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">Пароль</label>
                    <input type="password" id="password" name="password" placeholder="Введите пароль" required>
                </div>
                
                <button type="submit" class="btn-login">Войти</button>
            </form>
            
            <div class="login-footer">
                <a href="/">Вернуться на сайт</a>
            </div>
            
            <?php if (!empty($debug) && isset($_POST['username'])): ?>
                <div class="debug-box">
                    <strong style="color: var(--admin-text-primary);">🔍 Отладка:</strong><br>
                    <?php foreach ($debug as $key => $value): ?>
                        <span class="key"><?= $key ?>:</span>
                        <span class="value"><?= htmlspecialchars($value) ?></span><br>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <div style="margin-top: 12px; font-size: 11px; color: var(--admin-text-muted); text-align: center;">
                Если не получается войти, запустите <a href="/install/clean_admin.php" style="color: var(--admin-warning);">очистку администраторов</a>
            </div>
        </div>
    </div>
</body>
</html>