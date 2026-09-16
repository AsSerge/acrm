<?php
/**
 * Модуль Modules - список модулей
 */

if (!$auth->hasAdminAccess('modules', 'view')) {
    echo '<div class="admin-alert admin-alert-danger">У вас нет прав для просмотра этого раздела.</div>';
    return;
}

$db = Database::getInstance();
$moduleManager = new ModuleManager();

// Сканируем папку /modules/ (автоматически регистрируем новые)
$scanResult = $moduleManager->scanModules();

// Получаем все модули из БД
$modules = $db->fetchAll("
    SELECT * FROM modules_registry 
    ORDER BY is_frontend DESC, module_name
");

// Статистика
$totalModules = count($modules);
$frontendModules = count(array_filter($modules, function($m) { return $m['is_frontend']; }));
$activeModules = count(array_filter($modules, function($m) { return $m['is_active']; }));
?>

<style>
.module-modules .module-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 12px;
}

.module-modules .module-header h2 {
    font-size: 22px;
    font-weight: 600;
    color: var(--admin-text-primary);
    margin: 0;
}

.modules-stats {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}

.modules-stats .stat-item {
    background: var(--admin-bg-card);
    border: 1px solid var(--admin-border);
    border-radius: 8px;
    padding: 12px 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.modules-stats .stat-item .stat-number {
    font-size: 20px;
    font-weight: 600;
    color: var(--admin-text-primary);
}

.modules-stats .stat-item .stat-label {
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

.module-type-badge {
    display: inline-block;
    padding: 2px 10px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 600;
}

.module-type-frontend {
    background: rgba(76, 175, 80, 0.2);
    color: var(--admin-success);
}

.module-type-admin {
    background: rgba(100, 181, 246, 0.2);
    color: var(--admin-accent);
}

.template-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 11px;
    background: var(--admin-bg-hover);
    color: var(--admin-text-secondary);
    font-family: monospace;
}

@media (max-width: 768px) {
    .module-modules .module-header {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
    }
    .modules-stats {
        flex-direction: column;
    }
    .table-responsive {
        overflow-x: auto;
    }
}
</style>

<div class="module-modules">
    <div class="module-header">
        <h2>
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 8px;">
                <path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/>
                <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                <line x1="12" y1="22.08" x2="12" y2="12"/>
            </svg>
            Управление модулями
        </h2>
        <button type="button" class="btn-admin btn-admin-primary" id="btn-rescan">
            <svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="23 4 23 10 17 10"/>
                <polyline points="1 20 1 14 7 14"/>
                <path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/>
            </svg>
            Обнаружить модули
        </button>
    </div>
    
    <?php if ($scanResult['added'] > 0): ?>
        <div class="admin-alert admin-alert-success">
            ✅ Обнаружено новых модулей: <strong><?= $scanResult['added'] ?></strong>
        </div>
    <?php endif; ?>
    
    <div class="modules-stats">
        <div class="stat-item">
            <span class="stat-number"><?= $totalModules ?></span>
            <span class="stat-label">Всего модулей</span>
        </div>
        <div class="stat-item">
            <span class="stat-number" style="color: var(--admin-success);"><?= $frontendModules ?></span>
            <span class="stat-label">Фронтенд</span>
        </div>
        <div class="stat-item">
            <span class="stat-number" style="color: var(--admin-accent);"><?= $activeModules ?></span>
            <span class="stat-label">Активных</span>
        </div>
    </div>
    
    <div class="admin-card">
        <div class="admin-card-header">
            <h3>Все модули</h3>
        </div>
        
        <?php if (empty($modules)): ?>
            <p style="color: var(--admin-text-muted); text-align: center; padding: 30px 0;">
                Модули не найдены. Создайте папку в /modules/ и нажмите «Обнаружить модули».
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Название</th>
                            <th>Slug</th>
                            <th>Тип</th>
                            <th>Шаблон</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($modules as $module): ?>
                            <tr>
                                <td><?= $module['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($module['title'] ?: $module['module_name']) ?></strong>
                                    <br>
                                    <small style="color: var(--admin-text-muted); font-family: monospace;">
                                        /modules/<?= htmlspecialchars($module['module_name']) ?>/
                                    </small>
                                </td>
                                <td>
                                    <code style="background: var(--admin-bg-hover); padding: 2px 8px; border-radius: 4px; font-size: 12px; color: var(--admin-text-secondary);">
                                        <?= htmlspecialchars($module['slug']) ?>
                                    </code>
                                </td>
                                <td>
                                    <?php if ($module['is_frontend']): ?>
                                        <span class="module-type-badge module-type-frontend">🌐 Фронтенд</span>
                                    <?php else: ?>
                                        <span class="module-type-badge module-type-admin">🛡️ Админ</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="template-badge"><?= htmlspecialchars($module['template'] ?: 'default') ?></span>
                                </td>
                                <td>
                                    <?php if ($module['is_active']): ?>
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
                                    <div class="action-buttons">
                                        <?php if ($module['is_frontend']): ?>
                                            <a href="?route=modules&action=editor&file=modules/<?= urlencode($module['module_name']) ?>/index.php" 
                                               class="btn-admin-circle btn-admin-circle-secondary" 
                                               title="Редактировать код">
                                                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="16 18 22 12 16 6"/>
                                                    <polyline points="8 6 2 12 8 18"/>
                                                </svg>
                                            </a>
                                        <?php endif; ?>
                                        <a href="?route=modules&action=edit&id=<?= $module['id'] ?>" class="btn-admin-circle btn-admin-circle-primary" title="Редактировать">
                                            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                                                <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                            </svg>
                                        </a>
                                        <button class="btn-admin-circle btn-admin-circle-<?= $module['is_active'] ? 'warning' : 'success' ?>" 
                                                data-toggle-module
                                                data-id="<?= $module['id'] ?>"
                                                data-status="<?= $module['is_active'] ?>"
                                                title="<?= $module['is_active'] ? 'Скрыть' : 'Показать' ?>">
                                            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <?php if ($module['is_active']): ?>
                                                    <path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/>
                                                    <line x1="1" y1="1" x2="23" y2="23"/>
                                                <?php else: ?>
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                                    <circle cx="12" cy="12" r="3"/>
                                                <?php endif; ?>
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
    // Пересканирование
    $('#btn-rescan').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true);
        showAdminNotification('Сканирование...', 'info');
        
        $.ajax({
            url: '/Adm/api/modules.api.php',
            type: 'POST',
            data: { action: 'scan' },
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
                showAdminNotification('Ошибка сканирования', 'danger');
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });
    
    // Toggle статуса
    $(document).on('click', '[data-toggle-module]', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        var currentStatus = $(this).data('status');
        var newStatus = currentStatus ? 0 : 1;
        
        $.ajax({
            url: '/Adm/api/modules.api.php',
            type: 'POST',
            data: { action: 'toggle', id: id, status: newStatus },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAdminNotification(response.message, 'success');
                    setTimeout(function() { location.reload(); }, 800);
                } else {
                    showAdminNotification(response.message, 'danger');
                }
            },
            error: function() {
                showAdminNotification('Ошибка выполнения запроса', 'danger');
            }
        });
    });
    
    console.log('Modules module loaded');
});
</script>