<?php
/**
 * Модуль Galleries - редактирование галереи
 */

if (!$auth->hasAdminAccess('galleries', 'edit')) {
    echo '<div class="admin-alert admin-alert-danger">У вас нет прав для редактирования галерей.</div>';
    return;
}

$db = Database::getInstance();
$galleryManager = new GalleryManager();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo '<div class="admin-alert admin-alert-danger">Не указан ID галереи.</div>';
    return;
}

$gallery = $galleryManager->getById($id);

if (!$gallery) {
    echo '<div class="admin-alert admin-alert-danger">Галерея не найдена.</div>';
    return;
}

$error = '';
$success = '';

// ============================================
// ОБРАБОТКА СОХРАНЕНИЯ МЕТАДАННЫХ
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_meta') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($title)) {
        $error = 'Название галереи обязательно';
    } else {
        $galleryManager->update($id, [
            'title' => $title,
            'description' => $description,
            'is_active' => $isActive,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        // Логируем
        $user = $auth->getUser();
        $db->insert('audit_log', [
            'user_id' => $user['id'],
            'action' => 'gallery_edit',
            'module' => 'galleries',
            'description' => "Отредактирована галерея \"{$title}\" (ID: {$id})",
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        $success = "Галерея обновлена!";
        $gallery = $galleryManager->getById($id);
    }
}
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
            Редактирование галереи
        </h2>
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <a href="?route=galleries" class="btn-admin btn-admin-secondary">← К списку</a>
        </div>
    </div>
    
    <?php if ($error): ?>
        <div class="admin-alert admin-alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="admin-alert admin-alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    
    <!-- ============================================ -->
    <!-- МЕТАДАННЫЕ ГАЛЕРЕИ -->
    <!-- ============================================ -->
    
    <div class="admin-card">
        <div class="admin-card-header">
            <h3>📝 Параметры галереи</h3>
        </div>
        
        <form method="POST" class="admin-form">
            <input type="hidden" name="action" value="update_meta">
            
            <div class="row">
                <div class="col-xs-12 col-md-6">
                    <div class="admin-form-group">
                        <label for="title">Название галереи *</label>
                        <input type="text" id="title" name="title" class="admin-form-control" 
                               value="<?= htmlspecialchars($gallery['title']) ?>" required>
                    </div>
                </div>
                
                <div class="col-xs-12 col-md-6">
                    <div class="admin-form-group" style="padding-top: 24px;">
                        <label class="admin-checkbox">
                            <input type="checkbox" name="is_active" value="1" <?= $gallery['is_active'] ? 'checked' : '' ?>>
                            <span class="checkmark"></span>
                            Активна
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="admin-form-group">
                <label for="description">Описание</label>
                <textarea id="description" name="description" class="admin-form-control" rows="2"><?= htmlspecialchars($gallery['description']) ?></textarea>
            </div>
            
            <div class="admin-form-group">
                <button type="submit" class="btn-admin btn-admin-success">💾 Сохранить</button>
            </div>
        </form>
    </div>
    
    <!-- ============================================ -->
    <!-- ЗАГРУЗКА ФОТО -->
    <!-- ============================================ -->
    
    <div class="admin-card">
        <div class="admin-card-header">
            <h3>🖼️ Изображения (<span id="images-count"><?= count($gallery['images']) ?></span>)</h3>
            <button type="button" class="btn-admin btn-admin-primary" id="btn-upload-image">
                <svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                    <polyline points="17 8 12 3 7 8"/>
                    <line x1="12" y1="3" x2="12" y2="15"/>
                </svg>
                Загрузить фото
            </button>
        </div>
        

		<!-- Зона drag-and-drop -->
<div class="gallery-dropzone" id="gallery-dropzone">
    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 48px; height: 48px; stroke: var(--admin-text-muted);">
        <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
        <polyline points="17 8 12 3 7 8"/>
        <line x1="12" y1="3" x2="12" y2="15"/>
    </svg>
    <p>Перетащите фотографии сюда или нажмите «Загрузить фото»</p>
    <small>JPG, PNG, GIF, WEBP — до 15 МБ. Можно несколько сразу.</small>
</div>

<!-- Прогресс загрузки -->
<div class="gallery-upload-progress" id="upload-progress" style="display: none;">
    <div class="progress-info">
        <span class="progress-text">Загрузка...</span>
        <span class="progress-counter"><span id="progress-done">0</span> из <span id="progress-total">0</span></span>
    </div>
    <div class="progress-bar">
        <div class="progress-fill" id="progress-fill" style="width: 0%;"></div>
    </div>
</div>

<!-- Временные превью загружаемых фото -->
<div class="gallery-uploading-grid" id="uploading-grid"></div>

<input type="file" id="image-input" accept="image/*" multiple style="display: none;">
        
        <input type="file" id="image-input" accept="image/*" multiple style="display: none;">
        
        <!-- Сетка изображений -->
        <div class="gallery-images-grid" id="images-grid">
            <?php if (empty($gallery['images'])): ?>
                <p class="gallery-empty">Фотографий пока нет. Загрузите первые!</p>
            <?php else: ?>
                <?php foreach ($gallery['images'] as $image): ?>
                    <?php
                    // Определяем путь к миниатюре
                    $thumbPath = $image['image_path'];
                    $info = pathinfo($image['image_path']);
                    $thumbName = $info['filename'] . '_thumb.' . ($info['extension'] ?? '');
                    $thumbDir = dirname($image['image_path']);
                    $thumbFullPath = ROOT_DIR . $thumbDir . '/' . $thumbName;
                    
                    if (file_exists($thumbFullPath)) {
                        $thumbPath = $thumbDir . '/' . $thumbName;
                    }
                    ?>
                    <div class="gallery-image-item" data-image-id="<?= $image['id'] ?>">
                        <div class="gallery-image-preview">
                            <img src="<?= htmlspecialchars($thumbPath) ?>" alt="<?= htmlspecialchars($image['image_alt']) ?>" loading="lazy">
                        </div>
                        
                        <div class="gallery-image-actions">
                            <button type="button" class="gallery-image-btn edit" 
                                    data-edit-image
                                    data-id="<?= $image['id'] ?>"
                                    data-alt="<?= htmlspecialchars($image['image_alt']) ?>"
                                    data-caption="<?= htmlspecialchars($image['image_caption']) ?>"
                                    title="Редактировать">
                                ✏️
                            </button>
                            <button type="button" class="gallery-image-btn delete" 
                                    data-delete-image
                                    data-id="<?= $image['id'] ?>"
                                    title="Удалить">
                                🗑️
                            </button>
                        </div>
                        
                        <?php if (!empty($image['image_caption'])): ?>
                            <div class="gallery-image-caption"><?= htmlspecialchars($image['image_caption']) ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- МОДАЛЬНОЕ ОКНО РЕДАКТИРОВАНИЯ ИЗОБРАЖЕНИЯ -->
<!-- ============================================ -->

<div class="gallery-modal" id="image-edit-modal">
    <div class="gallery-modal-content">
        <button type="button" class="gallery-modal-close" id="modal-close">✕</button>
        <h3>Редактирование изображения</h3>
        
        <div class="admin-form-group">
            <label for="modal-alt">Alt-текст (для SEO)</label>
            <input type="text" id="modal-alt" class="admin-form-control" placeholder="Описание изображения">
        </div>
        
        <div class="admin-form-group">
            <label for="modal-caption">Подпись</label>
            <input type="text" id="modal-caption" class="admin-form-control" placeholder="Подпись под изображением">
        </div>
        
        <div class="gallery-modal-actions">
            <button type="button" class="btn-admin btn-admin-secondary" id="modal-cancel">Отмена</button>
            <button type="button" class="btn-admin btn-admin-success" id="modal-save">Сохранить</button>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var galleryId = <?= (int)$id ?>;
    var currentImageId = null;
    
    // ============================================
    // ЗАГРУЗКА ИЗОБРАЖЕНИЙ
    // ============================================
    
    $('#btn-upload-image').on('click', function() {
        $('#image-input').click();
    });
    
    $('#image-input').on('change', function() {
        if (this.files.length > 0) {
            uploadImages(this.files);
        }
        $(this).val('');
    });
    
    // Drag-and-drop
    var $dropzone = $('#gallery-dropzone');
    
    $dropzone.on('dragover dragenter', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).addClass('dragover');
    });
    
    $dropzone.on('dragleave dragend drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('dragover');
    });
    
    $dropzone.on('drop', function(e) {
        var files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) {
            uploadImages(files);
        }
    });
    
function uploadImages(files) {
    var total = files.length;
    var done = 0;
    var uploaded = 0;
    var errors = 0;
    
    // Показываем прогресс
    $('#upload-progress').show();
    $('#progress-total').text(total);
    $('#progress-done').text(0);
    $('#progress-fill').css('width', '0%');
    
    // Очищаем контейнер временных превью
    $('#uploading-grid').html('');
    
    // Создаём превью для каждого файла
    for (var i = 0; i < total; i++) {
        (function(file, index) {
            var fileId = 'upload-' + Date.now() + '-' + index;
            
            // Создаём элемент превью
            var previewHtml = '<div class="uploading-item" id="' + fileId + '">';
            previewHtml += '<div class="uploading-preview">';
            
            // Если это изображение — создаём превью
            if (file.type.match('image.*')) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    $('#' + fileId + ' .uploading-preview').html('<img src="' + e.target.result + '" alt="">');
                };
                reader.readAsDataURL(file);
            } else {
                previewHtml += '<div class="uploading-icon">📎</div>';
            }
            
            previewHtml += '</div>';
            previewHtml += '<div class="uploading-overlay">';
            previewHtml += '<div class="uploading-status">⏳</div>';
            previewHtml += '</div>';
            previewHtml += '</div>';
            
            $('#uploading-grid').append(previewHtml);
            
            // Загружаем файл
            var formData = new FormData();
            formData.append('action', 'add_image');
            formData.append('gallery_id', galleryId);
            formData.append('image', file);
            
            $.ajax({
                url: '/Adm/api/galleries.api.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                xhr: function() {
                    // Прогресс для каждого файла
                    var xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener('progress', function(e) {
                        if (e.lengthComputable) {
                            var percent = Math.round((e.loaded / e.total) * 100);
                            $('#' + fileId + ' .uploading-status').text(percent + '%');
                        }
                    }, false);
                    return xhr;
                },
                success: function(response) {
                    if (response.success) {
                        uploaded++;
                        $('#' + fileId).addClass('uploading-success');
                        $('#' + fileId + ' .uploading-status').html('✓');
                    } else {
                        errors++;
                        $('#' + fileId).addClass('uploading-error');
                        $('#' + fileId + ' .uploading-status').html('✗');
                        $('#' + fileId + ' .uploading-overlay').attr('title', response.message);
                    }
                },
                error: function() {
                    errors++;
                    $('#' + fileId).addClass('uploading-error');
                    $('#' + fileId + ' .uploading-status').html('✗');
                },
                complete: function() {
                    done++;
                    
                    // Обновляем прогресс
                    var percent = Math.round((done / total) * 100);
                    $('#progress-done').text(done);
                    $('#progress-fill').css('width', percent + '%');
                    
                    // Все загружены?
                    if (done === total) {
                        var text = 'Готово: ' + uploaded + ' из ' + total;
                        if (errors > 0) {
                            text += ' (' + errors + ' с ошибкой)';
                        }
                        
                        $('.progress-text').text(text);
                        
                        if (uploaded > 0) {
                            showAdminNotification('Загружено: ' + uploaded + ' из ' + total, 'success');
                            
                            // Через 1.5 секунды — перезагружаем
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        } else {
                            showAdminNotification('Не удалось загрузить ни один файл', 'danger');
                        }
                    }
                }
            });
        })(files[i], i);
    }
}
    
    // ============================================
    // УДАЛЕНИЕ ИЗОБРАЖЕНИЯ
    // ============================================
    
    $(document).on('click', '[data-delete-image]', function(e) {
        e.preventDefault();
        
        var imageId = $(this).data('id');
        
        if (!confirm('Удалить это изображение?')) return;
        
        $.ajax({
            url: '/Adm/api/galleries.api.php',
            type: 'POST',
            data: { action: 'delete_image', image_id: imageId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAdminNotification('Изображение удалено', 'success');
                    $('[data-image-id="' + imageId + '"]').fadeOut(300, function() {
                        $(this).remove();
                        updateImagesCount();
                    });
                } else {
                    showAdminNotification(response.message, 'danger');
                }
            },
            error: function() {
                showAdminNotification('Ошибка удаления', 'danger');
            }
        });
    });
    
    // ============================================
    // РЕДАКТИРОВАНИЕ ИЗОБРАЖЕНИЯ
    // ============================================
    
    $(document).on('click', '[data-edit-image]', function(e) {
        e.preventDefault();
        
        currentImageId = $(this).data('id');
        
        $('#modal-alt').val($(this).data('alt') || '');
        $('#modal-caption').val($(this).data('caption') || '');
        $('#image-edit-modal').addClass('active');
        $('#modal-alt').focus();
    });
    
    $('#modal-close, #modal-cancel').on('click', function() {
        $('#image-edit-modal').removeClass('active');
        currentImageId = null;
    });
    
    $('#image-edit-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).removeClass('active');
            currentImageId = null;
        }
    });
    
    $('#modal-save').on('click', function() {
        if (!currentImageId) return;
        
        var alt = $('#modal-alt').val();
        var caption = $('#modal-caption').val();
        
        $.ajax({
            url: '/Adm/api/galleries.api.php',
            type: 'POST',
            data: {
                action: 'update_image',
                image_id: currentImageId,
                alt: alt,
                caption: caption
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAdminNotification('Изображение обновлено', 'success');
                    $('#image-edit-modal').removeClass('active');
                    
                    // Обновляем атрибуты кнопки
                    var $btn = $('[data-edit-image][data-id="' + currentImageId + '"]');
                    $btn.data('alt', alt);
                    $btn.data('caption', caption);
                    
                    // Обновляем подпись в DOM
                    var $item = $('[data-image-id="' + currentImageId + '"]');
                    $item.find('.gallery-image-caption').remove();
                    if (caption) {
                        $item.append('<div class="gallery-image-caption">' + caption + '</div>');
                    }
                    
                    currentImageId = null;
                } else {
                    showAdminNotification(response.message, 'danger');
                }
            },
            error: function() {
                showAdminNotification('Ошибка сохранения', 'danger');
            }
        });
    });
    
    // ============================================
    // ОБНОВЛЕНИЕ СЧЁТЧИКА
    // ============================================
    
    function updateImagesCount() {
        var count = $('.gallery-image-item').length;
        $('#images-count').text(count);
        
        if (count === 0) {
            $('#images-grid').html('<p class="gallery-empty">Фотографий пока нет. Загрузите первые!</p>');
        }
    }
	    // ============================================
    // СОРТИРОВКА ИЗОБРАЖЕНИЙ (drag-and-drop)
    // ============================================
    
    var imagesGrid = document.getElementById('images-grid');
    
    if (imagesGrid && typeof Sortable !== 'undefined') {
        new Sortable(imagesGrid, {
            animation: 150,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            handle: '.gallery-image-preview', // Тянем только за превью
            onEnd: function(evt) {
                saveImageOrder();
            }
        });
    }
    
    function saveImageOrder() {
        var order = [];
        
        $('.gallery-image-item').each(function() {
            order.push($(this).data('image-id'));
        });
        
        if (order.length === 0) return;
        
        $.ajax({
            url: '/Adm/api/galleries.api.php',
            type: 'POST',
            data: { 
                action: 'sort', 
                order: order 
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAdminNotification('Порядок сохранён', 'success');
                } else {
                    showAdminNotification(response.message, 'danger');
                }
            },
            error: function() {
                showAdminNotification('Ошибка сохранения порядка', 'danger');
            }
        });
    }
});
</script>