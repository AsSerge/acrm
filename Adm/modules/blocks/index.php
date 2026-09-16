<?php
/**
 * Модуль Blocks - список блоков
 */

if (!$auth->hasAdminAccess('blocks', 'view')) {
    echo '<div class="admin-alert admin-alert-danger">У вас нет прав для просмотра этого раздела.</div>';
    return;
}

$db = Database::getInstance();
$blockManager = new BlockManager();

// Сканируем папку /blocks/ (автоматически регистрируем новые)
$scanResult = $blockManager->scanBlocks();

// Получаем все блоки из БД
$blocks = $db->fetchAll("SELECT * FROM blocks_registry ORDER BY block_name");

$totalBlocks = count($blocks);
$activeBlocks = count(array_filter($blocks, function($b) { return $b['is_active']; }));
?>

<style>
.module-blocks .module-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 12px;
}

.module-blocks .module-header h2 {
    font-size: 22px;
    font-weight: 600;
    color: var(--admin-text-primary);
    margin: 0;
}

.blocks-stats {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}

.blocks-stats .stat-item {
    background: var(--admin-bg-card);
    border: 1px solid var(--admin-border);
    border-radius: 8px;
    padding: 12px 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.blocks-stats .stat-item .stat-number {
    font-size: 20px;
    font-weight: 600;
    color: var(--admin-text-primary);
}

.blocks-stats .stat-item .stat-label {
    color: var(--admin-text-muted);
    font-size: 13px;
}

.status-active { color: var(--admin-success); }
.status-inactive { color: var(--admin-danger); }

.action-buttons {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.block-path {
    font-family: 'Consolas', 'Courier New', monospace;
    font-size: 12px;
    color: var(--admin-text-muted);
}

@media (max-width: 768px) {
    .module-blocks .module-header {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
    }
    .blocks-stats { flex-direction: column; }
    .table-responsive { overflow-x: auto; }
}
</style>

<div class="module-blocks">
    <div class="module-header">
        <h2>
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 8px;">
                <rect x="3" y="3" width="7" height="7"/>
                <rect x="14" y="3" width="7" height="7"/>
                <rect x="14" y="14" width="7" height="7"/>
                <rect x="3" y="14" width="7" height="7"/>
            </svg>
            Управление блоками
        </h2>
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <button type="button" class="btn-admin btn-admin-secondary" id="btn-rescan">
                <svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="23 4 23 10 17 10"/>
                    <polyline points="1 20 1 14 7 14"/>
                    <path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/>
                </svg>
                Обнаружить блоки
            </button>
            <a href="?route=blocks&action=create" class="btn-admin btn-admin-primary">
                <svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                Создать блок
            </a>
        </div>
    </div>
    
    <?php if ($scanResult['added'] > 0): ?>
        <div class="admin-alert admin-alert-success">
            ✅ Обнаружено новых блоков: <strong><?= $scanResult['added'] ?></strong>
        </div>
    <?php endif; ?>
    
    <div class="blocks-stats">
        <div class="stat-item">
            <span class="stat-number"><?= $totalBlocks ?></span>
            <span class="stat-label">Всего блоков</span>
        </div>
        <div class="stat-item">
            <span class="stat-number" style="color: var(--admin-success);"><?= $activeBlocks ?></span>
            <span class="stat-label">Активных</span>
        </div>
    </div>
    
    <div class="admin-card">
        <?php if (empty($blocks)): ?>
            <p style="color: var(--admin-text-muted); text-align: center; padding: 30px 0;">
                Блоки не найдены. Создайте первый блок.
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Название</th>
                            <th>Описание</th>
                            <th>Версия</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($blocks as $block): ?>
                            <tr>
                                <td><?= $block['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($block['title']) ?></strong>
                                    <br>
                                    <span class="block-path">/blocks/<?= htmlspecialchars($block['block_name']) ?>/</span>
                                </td>
                                <td><?= htmlspecialchars($block['description'] ?: '—') ?></td>
                                <td><?= htmlspecialchars($block['version']) ?></td>
                                <td>
                                    <?php if ($block['is_active']): ?>
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
										<a href="?route=modules&action=editor&file=blocks/<?= urlencode($block['block_name']) ?>/block.php" 
												class="btn-admin-circle btn-admin-circle-secondary" 
												title="Редактировать код">
                                            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="16 18 22 12 16 6"/>
                                                <polyline points="8 6 2 12 8 18"/>
                                            </svg>
                                        </a>
                                        <a href="?route=blocks&action=edit&id=<?= $block['id'] ?>" class="btn-admin-circle btn-admin-circle-primary" title="Редактировать">
                                            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                                                <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                            </svg>
                                        </a>
                                        <button class="btn-admin-circle btn-admin-circle-<?= $block['is_active'] ? 'warning' : 'success' ?>" 
                                                data-toggle-block
                                                data-id="<?= $block['id'] ?>"
                                                data-status="<?= $block['is_active'] ?>"
                                                title="<?= $block['is_active'] ? 'Скрыть' : 'Показать' ?>">
                                            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <?php if ($block['is_active']): ?>
                                                    <path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/>
                                                    <line x1="1" y1="1" x2="23" y2="23"/>
                                                <?php else: ?>
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                                    <circle cx="12" cy="12" r="3"/>
                                                <?php endif; ?>
                                            </svg>
                                        </button>
                                        <button class="btn-admin-circle btn-admin-circle-danger" 
                                                data-delete-block
                                                data-id="<?= $block['id'] ?>"
                                                data-name="<?= htmlspecialchars($block['block_name']) ?>"
                                                data-title="<?= htmlspecialchars($block['title']) ?>"
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
    // Пересканирование
    $('#btn-rescan').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true);
        showAdminNotification('Сканирование...', 'info');
        
        $.ajax({
            url: '/Adm/api/blocks.api.php',
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
    
    // Toggle
    $(document).on('click', '[data-toggle-block]', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        var currentStatus = $(this).data('status');
        var newStatus = currentStatus ? 0 : 1;
        
        $.ajax({
            url: '/Adm/api/blocks.api.php',
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
    
    // Удаление
    $(document).on('click', '[data-delete-block]', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        var name = $(this).data('name');
        var title = $(this).data('title');
        
        if (!confirm('Вы уверены, что хотите удалить блок "' + title + '"?\n\n⚠️ Папка /blocks/' + name + '/ будет удалена полностью!\n\nНе забудьте удалить таблицы block_' + name + '_* из базы данных вручную через phpMyAdmin.')) {
            return;
        }
        
        $.ajax({
            url: '/Adm/api/blocks.api.php',
            type: 'POST',
            data: { action: 'delete', id: id, name: name },
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
    
    console.log('Blocks module loaded');
});
</script>