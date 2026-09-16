<?php
/**
 * Личный кабинет пользователя
 */

require_once __DIR__ . '/base/config.php';
require_once __DIR__ . '/classes.php';

$auth = new Auth();

// Если не залогинен — редирект на вход
if (!$auth->isLoggedIn()) {
    header('Location: /login.php');
    exit;
}

$user = $auth->getUser();

require_once __DIR__ . '/layout/header.php';
?>

<div class="profile-page">
    <h1>Личный кабинет</h1>
    
    <div class="profile-card">
        <div class="profile-avatar">
            <?= strtoupper(substr($user['username'], 0, 1)) ?>
        </div>
        
        <div class="profile-info">
            <p><strong>Имя пользователя:</strong> <?= htmlspecialchars($user['username']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
            <p><strong>Группа:</strong> <?= htmlspecialchars($user['group_name']) ?></p>
            <p><strong>Дата регистрации:</strong> <?= date('d.m.Y H:i', strtotime($user['created_at'])) ?></p>
        </div>
        
        <a href="/logout.php" class="btn btn-danger">Выйти</a>

		<?php if ($user['group_type'] === 'admin'): ?>
    		<a href="/Adm/" class="btn btn-primary" style="display: inline-block; margin-top: 10px;">Перейти в админ-панель</a>
		<?php endif; ?>
    </div>
</div>

<style>
.profile-page {
    max-width: 600px;
    margin: 40px auto;
    padding: 0 20px;
}

.profile-page h1 {
    font-size: 28px;
    font-weight: 600;
    color: var(--color-primary);
    margin-bottom: 24px;
}

.profile-card {
    background: var(--color-white);
    border: 1px solid var(--color-border);
    border-radius: 12px;
    padding: 40px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    text-align: center;
}

.profile-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: var(--color-primary);
    color: var(--color-white);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    font-weight: 600;
    margin: 0 auto 20px;
}

.profile-info {
    text-align: left;
    margin-bottom: 24px;
}

.profile-info p {
    padding: 8px 0;
    border-bottom: 1px solid var(--color-border);
}

.profile-info p:last-child {
    border-bottom: none;
}

.profile-info strong {
    display: inline-block;
    min-width: 150px;
    color: var(--color-text-light);
}
</style>

<?php
require_once __DIR__ . '/layout/footer.php';