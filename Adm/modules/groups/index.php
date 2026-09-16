<?php
/**
 * Модуль Groups - список групп
 */

if (!$auth->canManageGroups()) {
    echo '<div class="admin-alert admin-alert-danger">У вас нет прав для просмотра этого раздела. Доступ только у Супер-админа.</div>';
    return;
}

$db = Database::getInstance();

$groups = $db->fetchAll("
    SELECT g.*, (SELECT COUNT(*) FROM users u WHERE u.group_id = g.id) as user_count
    FROM user_groups g
    WHERE g.group_name != 'Супер-админ'
    ORDER BY g.group_type, g.group_name
");
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
.group-type-badge {
    display: inline-block;
    padding: 2px 10px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 600;
}
.group-type-admin {
    background: rgba(100, 181, 246, 0.2);
    color: var(--admin-accent);
}
.group-type-frontend {
    background: rgba(255, 167, 38, 0.2);
    color: var(--admin-warning);
}
.group-system-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 10px;
    background: rgba(76, 175, 80, 0.2);
    color: var(--admin-success);
    margin-left: 6px;
}
.action-buttons {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}
@media (max-width: 768px) {
    .module-groups .module-header {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
    }
    .module-groups .module-header .btn-admin {
        text-align: center;
    }
    .table-responsive {
        overflow-x: auto;
    }
}
</style>

<div class="module-groups">
    <div class="module-header">
        <h2>
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 8px;">
                <path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            Управление группами
        </h2>
        <a href="?route=groups&action=create" class="btn-admin btn-admin-primary">
            <svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"/>
                <line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Добавить группу
        </a>
    </div>
    
    <div class="admin-card">
        <div class="admin-card-header">
            <h3>Все группы (<?= count($groups) ?>)</h3>
        </div>
        <?php if (empty($groups)): ?>
            <p style="color: var(--admin-text-muted); text-align: center; padding: 30px 0;">Группы не найдены</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Название группы</th>
                            <th>Тип</th>
                            <th>Пользователей</th>
                            <th>Системная</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($groups as $group): ?>
                            <tr>
                                <td><?= $group['id'] ?></td>
                                <td><strong><?= htmlspecialchars($group['group_name']) ?></strong></td>
                                <td>
                                    <span class="group-type-badge group-type-<?= $group['group_type'] ?>">
                                        <?php if ($group['group_type'] === 'admin'): ?>
                                            <svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 2px;">
                                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                                <path d="M12 8v4"/>
                                                <path d="M12 16h.01"/>
                                            </svg>
                                            Администратор
                                        <?php else: ?>
                                            <svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 2px;">
                                                <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                                                <circle cx="12" cy="7" r="4"/>
                                            </svg>
                                            Пользователь
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td><?= $group['user_count'] ?></td>
                                <td>
                                    <?php if ($group['is_system']): ?>
                                        <span class="group-system-badge">
                                            <svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 2px;">
                                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                                <path d="M7 11V7a5 5 0 0110 0v4"/>
                                            </svg>
                                            Системная
                                        </span>
                                    <?php else: ?>
                                        <span style="color: var(--admin-text-muted); font-size: 12px;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="?route=groups&action=edit&id=<?= $group['id'] ?>" class="btn-admin-circle btn-admin-circle-primary" title="Редактировать">
                                            <svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                                                <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                            </svg>
                                        </a>
                                        <?php if (!$group['is_system'] && $group['user_count'] == 0): ?>
                                            <button class="btn-admin-circle btn-admin-circle-danger" data-delete-group data-id="<?= $group['id'] ?>" data-name="<?= htmlspecialchars($group['group_name']) ?>" title="Удалить">
                                                <svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="3 6 5 6 21 6"/>
                                                    <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                                                    <line x1="10" y1="11" x2="10" y2="17"/>
                                                    <line x1="14" y1="11" x2="14" y2="17"/>
                                                </svg>
                                            </button>
                                        <?php elseif ($group['is_system']): ?>
                                            <span style="color: var(--admin-text-muted); font-size: 12px;">Нельзя удалить</span>
                                        <?php else: ?>
                                            <span style="color: var(--admin-text-muted); font-size: 12px;">Есть пользователи</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
$(document).ready(function() {
    $(document).on('click', '[data-delete-group]', function(e) {
        e.preventDefault();
        var groupId = $(this).data('id');
        var groupName = $(this).data('name');
        if (!confirm('Вы уверены, что хотите удалить группу "' + groupName + '"?\nЭто действие нельзя отменить!')) return;
        $.ajax({
            url: '/Adm/api/groups.api.php',
            type: 'POST',
            data: { action: 'delete', id: groupId },
            dataType: 'json',
            success: function(response) {
                if (response.success) { showAdminNotification(response.message, 'success'); setTimeout(function() { location.reload(); }, 1000); } 
                else { showAdminNotification(response.message, 'danger'); }
            },
            error: function() { showAdminNotification('Ошибка выполнения запроса', 'danger'); }
        });
    });
    console.log('Groups module loaded');
});
</script>