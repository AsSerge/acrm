<?php
/**
 * Модуль Users - создание пользователя
 */

// Проверяем права доступа — только Супер-админ
if (!$auth->canManageUsers()) {
    echo '<div class="admin-alert admin-alert-danger">У вас нет прав для создания пользователей. Доступ только у Супер-админа.</div>';
    return;
}

$db = Database::getInstance();
$error = '';
$success = '';

// Получаем группы для выбора
$groups = $db->fetchAll("SELECT id, group_name, group_type FROM user_groups ORDER BY group_type, group_name");

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $groupId = (int)($_POST['group_id'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($username)) {
        $error = 'Имя пользователя обязательно';
    } elseif (strlen($username) < 3) {
        $error = 'Имя пользователя должно содержать минимум 3 символа';
    } elseif (empty($email)) {
        $error = 'Email обязателен';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Введите корректный email';
    } elseif (empty($password)) {
        $error = 'Пароль обязателен';
    } elseif (strlen($password) < 6) {
        $error = 'Пароль должен содержать минимум 6 символов';
    } elseif ($groupId <= 0) {
        $error = 'Выберите группу';
    } else {
        $exists = $db->fetchOne("SELECT id FROM users WHERE username = ? OR email = ?", [$username, $email]);
        if ($exists) {
            $error = 'Пользователь с таким именем или email уже существует';
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            $db->insert('users', [
                'username' => $username,
                'email' => $email,
                'password_hash' => $passwordHash,
                'group_id' => $groupId,
                'is_active' => $isActive,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $userId = $db->lastInsertId();
            
            $user = $auth->getUser();
            $db->insert('audit_log', [
                'user_id' => $user['id'],
                'action' => 'user_create',
                'module' => 'users',
                'description' => "Создан пользователь {$username} (ID: {$userId})",
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $success = "Пользователь {$username} успешно создан!";
            $_POST = [];
        }
    }
}
?>

<style>
.module-users .module-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 12px;
}

.module-users .module-header h2 {
    font-size: 22px;
    font-weight: 600;
    color: var(--admin-text-primary);
    margin: 0;
}

.admin-form .row {
    margin-bottom: 8px;
}

@media (max-width: 768px) {
    .module-users .module-header {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
    }
}
</style>

<div class="module-users">
    <div class="module-header">
        <h2>➕ Создание пользователя</h2>
        <a href="?route=users" class="btn-admin btn-admin-secondary">← Назад к списку</a>
    </div>
    
    <?php if ($error): ?>
        <div class="admin-alert admin-alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="admin-alert admin-alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    
    <div class="admin-card">
        <form method="POST" class="admin-form">
            <div class="row">
                <div class="col-xs-12 col-md-6">
                    <div class="admin-form-group">
                        <label for="username">Имя пользователя *</label>
                        <input type="text" id="username" name="username" class="admin-form-control" 
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                    </div>
                </div>
                
                <div class="col-xs-12 col-md-6">
                    <div class="admin-form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" class="admin-form-control" 
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-xs-12 col-md-6">
                    <div class="admin-form-group">
                        <label for="password">Пароль *</label>
                        <input type="password" id="password" name="password" class="admin-form-control" required>
                        <small style="color: var(--admin-text-muted);">Минимум 6 символов</small>
                    </div>
                </div>
                
                <div class="col-xs-12 col-md-6">
                    <div class="admin-form-group">
                        <label for="group_id">Группа *</label>
                        <select id="group_id" name="group_id" class="admin-form-control" required>
                            <option value="">Выберите группу</option>
                            <?php foreach ($groups as $group): ?>
                                <option value="<?= $group['id'] ?>" <?= ($_POST['group_id'] ?? '') == $group['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($group['group_name']) ?> (<?= $group['group_type'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
			<div class="admin-form-group">
				<label class="admin-checkbox">
					<input type="checkbox" name="is_active" value="1" <?= isset($_POST['is_active']) ? 'checked' : 'checked' ?>>
					<span class="checkmark"></span>
					Активен
				</label>
			</div>
            
            <div class="admin-form-group">
                <button type="submit" class="btn-admin btn-admin-success">Создать пользователя</button>
                <a href="?route=users" class="btn-admin btn-admin-secondary">Отмена</a>
            </div>
        </form>
    </div>
</div>