<?php
/**
 * Модуль Users - список пользователей
 */

if (!$auth->canManageUsers()) {
    echo '<div class="admin-alert admin-alert-danger">У вас нет прав для просмотра этого раздела. Доступ только у Супер-админа.</div>';
    return;
}

$db = Database::getInstance();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$groupFilter = isset($_GET['group']) ? (int)$_GET['group'] : 0;
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

$whereConditions = [];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(u.username LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($groupFilter > 0) {
    $whereConditions[] = "u.group_id = ?";
    $params[] = $groupFilter;
}

if ($statusFilter === 'active') {
    $whereConditions[] = "u.is_active = 1";
} elseif ($statusFilter === 'inactive') {
    $whereConditions[] = "u.is_active = 0";
}

$whereSQL = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

$sql = "
    SELECT u.*, ug.group_name, ug.group_type 
    FROM users u
    JOIN user_groups ug ON u.group_id = ug.id
    $whereSQL
    ORDER BY u.id DESC
    LIMIT ? OFFSET ?
";
$params[] = $perPage;
$params[] = $offset;
$users = $db->fetchAll($sql, $params);

$countSQL = "
    SELECT COUNT(*) as total
    FROM users u
    JOIN user_groups ug ON u.group_id = ug.id
    $whereSQL
";
$countParams = array_slice($params, 0, -2);
$total = $db->fetchOne($countSQL, $countParams)['total'] ?? 0;
$totalPages = ceil($total / $perPage);

$groups = $db->fetchAll("SELECT id, group_name, group_type FROM user_groups ORDER BY group_type, group_name");
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
.filters-bar {
    background: var(--admin-bg-card);
    border: 1px solid var(--admin-border);
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 20px;
}
.filters-form {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: flex-end;
}
.filter-group {
    flex: 1;
    min-width: 150px;
}
.filter-group .admin-form-control {
    width: 100%;
    height: 40px;
    box-sizing: border-box;
}
.filter-actions {
    display: flex;
    gap: 8px;
    align-items: stretch;
    flex-shrink: 0;
}
.btn-filter {
    height: 40px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 20px;
    font-size: 14px;
    line-height: 1;
    min-width: 80px;
    box-sizing: border-box;
}
.action-buttons {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}
.status-active {
    color: var(--admin-success);
}
.status-inactive {
    color: var(--admin-danger);
}
.status-active .icon,
.status-inactive .icon {
    vertical-align: middle;
    margin-right: 4px;
}
.btn-admin-warning {
    background: var(--admin-warning);
    color: #fff;
}
.btn-admin-warning:hover {
    opacity: 0.85;
}
.filter-loading {
    display: none;
    font-size: 13px;
    color: var(--admin-text-muted);
    animation: pulse 1s ease-in-out infinite;
}
.filter-loading.active {
    display: inline-block;
}
@keyframes pulse {
    0%, 100% { opacity: 0.5; }
    50% { opacity: 1; }
}
@media (max-width: 768px) {
    .module-users .module-header {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
    }
    .module-users .module-header .btn-admin {
        text-align: center;
    }
    .filters-form {
        flex-direction: column;
    }
    .filter-group {
        min-width: 100%;
    }
    .filter-actions {
        width: 100%;
    }
    .btn-filter {
        flex: 1;
    }
    .table-responsive {
        overflow-x: auto;
    }
}
</style>

<div class="module-users">
    <div class="module-header">
        <h2>
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 8px;">
                <path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            Управление пользователями
        </h2>
        <a href="?route=users&action=create" class="btn-admin btn-admin-primary">		
            <svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"/>
                <line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Добавить пользователя
        </a>
    </div>
    
    <div class="filters-bar">
        <form method="GET" class="filters-form" id="filterForm" autocomplete="off">
            <input type="hidden" name="route" value="users">
            <div class="filter-group">
                <div style="position: relative;">
                    <svg class="icon" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); stroke: var(--admin-text-muted);" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <input type="text" name="search" placeholder="Поиск по имени или email..." 
                           value="<?= htmlspecialchars($search) ?>" class="admin-form-control" id="searchInput" style="padding-left: 36px;">
                </div>
            </div>
            <div class="filter-group">
                <select name="group" class="admin-form-control" id="groupSelect">
                    <option value="0">Все группы</option>
                    <?php foreach ($groups as $group): ?>
                        <option value="<?= $group['id'] ?>" <?= $groupFilter == $group['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($group['group_name']) ?> (<?= $group['group_type'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <select name="status" class="admin-form-control" id="statusSelect">
                    <option value="">Все статусы</option>
                    <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Активные</option>
                    <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Заблокированные</option>
                </select>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn-admin btn-admin-primary btn-filter">
                    <svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="22 3 2 3 10 13 10 21 14 18 14 13 22 3"/>
                    </svg>
                    Фильтр
                </button>
                <a href="?route=users" class="btn-admin btn-admin-secondary btn-filter">Сбросить</a>
            </div>
        </form>
    </div>
    
    <div class="admin-card" id="usersTable">
        <div class="admin-card-header">
            <h3>Все пользователи (<span id="totalCount"><?= $total ?></span>)</h3>
        </div>
        <?php if (empty($users)): ?>
            <p style="color: var(--admin-text-muted); text-align: center; padding: 30px 0;">Пользователи не найдены</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Имя пользователя</th>
                            <th>Email</th>
                            <th>Группа</th>
                            <th>Тип</th>
                            <th>Статус</th>
                            <th>Последний вход</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody id="usersBody">
                        <?php foreach ($users as $user): ?>
                            <tr data-user-id="<?= $user['id'] ?>">
                                <td><?= $user['id'] ?></td>
                                <td><strong><?= htmlspecialchars($user['username']) ?></strong></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= htmlspecialchars($user['group_name']) ?></td>
                                <td>
                                    <span style="font-size: 11px; padding: 2px 8px; border-radius: 10px; background: <?= $user['group_type'] === 'admin' ? 'rgba(100,181,246,0.2)' : 'rgba(255,167,38,0.2)' ?>; color: <?= $user['group_type'] === 'admin' ? 'var(--admin-accent)' : 'var(--admin-warning)' ?>;">
                                        <?= $user['group_type'] ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-<?= $user['is_active'] ? 'active' : 'inactive' ?>">
                                        <?php if ($user['is_active']): ?>
                                            <svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="stroke: var(--admin-success);">
                                                <path d="M20 6L9 17l-5-5"/>
                                            </svg>
                                            Активен
                                        <?php else: ?>
                                            <svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="stroke: var(--admin-danger);">
                                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                                <path d="M7 11V7a5 5 0 0110 0v4"/>
                                            </svg>
                                            Заблокирован
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td><?= $user['last_login'] ? date('d.m.Y H:i', strtotime($user['last_login'])) : '—' ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="?route=users&action=edit&id=<?= $user['id'] ?>" class="btn-admin-circle btn-admin-circle-primary" title="Редактировать">
                                            <svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                                                <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                            </svg>
                                        </a>                                        
										<button class="btn-admin-circle btn-admin-circle-<?= $user['is_active'] ? 'warning' : 'success' ?>"  
                                                data-toggle-user
                                                data-id="<?= $user['id'] ?>"
                                                data-status="<?= $user['is_active'] ?>"
                                                title="<?= $user['is_active'] ? 'Заблокировать' : 'Разблокировать' ?>">
                                            <svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <?php if ($user['is_active']): ?>
                                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                                    <path d="M7 11V7a5 5 0 0110 0v4"/>
                                                <?php else: ?>
                                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                                    <path d="M7 11V7a5 5 0 019.9-1"/>
                                                <?php endif; ?>
                                            </svg>
                                        </button>
                                        <button class="btn-admin-circle btn-admin-circle-danger" 
                                                data-delete-user
                                                data-id="<?= $user['id'] ?>"
                                                data-username="<?= htmlspecialchars($user['username']) ?>"
                                                title="Удалить">
                                            <svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="3 6 5 6 21 6"/>
                                                <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                                                <line x1="10" y1="11" x2="10" y2="17"/>
                                                <line x1="14" y1="11" x2="14" y2="17"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($totalPages > 1): ?>
                <div class="pagination" id="pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?route=users&page=<?= $i ?>&search=<?= urlencode($search) ?>&group=<?= $groupFilter ?>&status=<?= $statusFilter ?>" class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
$(document).ready(function() {
    var $searchInput = $('#searchInput');
    var $groupSelect = $('#groupSelect');
    var $statusSelect = $('#statusSelect');
    var $loading = $('#filterLoading');
    var $usersBody = $('#usersBody');
    var $totalCount = $('#totalCount');
    var $pagination = $('#pagination');
    var filterTimeout = null;
    
    function applyFilters() {
        $loading.addClass('active');
        var params = { route: 'users', search: $searchInput.val(), group: $groupSelect.val(), status: $statusSelect.val() };
        $.ajax({
            url: '/Adm/api/users.api.php',
            type: 'GET',
            data: $.extend({ action: 'filter' }, params),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $usersBody.html(response.html);
                    $totalCount.text(response.total);
                    $pagination.html(response.pagination || '');
                } else {
                    showAdminNotification(response.message || 'Ошибка фильтрации', 'danger');
                }
            },
            error: function() {
                showAdminNotification('Ошибка соединения с сервером', 'danger');
            },
            complete: function() {
                $loading.removeClass('active');
            }
        });
    }
    
    $searchInput.on('input', function() {
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(applyFilters, 400);
    });
    $groupSelect.on('change', applyFilters);
    $statusSelect.on('change', applyFilters);
    
    $(document).on('click', '[data-delete-user]', function(e) {
        e.preventDefault();
        var userId = $(this).data('id');
        var username = $(this).data('username');
        if (!confirm('Вы уверены, что хотите удалить пользователя "' + username + '"? Это действие нельзя отменить!')) return;
        $.ajax({
            url: '/Adm/api/users.api.php',
            type: 'POST',
            data: { action: 'delete', id: userId },
            dataType: 'json',
            success: function(response) {
                if (response.success) { showAdminNotification(response.message, 'success'); applyFilters(); } 
                else { showAdminNotification(response.message, 'danger'); }
            },
            error: function() { showAdminNotification('Ошибка выполнения запроса', 'danger'); }
        });
    });
    
    $(document).on('click', '[data-toggle-user]', function(e) {
        e.preventDefault();
        var userId = $(this).data('id');
        var currentStatus = $(this).data('status');
        var newStatus = currentStatus ? 0 : 1;
        var statusText = newStatus ? 'активировать' : 'заблокировать';
        if (!confirm('Вы уверены, что хотите ' + statusText + ' этого пользователя?')) return;
        $.ajax({
            url: '/Adm/api/users.api.php',
            type: 'POST',
            data: { action: 'toggle', id: userId, status: newStatus },
            dataType: 'json',
            success: function(response) {
                if (response.success) { showAdminNotification(response.message, 'success'); applyFilters(); } 
                else { showAdminNotification(response.message, 'danger'); }
            },
            error: function() { showAdminNotification('Ошибка выполнения запроса', 'danger'); }
        });
    });
    
    console.log('Users module loaded');
});
</script>