<?php
/**
 * Модуль Groups - редактирование группы
 * Супер-админ скрыт из списка, системные группы защищены
 */

// Проверяем права доступа — только Супер-админ
if (!$auth->canManageGroups()) {
    echo '<div class="admin-alert admin-alert-danger">У вас нет прав для редактирования групп. Доступ только у Супер-админа.</div>';
    return;
}

$db = Database::getInstance();
$groupId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($groupId <= 0) {
    echo '<div class="admin-alert admin-alert-danger">Не указан ID группы.</div>';
    return;
}

$group = $db->fetchOne(
    "SELECT g.*, 
            (SELECT COUNT(*) FROM users u WHERE u.group_id = g.id) as user_count
     FROM user_groups g 
     WHERE g.id = ? AND g.group_name != 'Супер-админ'",
    [$groupId]
);

if (!$group) {
    echo '<div class="admin-alert admin-alert-danger">Группа не найдена.</div>';
    return;
}

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
        $exists = $db->fetchOne("SELECT id FROM user_groups WHERE group_name = ? AND id != ?", [$groupName, $groupId]);
        if ($exists) {
            $error = 'Группа с таким названием уже существует';
        } else {
            $db->update('user_groups', [
                'group_name' => $groupName,
                'group_type' => $groupType
            ], 'id = ?', [$groupId]);
            
            $user = $auth->getUser();
            $db->insert('audit_log', [
                'user_id' => $user['id'],
                'action' => 'group_edit',
                'module' => 'groups',
                'description' => "Отредактирована группа {$groupName} (ID: {$groupId})",
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $success = "Группа {$groupName} успешно обновлена!";
            $group['group_name'] = $groupName;
            $group['group_type'] = $groupType;
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
        <h2>✏️ Редактирование группы</h2>
        <a href="?route=groups" class="btn-admin btn-admin-secondary">← Назад к списку</a>
    </div>
    
    <?php if ($error): ?>
        <div class="admin-alert admin-alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="admin-alert admin-alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    
    <?php if ($group['is_system']): ?>
        <div class="admin-alert admin-alert-warning">
            ⚠️ Это системная группа. Вы можете изменить название, но не можете изменить тип или удалить группу.
        </div>
    <?php endif; ?>
    
    <div class="admin-card">
        <form method="POST" class="admin-form">
            <div class="row">
                <div class="col-xs-12 col-md-8">
                    <div class="admin-form-group">
                        <label for="group_name">Название группы *</label>
                        <input type="text" id="group_name" name="group_name" class="admin-form-control" 
                               value="<?= htmlspecialchars($group['group_name']) ?>" required>
                    </div>
                </div>
                
                <div class="col-xs-12 col-md-4">
                    <div class="admin-form-group">
                        <label for="group_type">Тип группы *</label>
                        <select id="group_type" name="group_type" class="admin-form-control" 
                                <?= $group['is_system'] ? 'disabled' : '' ?>>
                            <option value="frontend" <?= $group['group_type'] === 'frontend' ? 'selected' : '' ?>>
                                👤 Пользователь фронтенда (доступ только к сайту)
                            </option>
                            <option value="admin" <?= $group['group_type'] === 'admin' ? 'selected' : '' ?>>
                                🛡️ Администратор (управление контентом, без прав на пользователей и группы)
                            </option>
                        </select>
                        <?php if ($group['is_system']): ?>
                            <input type="hidden" name="group_type" value="<?= $group['group_type'] ?>">
                            <small style="color: var(--admin-text-muted);">Тип системной группы нельзя изменить</small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="admin-form-group">
                <label>
                    <span style="color: var(--admin-text-muted);">Пользователей в группе: </span>
                    <strong><?= $group['user_count'] ?></strong>
                </label>
            </div>
            
            <div class="admin-form-group">
                <button type="submit" class="btn-admin btn-admin-success">Сохранить</button>
                <a href="?route=groups" class="btn-admin btn-admin-secondary">Отмена</a>
            </div>
        </form>
    </div>
</div>