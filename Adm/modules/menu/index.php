<?php
/**
 * Модуль Menu - список пунктов меню (дерево)
 */

if (!$auth->hasAdminAccess('menu', 'view')) {
    echo '<div class="admin-alert admin-alert-danger">У вас нет прав для просмотра этого раздела.</div>';
    return;
}

$db = Database::getInstance();
$menuBuilder = new MenuBuilder();
$moduleManager = new ModuleManager();

// Получаем дерево меню
$menuTree = $menuBuilder->getTree('frontend', false, null, true);

// Получаем статистику
$totalItems = $db->fetchOne("SELECT COUNT(*) as count FROM menu_structure")['count'] ?? 0;
$activeItems = $db->fetchOne("SELECT COUNT(*) as count FROM menu_structure WHERE is_active = 1")['count'] ?? 0;

// Получаем модули для отображения названий
$allModules = $moduleManager->getAllModules();
$moduleTitles = [];
foreach ($allModules as $name => $m) {
    $moduleTitles[$name] = $m['title'] ?: $name;
}

// Функция для получения названий групп по ID
function getGroupNames($accessGroups, $db) {
    if ($accessGroups === null || $accessGroups === '') {
        return null;
    }
    $groupIds = json_decode($accessGroups, true);
    if (!is_array($groupIds) || empty($groupIds)) {
        return null;
    }
    $placeholders = implode(',', array_fill(0, count($groupIds), '?'));
    $groups = $db->fetchAll(
        "SELECT group_name FROM user_groups WHERE id IN ($placeholders)",
        $groupIds
    );
    return implode(', ', array_column($groups, 'group_name'));
}

/**
 * Рекурсивная функция для отображения дерева меню
 */
/**
 * Рекурсивная функция для отображения дерева меню
 */
function renderMenuTree($items, $depth = 0, $db = null, $moduleTitles = []) {
    if (empty($items)) return;
    
    $padding = $depth * 30;
    foreach ($items as $item):
        $linkType = $item['link_type'] ?? 'module';
        $moduleLink = $item['module_link'];
        
        // Определяем, куда ведёт ссылка
        if ($linkType === 'module') {
            $moduleName = trim($moduleLink, '/');
            $moduleName = str_replace('modules/', '', $moduleName);
            $moduleName = rtrim($moduleName, '/');
            
            if (isset($moduleTitles[$moduleName])) {
                $linkDisplay = '/' . $moduleName;
                $linkTitle = $moduleTitles[$moduleName];
            } else {
                $linkDisplay = $moduleLink;
                $linkTitle = 'Неизвестный модуль';
            }
        } elseif ($linkType === 'external') {
            $linkDisplay = $moduleLink;
            $linkTitle = '';
        } else {
            $linkDisplay = '';
            $linkTitle = '';
        }
        ?>
        <tr data-id="<?= $item['id'] ?>" data-parent="<?= $item['parent_id'] ?: 0 ?>" class="sortable-row">
            <td>
                <div style="display: flex; align-items: center; gap: 8px; padding-left: <?= $padding ?>px;">
                    <span class="drag-handle" style="cursor: grab; color: var(--admin-text-muted); opacity: 0.3; font-size: 16px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="9" cy="12" r="1"/>
                            <circle cx="9" cy="5" r="1"/>
                            <circle cx="9" cy="19" r="1"/>
                            <circle cx="15" cy="12" r="1"/>
                            <circle cx="15" cy="5" r="1"/>
                            <circle cx="15" cy="19" r="1"/>
                        </svg>
                    </span>
                    <?php if (isset($item['children']) && !empty($item['children'])): ?>
                        <span class="menu-toggle" data-id="<?= $item['id'] ?>">▼</span>
                    <?php else: ?>
                        <span style="width: 20px; display: inline-block;"></span>
                    <?php endif; ?>
                    <strong><?= htmlspecialchars($item['menu_title']) ?></strong>
                </div>
            </td>
            <td>
                <?php if ($linkType === 'external'): ?>
                    <span class="link-type-badge link-type-external">
                        <svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="stroke: currentColor; vertical-align: middle;">
                            <path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/>
                            <polyline points="15 3 21 3 21 9"/>
                            <line x1="10" y1="14" x2="21" y2="3"/>
                        </svg>
                    </span>
                    <code style="background: var(--admin-bg-hover); padding: 2px 8px; border-radius: 4px; font-size: 12px; color: var(--admin-text-secondary);">
                        <?= htmlspecialchars($linkDisplay) ?>
                    </code>
                <?php elseif ($linkType === 'none'): ?>
                    <span class="link-type-badge link-type-none">
                        <svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="stroke: var(--admin-text-muted); vertical-align: middle;">
                            <path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/>
                        </svg>
                    </span>
                    <span style="color: var(--admin-text-muted); font-size: 13px;">Группировка</span>
                <?php else: ?>
                    <span class="link-type-badge link-type-module">
                        <svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="stroke: var(--admin-accent); vertical-align: middle;">
                            <path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/>
                        </svg>
                    </span>
                    <code style="background: var(--admin-bg-hover); padding: 2px 8px; border-radius: 4px; font-size: 12px; color: var(--admin-text-secondary);">
                        <?= htmlspecialchars($linkDisplay) ?>
                    </code>
                    <br>
                    <small style="color: var(--admin-text-muted); font-size: 11px;">
                        <?= htmlspecialchars($linkTitle) ?>
                    </small>
                <?php endif; ?>
            </td>
            <td>
                <?php if ($item['is_active']): ?>
                    <span class="status-active">
                        <svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="stroke: var(--admin-success); vertical-align: middle;">
                            <path d="M20 6L9 17l-5-5"/>
                        </svg>
                        Активен
                    </span>
                <?php else: ?>
                    <span class="status-inactive">
                        <svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="stroke: var(--admin-danger); vertical-align: middle;">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                        Скрыт
                    </span>
                <?php endif; ?>
            </td>
            <td>
                <?php
                $accessGroups = $item['access_groups'];
                if ($accessGroups === null || $accessGroups === '') {
                    echo '<span class="access-badge access-public">';
                    echo '<svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="stroke: currentColor; vertical-align: middle;">';
                    echo '<circle cx="12" cy="12" r="10"/>';
                    echo '<line x1="2" y1="12" x2="22" y2="12"/>';
                    echo '</svg> Все';
                    echo '</span>';
                } else {
                    $groupNames = getGroupNames($accessGroups, $db);
                    echo '<span class="access-badge access-restricted" title="' . htmlspecialchars($groupNames ?: 'Ограничен') . '">';
                    echo '<svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="stroke: currentColor; vertical-align: middle;">';
                    echo '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>';
                    echo '<path d="M7 11V7a5 5 0 0110 0v4"/>';
                    echo '</svg> ' . htmlspecialchars($groupNames ?: 'Ограничен');
                    echo '</span>';
                }
                ?>
            </td>
            <td>
                <div class="action-buttons">
                    <a href="?route=menu&action=edit&id=<?= $item['id'] ?>" class="btn-admin-circle btn-admin-circle-primary" title="Редактировать">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                    </a>
                    <button class="btn-admin-circle btn-admin-circle-<?= $item['is_active'] ? 'warning' : 'success' ?>" 
                            data-toggle-menu
                            data-id="<?= $item['id'] ?>"
                            data-status="<?= $item['is_active'] ?>"
                            title="<?= $item['is_active'] ? 'Скрыть' : 'Показать' ?>">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <?php if ($item['is_active']): ?>
                                <path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/>
                                <line x1="1" y1="1" x2="23" y2="23"/>
                            <?php else: ?>
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            <?php endif; ?>
                        </svg>
                    </button>
                    <?php if (empty($item['children'])): ?>
                        <button class="btn-admin-circle btn-admin-circle-danger" 
                                data-delete-menu
                                data-id="<?= $item['id'] ?>"
                                data-title="<?= htmlspecialchars($item['menu_title']) ?>"
                                title="Удалить">
                            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="3 6 5 6 21 6"/>
                                <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                                <line x1="10" y1="11" x2="10" y2="17"/>
                                <line x1="14" y1="11" x2="14" y2="17"/>
                            </svg>
                        </button>
                    <?php else: ?>
                        <span style="color: var(--admin-text-muted); font-size: 12px;">Есть дочерние</span>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php if (isset($item['children']) && !empty($item['children'])): ?>
            <?php renderMenuTree($item['children'], $depth + 1, $db, $moduleTitles); ?>
        <?php endif; ?>
    <?php endforeach;
}
?>

<style>
.module-menu .module-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 12px;
}
.module-menu .module-header h2 {
    font-size: 22px;
    font-weight: 600;
    color: var(--admin-text-primary);
    margin: 0;
}
.menu-stats {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}
.menu-stats .stat-item {
    background: var(--admin-bg-card);
    border: 1px solid var(--admin-border);
    border-radius: 8px;
    padding: 12px 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.menu-stats .stat-item .stat-number {
    font-size: 20px;
    font-weight: 600;
    color: var(--admin-text-primary);
}
.menu-stats .stat-item .stat-label {
    color: var(--admin-text-muted);
    font-size: 13px;
}
.menu-toggle {
    cursor: pointer;
    color: var(--admin-text-muted);
    transition: transform 0.2s ease;
    display: inline-block;
    width: 20px;
    text-align: center;
}
.menu-toggle.collapsed {
    transform: rotate(-90deg);
}
.link-type-badge {
    display: inline-flex;
    align-items: center;
    margin-right: 6px;
    vertical-align: middle;
}
.link-type-badge svg {
    width: 14px;
    height: 14px;
}
.link-type-module svg { stroke: var(--admin-accent); }
.link-type-external svg { stroke: var(--admin-warning); }
.access-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 10px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 500;
    white-space: nowrap;
}
.access-badge .icon {
    width: 14px;
    height: 14px;
}
.access-public {
    background: rgba(76, 175, 80, 0.15);
    color: var(--admin-success);
}
.access-restricted {
    background: rgba(255, 167, 38, 0.15);
    color: var(--admin-warning);
}
.action-buttons {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}
.status-active { color: var(--admin-success); }
.status-inactive { color: var(--admin-danger); }
.status-active .icon, .status-inactive .icon {
    vertical-align: middle;
    margin-right: 4px;
}
.drag-handle {
    cursor: grab;
    color: var(--admin-text-muted);
    opacity: 0.3;
    font-size: 16px;
    transition: opacity 0.2s ease;
}
.drag-handle:hover {
    opacity: 1 !important;
    color: var(--admin-text-primary) !important;
}
.sortable-ghost { opacity: 0.4; background: var(--admin-bg-hover); }
.sortable-chosen { background: var(--admin-bg-hover); border: 1px solid var(--admin-accent); }
.sortable-drag {
    background: var(--admin-bg-card);
    border: 1px solid var(--admin-accent);
    box-shadow: 0 8px 30px rgba(0,0,0,0.3);
}
.sortable-tree .sortable-row {
    user-select: none;
    transition: background 0.15s ease;
}
@media (max-width: 768px) {
    .module-menu .module-header {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
    }
    .menu-stats { flex-direction: column; }
    .table-responsive { overflow-x: auto; }
}
</style>

<div class="module-menu">
    <div class="module-header">
        <h2>
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 8px;">
                <path d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
            Управление меню
        </h2>
        <a href="?route=menu&action=create" class="btn-admin btn-admin-primary">
            <svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"/>
                <line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Добавить пункт
        </a>
    </div>
    
    <div class="menu-stats">
        <div class="stat-item">
            <span class="stat-number"><?= $totalItems ?></span>
            <span class="stat-label">Всего пунктов</span>
        </div>
        <div class="stat-item">
            <span class="stat-number" style="color: var(--admin-success);"><?= $activeItems ?></span>
            <span class="stat-label">Активных</span>
        </div>
        <div class="stat-item">
            <span class="stat-number" style="color: var(--admin-danger);"><?= $totalItems - $activeItems ?></span>
            <span class="stat-label">Скрытых</span>
        </div>
    </div>
    
    <div class="admin-card">
        <div class="admin-card-header">
            <h3>Структура меню</h3>
        </div>
        
        <?php if (empty($menuTree)): ?>
            <p style="color: var(--admin-text-muted); text-align: center; padding: 30px 0;">
                Пункты меню не найдены
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th style="min-width: 200px;">Название</th>
                            <th>Ссылка</th>
                            <th>Статус</th>
                            <th>Доступ</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody id="menuTree" class="sortable-tree">
                        <?php renderMenuTree($menuTree, 0, $db, $moduleTitles); ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.menu-toggle').on('click', function() {
        var $this = $(this);
        var id = $this.data('id');
        var $rows = $('tr[data-parent="' + id + '"]');
        var isCollapsed = $this.hasClass('collapsed');
        
        if (isCollapsed) {
            $rows.show();
            $this.removeClass('collapsed');
        } else {
            $rows.hide();
            $this.addClass('collapsed');
        }
    });
    
    $(document).on('click', '[data-toggle-menu]', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        var currentStatus = $(this).data('status');
        var newStatus = currentStatus ? 0 : 1;
        
        $.ajax({
            url: '/Adm/api/menu.api.php',
            type: 'POST',
            data: { action: 'toggle', id: id, status: newStatus },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAdminNotification(response.message, 'success');
                    setTimeout(function() { location.reload(); }, 1000);
                } else {
                    showAdminNotification(response.message, 'danger');
                }
            },
            error: function() {
                showAdminNotification('Ошибка выполнения запроса', 'danger');
            }
        });
    });
    
    $(document).on('click', '[data-delete-menu]', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        var title = $(this).data('title');
        
        if (!confirm('Вы уверены, что хотите удалить пункт меню "' + title + '"?')) return;
        
        $.ajax({
            url: '/Adm/api/menu.api.php',
            type: 'POST',
            data: { action: 'delete', id: id },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAdminNotification(response.message, 'success');
                    setTimeout(function() { location.reload(); }, 1000);
                } else {
                    showAdminNotification(response.message, 'danger');
                }
            },
            error: function() {
                showAdminNotification('Ошибка выполнения запроса', 'danger');
            }
        });
    });
    
    console.log('Menu module loaded');
});
</script>