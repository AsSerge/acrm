<?php
/**
 * Модуль Pages - список страниц
 */

if (!$auth->hasAdminAccess('pages', 'view')) {
    echo '<div class="admin-alert admin-alert-danger">У вас нет прав для просмотра этого раздела.</div>';
    return;
}

$db = Database::getInstance();
$pageManager = new PageManager();

$pages = $pageManager->getAll(false);

// Получаем статистику
$totalPages = count($pages);
$activePages = count(array_filter($pages, function($p) { return $p['is_active']; }));
?>

<style>
.module-pages .module-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 12px;
}

.module-pages .module-header h2 {
    font-size: 22px;
    font-weight: 600;
    color: var(--admin-text-primary);
    margin: 0;
}

.pages-stats {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}

.pages-stats .stat-item {
    background: var(--admin-bg-card);
    border: 1px solid var(--admin-border);
    border-radius: 8px;
    padding: 12px 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.pages-stats .stat-item .stat-number {
    font-size: 20px;
    font-weight: 600;
    color: var(--admin-text-primary);
}

.pages-stats .stat-item .stat-label {
    color: var(--admin-text-muted);
    font-size: 13px;
}

.status-active {
    color: var(--admin-success);
}

.status-inactive {
    color: var(--admin-danger);
}

.action-buttons {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.menu-link {
    color: var(--admin-accent);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 13px;
}

.menu-link:hover {
    text-decoration: underline;
}

@media (max-width: 768px) {
    .module-pages .module-header {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
    }
    .pages-stats {
        flex-direction: column;
    }
    .table-responsive {
        overflow-x: auto;
    }
}
</style>

<div class="module-pages">
    <div class="module-header">
        <h2>
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 8px;">
                <path d="M7 20h10a2 2 0 002-2V6a2 2 0 00-2-2H7a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                <path d="M7 4h6v4H7z"/>
            </svg>
            Управление страницами
        </h2>
        <a href="?route=pages&action=create" class="btn-admin btn-admin-primary">
            <svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"/>
                <line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Добавить страницу
        </a>
    </div>
    
    <div class="pages-stats">
        <div class="stat-item">
            <span class="stat-number"><?= $totalPages ?></span>
            <span class="stat-label">Всего страниц</span>
        </div>
        <div class="stat-item">
            <span class="stat-number" style="color: var(--admin-success);"><?= $activePages ?></span>
            <span class="stat-label">Активных</span>
        </div>
        <div class="stat-item">
            <span class="stat-number" style="color: var(--admin-danger);"><?= $totalPages - $activePages ?></span>
            <span class="stat-label">Скрытых</span>
        </div>
    </div>
    
    <div class="admin-card">
        <div class="admin-card-header">
            <h3>Все страницы</h3>
        </div>
        
        <?php if (empty($pages)): ?>
            <p style="color: var(--admin-text-muted); text-align: center; padding: 30px 0;">
                Страницы не найдены
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Заголовок</th>
                            <th>URL</th>
                            <th>Шаблон</th>
                            <th>Статус</th>
                            <th>Привязана к меню</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pages as $page): ?>
                            <tr>
                                <td><?= $page['id'] ?></td>
                                <td><strong><?= htmlspecialchars($page['title']) ?></strong></td>
                                <td>
                                    <code style="background: var(--admin-bg-hover); padding: 2px 8px; border-radius: 4px; font-size: 12px; color: var(--admin-text-secondary);">
                                        <?= htmlspecialchars($page['slug']) ?>
                                    </code>
                                </td>
                                <td><?= htmlspecialchars($page['template']) ?></td>
                                <td>
                                    <?php if ($page['is_active']): ?>
                                        <span class="status-active">
                                            <svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="stroke: var(--admin-success); vertical-align: middle;">
                                                <path d="M20 6L9 17l-5-5"/>
                                            </svg>
                                            Активна
                                        </span>
                                    <?php else: ?>
                                        <span class="status-inactive">
                                            <svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="stroke: var(--admin-danger); vertical-align: middle;">
                                                <line x1="18" y1="6" x2="6" y2="18"/>
                                                <line x1="6" y1="6" x2="18" y2="18"/>
                                            </svg>
                                            Скрыта
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    // Ищем пункт меню, который ссылается на эту страницу
                                    $menuItem = $db->fetchOne(
                                        "SELECT id, menu_title FROM menu_structure WHERE page_id = ? AND menu_type = 'frontend' LIMIT 1",
                                        [$page['id']]
                                    );
                                    
                                    if ($menuItem):
                                        $menuLink = '?route=menu&action=edit&id=' . $menuItem['id'];
                                    ?>
                                        <a href="<?= $menuLink ?>" class="menu-link">
                                            <svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="stroke: currentColor; width: 14px; height: 14px;">
                                                <path d="M4 6h16M4 12h16M4 18h16"/>
                                            </svg>
                                            <?= htmlspecialchars($menuItem['menu_title']) ?>
                                        </a>
                                    <?php else: ?>
                                        <span style="color: var(--admin-text-muted); font-size: 13px;">— не привязана</span>
                                    <?php endif; ?>
                                </td>
								<td>
									<div class="action-buttons">
										<?php if ($auth->isSuperAdmin() && !empty($page['page_file'])): ?>
											<a href="?route=pages&action=editor&file=<?= urlencode('modules/' . $page['page_file']) ?>" 
											class="btn-admin-circle btn-admin-circle-secondary" 
											title="Редактировать код файла">
												<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
													<polyline points="16 18 22 12 16 6"/>
													<polyline points="8 6 2 12 8 18"/>
												</svg>
											</a>
										<?php endif; ?>
										<a href="?route=pages&action=edit&id=<?= $page['id'] ?>" class="btn-admin-circle btn-admin-circle-primary" title="Редактировать">
											<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
												<path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
												<path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
											</svg>
										</a>
										<button class="btn-admin-circle btn-admin-circle-<?= $page['is_active'] ? 'warning' : 'success' ?>" 
												data-toggle-page
												data-id="<?= $page['id'] ?>"
												data-status="<?= $page['is_active'] ?>"
												title="<?= $page['is_active'] ? 'Скрыть' : 'Показать' ?>">
											<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
												<?php if ($page['is_active']): ?>
													<path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/>
													<line x1="1" y1="1" x2="23" y2="23"/>
												<?php else: ?>
													<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
													<circle cx="12" cy="12" r="3"/>
												<?php endif; ?>
											</svg>
										</button>
										<button class="btn-admin-circle btn-admin-circle-danger" 
												data-delete-page
												data-id="<?= $page['id'] ?>"
												data-title="<?= htmlspecialchars($page['title']) ?>"
												title="Удалить">
											<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
        <?php endif; ?>
    </div>
</div>

<script>
$(document).ready(function() {
    
    // Toggle статуса страницы
    $(document).on('click', '[data-toggle-page]', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        var currentStatus = $(this).data('status');
        var newStatus = currentStatus ? 0 : 1;
        
        $.ajax({
            url: '/Adm/api/pages.api.php',
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
    
    // Удаление страницы
    $(document).on('click', '[data-delete-page]', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        var title = $(this).data('title');
        
        if (!confirm('Вы уверены, что хотите удалить страницу "' + title + '"?')) {
            return;
        }
        
        $.ajax({
            url: '/Adm/api/pages.api.php',
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
    
    console.log('Pages module loaded');
});
</script>