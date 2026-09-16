<?php
/**
 * Модуль Groups - создание группы
 * Доступны только: Администратор, Пользователь фронтенда
 */

// Проверяем права доступа — только Супер-админ
if (!$auth->canManageGroups()) {
    echo '<div class="admin-alert admin-alert-danger">У вас нет прав для создания групп. Доступ только у Супер-админа.</div>';
    return;
}

$db = Database::getInstance();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $groupName = trim($_POST['group_name'] ?? '');
    $groupType = $_POST['group_type'] ?? 'frontend';
    
    if (empty($groupName)) {
        $error = 'Название группы обязательно';
    } elseif (strlen($groupName) < 2) {
        $error = 'Название группы должно содержать минимум 2 символа';
    } else {
        $exists = $db->fetchOne("SELECT id FROM user_groups WHERE group_name = ?", [$groupName]);
        if ($exists) {
            $error = 'Группа с таким названием уже существует';
        } else {
            $db->insert('user_groups', [
                'group_name' => $groupName,
                'group_type' => $groupType,
                'is_system' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $groupId = $db->lastInsertId();
            
            $user = $auth->getUser();
            $db->insert('audit_log', [
                'user_id' => $user['id'],
                'action' => 'group_create',
                'module' => 'groups',
                'description' => "Создана группа {$groupName} (ID: {$groupId})",
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $success = "Группа {$groupName} успешно создана!";
            $_POST = [];
        }
    }
}
?>

<style>
.module-groups .module-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 12px;
}

.module-groups .module-header h2 {
    font-size: 22px;
    font-weight: 600;
    color: var(--admin-text-primary);
    margin: 0;
}

.admin-form .row {
    margin-bottom: 8px;
}

@media (max-width: 768px) {
    .module-groups .module-header {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
    }
}
</style>

<div class="module-groups">
    <div class="module-header">
        <h2>➕ Создание группы</h2>
        <a href="?route=groups" class="btn-admin btn-admin-secondary">← Назад к списку</a>
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
                <div class="col-xs-12 col-md-8">
                    <div class="admin-form-group">
                        <label for="group_name">Название группы *</label>
                        <input type="text" id="group_name" name="group_name" class="admin-form-control" 
                               value="<?= htmlspecialchars($_POST['group_name'] ?? '') ?>" 
                               placeholder="Например: Модераторы" required>
                    </div>
                </div>
                
                <div class="col-xs-12 col-md-4">
                    <div class="admin-form-group">
                        <label for="group_type">Тип группы *</label>
                        <select id="group_type" name="group_type" class="admin-form-control" required>
                            <option value="frontend" <?= ($_POST['group_type'] ?? 'frontend') === 'frontend' ? 'selected' : '' ?>>
                                👤 Пользователь фронтенда (доступ только к сайту)
                            </option>
                            <option value="admin" <?= ($_POST['group_type'] ?? '') === 'admin' ? 'selected' : '' ?>>
                                🛡️ Администратор (управление контентом, без прав на пользователей и группы)
                            </option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="admin-form-group">
                <button type="submit" class="btn-admin btn-admin-success">Создать группу</button>
                <a href="?route=groups" class="btn-admin btn-admin-secondary">Отмена</a>
            </div>
        </form>
    </div>
</div>