<?php
/**
 * Модуль Articles - список статей
 */

if (!$auth->hasAdminAccess('articles', 'view')) {
    echo '<div class="admin-alert admin-alert-danger">У вас нет прав для просмотра этого раздела.</div>';
    return;
}

$db = Database::getInstance();
$contentManager = new ContentManager();

// Параметры
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

// Считаем всего
$total = $contentManager->count([
    'type' => 'article',
    'search' => $search
]);

$totalPages = ceil($total / $perPage);

// Получаем статьи
$articles = $contentManager->getAll([
    'type' => 'article',
    'search' => $search,
    'limit' => $perPage,
    'offset' => $offset
]);

// Фильтр по статусу (простой, в PHP)
if ($statusFilter === 'active') {
    $articles = array_filter($articles, function($a) { return $a['is_active']; });
} elseif ($statusFilter === 'inactive') {
    $articles = array_filter($articles, function($a) { return !$a['is_active']; });
}
?>

<link rel="stylesheet" href="/Adm/modules/articles/style.css">

<div class="module-articles">
    <div class="module-header">
        <h2>
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 8px;">
                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
            </svg>
            Управление статьями
        </h2>
        <a href="?route=articles&action=create" class="btn-admin btn-admin-primary">
            <svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"/>
                <line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Создать статью
        </a>
    </div>
    
    <!-- Фильтры -->
    <div class="articles-filters">
        <form method="GET" class="filters-form">
            <input type="hidden" name="route" value="articles">
            
            <div class="filter-group">
                <input type="text" name="search" placeholder="Поиск по заголовку или slug..." 
                       value="<?= htmlspecialchars($search) ?>" class="admin-form-control">
            </div>
            
            <div class="filter-group">
                <select name="status" class="admin-form-control">
                    <option value="">Все статусы</option>
                    <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Активные</option>
                    <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Скрытые</option>
                </select>
            </div>
            
            <button type="submit" class="btn-admin btn-admin-primary">Фильтр</button>
            <a href="?route=articles" class="btn-admin btn-admin-secondary">Сбросить</a>
        </form>
    </div>
    
    <!-- Таблица -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3>Все статьи (<?= $total ?>)</h3>
        </div>
        
        <?php if (empty($articles)): ?>
            <p style="color: var(--admin-text-muted); text-align: center; padding: 40px 0;">
                Статей пока нет. <a href="?route=articles&action=create" style="color: var(--admin-accent);">Создайте первую</a>!
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th style="width: 60px;">Обложка</th>
                            <th>Заголовок</th>
                            <th>Slug</th>
                            <th>Дата</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($articles as $article): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($article['cover'])): ?>
                                        <img src="<?= htmlspecialchars($article['cover']) ?>" 
                                             alt="" 
                                             class="article-cover-thumb"
                                             loading="lazy">
                                    <?php else: ?>
                                        <div class="article-cover-placeholder">📄</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($article['title']) ?></strong>
                                    <?php if (!empty($article['excerpt'])): ?>
                                        <br><small style="color: var(--admin-text-muted);">
                                            <?= htmlspecialchars(mb_substr($article['excerpt'], 0, 80)) ?>...
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <code style="background: var(--admin-bg-hover); padding: 2px 8px; border-radius: 4px; font-size: 12px; color: var(--admin-text-secondary);">
                                        <?= htmlspecialchars($article['slug']) ?>
                                    </code>
                                </td>
                                <td style="font-size: 13px;">
                                    <?= $article['published_at'] ? date('d.m.Y H:i', strtotime($article['published_at'])) : '—' ?>
                                </td>
                                <td>
                                    <?php if ($article['is_active']): ?>
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
                                    <div class="action-buttons">
                                        <a href="?route=articles&action=edit&id=<?= $article['id'] ?>" 
                                           class="btn-admin-circle btn-admin-circle-primary" 
                                           title="Редактировать">
                                            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                                                <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                            </svg>
                                        </a>
                                        <button class="btn-admin-circle btn-admin-circle-danger" 
                                                data-delete-article
                                                data-id="<?= $article['id'] ?>"
                                                data-title="<?= htmlspecialchars($article['title']) ?>"
                                                title="Удалить">
                                            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="3 6 5 6 21 6"/>
                                                <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Пагинация -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?route=articles&page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= $statusFilter ?>" 
                           class="<?= $i == $page ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
$(document).ready(function() {
    // Удаление статьи
    $(document).on('click', '[data-delete-article]', function(e) {
        e.preventDefault();
        
        var id = $(this).data('id');
        var title = $(this).data('title');
        
        if (!confirm('Удалить статью "' + title + '"?\nЭто действие нельзя отменить!')) return;
        
        $.ajax({
            url: '/Adm/api/articles.api.php',
            type: 'POST',
            data: { action: 'delete', id: id },
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
                showAdminNotification('Ошибка удаления', 'danger');
            }
        });
    });
});
</script>