<?php
/**
 * Модуль Galleries - список галерей
 */

if (!$auth->hasAdminAccess('galleries', 'view')) {
    echo '<div class="admin-alert admin-alert-danger">У вас нет прав для просмотра этого раздела.</div>';
    return;
}

$db = Database::getInstance();
$galleryManager = new GalleryManager();

$galleries = $galleryManager->getAll(false);
?>

<link rel="stylesheet" href="/Adm/modules/galleries/style.css">

<div class="module-galleries">
    <div class="module-header">
        <h2>
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 8px;">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                <circle cx="8.5" cy="8.5" r="1.5"/>
                <polyline points="21 15 16 10 5 21"/>
            </svg>
            Управление галереями
        </h2>
        <a href="?route=galleries&action=create" class="btn-admin btn-admin-primary">
            <svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"/>
                <line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Создать галерею
        </a>
    </div>
    
    <?php if (empty($galleries)): ?>
        <div class="admin-card">
            <p style="color: var(--admin-text-muted); text-align: center; padding: 40px 0;">
                Галерей пока нет. Создайте первую!
            </p>
        </div>
    <?php else: ?>
        <div class="admin-card">
            <div class="galleries-grid">
                <?php foreach ($galleries as $gallery): ?>
                    <div class="gallery-card" data-gallery-id="<?= $gallery['id'] ?>">
                        <div class="gallery-card-header">
                            <h3><?= htmlspecialchars($gallery['title']) ?></h3>
                            <?php if (!$gallery['is_active']): ?>
                                <span class="gallery-status gallery-status-inactive">Скрыта</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="gallery-card-info">
                            <?php if (!empty($gallery['description'])): ?>
                                <p><?= htmlspecialchars($gallery['description']) ?></p>
                            <?php endif; ?>
                            <div class="gallery-meta">
                                <span>🖼️ <?= $gallery['images_count'] ?> фото</span>
                                <span>📅 <?= date('d.m.Y', strtotime($gallery['created_at'])) ?></span>
                            </div>
                        </div>
                        
                        <div class="gallery-card-actions">
                            <a href="?route=galleries&action=edit&id=<?= $gallery['id'] ?>" class="btn-admin btn-admin-primary btn-admin-sm">
                                ✏️ Редактировать
                            </a>
                            <button type="button" class="btn-admin btn-admin-danger btn-admin-sm" 
                                    data-delete-gallery
                                    data-id="<?= $gallery['id'] ?>"
                                    data-title="<?= htmlspecialchars($gallery['title']) ?>"
                                    data-count="<?= $gallery['images_count'] ?>">
                                🗑️ Удалить
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
$(document).ready(function() {
    // Удаление галереи
    $(document).on('click', '[data-delete-gallery]', function(e) {
        e.preventDefault();
        
        var id = $(this).data('id');
        var title = $(this).data('title');
        var count = $(this).data('count');
        
        var message = 'Удалить галерею "' + title + '"?\n';
        if (count > 0) {
            message += '\n⚠️ В галерее ' + count + ' фото. Они будут удалены безвозвратно!';
        }
        
        if (!confirm(message)) return;
        
        $.ajax({
            url: '/Adm/api/galleries.api.php',
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