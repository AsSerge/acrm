<?php
/**
 * Модуль Media - медиа-библиотека
 */

if (!$auth->hasAdminAccess('media', 'view')) {
    echo '<div class="admin-alert admin-alert-danger">У вас нет прав для просмотра этого раздела.</div>';
    return;
}

$db = Database::getInstance();
?>

<link rel="stylesheet" href="/Adm/modules/media/style.css">

<div class="module-media">
    <div class="module-header">
        <h2>
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 8px;">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                <circle cx="8.5" cy="8.5" r="1.5"/>
                <polyline points="21 15 16 10 5 21"/>
            </svg>
            Медиа-библиотека
        </h2>
        <div class="media-actions">
            <button type="button" class="btn-admin btn-admin-primary" id="btn-upload">
                <svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                    <polyline points="17 8 12 3 7 8"/>
                    <line x1="12" y1="3" x2="12" y2="15"/>
                </svg>
                Загрузить
            </button>
        </div>
    </div>
    
    <!-- Хлебные крошки -->
    <div class="media-breadcrumbs" id="breadcrumbs">
        <span class="breadcrumb-item active" data-path="">📁 uploads</span>
    </div>
    
    <!-- Зона drag-and-drop -->
    <div class="media-dropzone" id="dropzone">
        <div class="dropzone-content">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 48px; height: 48px; stroke: var(--admin-text-muted);">
                <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                <polyline points="17 8 12 3 7 8"/>
                <line x1="12" y1="3" x2="12" y2="15"/>
            </svg>
            <p>Перетащите файлы сюда или нажмите «Загрузить»</p>
            <small>JPG, PNG, GIF, WEBP, SVG — до 15 МБ</small>
        </div>
    </div>
    
    <!-- Скрытый input для загрузки -->
    <input type="file" id="file-input" accept="image/*" multiple style="display: none;">
    
    <!-- Сетка изображений -->
    <div class="media-grid" id="media-grid">
        <p class="media-loading">Загрузка...</p>
    </div>
</div>

<!-- Модальное окно для просмотра -->
<div class="media-modal" id="media-modal">
    <div class="media-modal-content">
        <button type="button" class="media-modal-close" id="media-modal-close">✕</button>
        <img src="" alt="" id="media-modal-image">
        <div class="media-modal-info">
            <p><strong>Путь:</strong> <code id="media-modal-path"></code></p>
            <p><strong>Размер:</strong> <span id="media-modal-size"></span></p>
            <div class="media-modal-actions">
                <button type="button" class="btn-admin btn-admin-primary" id="btn-copy-path">
                    📋 Копировать путь
                </button>
                <button type="button" class="btn-admin btn-admin-danger" id="btn-delete-image">
                    🗑️ Удалить
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var currentPath = '';
    var currentImage = null;
    
    // ============================================
    // ЗАГРУЗКА СПИСКА ФАЙЛОВ
    // ============================================
    
    function loadFiles(path) {
        $('#media-grid').html('<p class="media-loading">Загрузка...</p>');
        
        $.ajax({
            url: '/Adm/api/media.api.php',
            type: 'GET',
            data: { action: 'scan', path: path },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    currentPath = path;
                    renderBreadcrumbs(path);
                    renderFiles(response.files, path);
                } else {
                    $('#media-grid').html('<p style="color: var(--admin-danger);">' + response.message + '</p>');
                }
            },
            error: function() {
                $('#media-grid').html('<p style="color: var(--admin-danger);">Ошибка загрузки файлов</p>');
            }
        });
    }
    
    // ============================================
    // ХЛЕБНЫЕ КРОШКИ
    // ============================================
    
    function renderBreadcrumbs(path) {
        var parts = path ? path.split('/') : [];
        var html = '<span class="breadcrumb-item ' + (parts.length === 0 ? 'active' : '') + '" data-path="">📁 uploads</span>';
        var builtPath = '';
        
        parts.forEach(function(part, index) {
            builtPath += (builtPath ? '/' : '') + part;
            var isLast = index === parts.length - 1;
            html += '<span class="breadcrumb-separator">›</span>';
            html += '<span class="breadcrumb-item ' + (isLast ? 'active' : '') + '" data-path="' + builtPath + '">' + part + '</span>';
        });
        
        $('#breadcrumbs').html(html);
    }
    
    // Клик по хлебным крошкам
    $(document).on('click', '.breadcrumb-item', function() {
        var path = $(this).data('path');
        loadFiles(path);
    });
    
    // ============================================
    // ОТРИСОВКА ФАЙЛОВ
    // ============================================
    
    function renderFiles(files, path) {
        var html = '';
        
        // Папки
        var dirs = files.filter(function(f) { return f.type === 'dir'; });
        var images = files.filter(function(f) { return f.type === 'image'; });
        
		dirs.forEach(function(item) {
			// Проверяем, является ли папка верхнего уровня
			var isTopFolder = item.path.split('/').length === 1 && 
				['site', 'news', 'articles', 'gallery', 'users', 'modules', 'blocks'].indexOf(item.path) !== -1;
			
			var actionsHtml = '';
			if (!isTopFolder) {
				actionsHtml = '<div class="media-item-actions">';
				actionsHtml += '<button type="button" class="media-item-action rename" data-rename-folder="' + item.path + '" data-name="' + item.name + '" title="Переименовать">✏️</button>';
				actionsHtml += '<button type="button" class="media-item-action delete" data-delete-folder="' + item.path + '" data-name="' + item.name + '" title="Удалить">🗑️</button>';
				actionsHtml += '</div>';
			}
			
			html += '<div class="media-item media-folder" data-scan="' + item.path + '">';
			html += actionsHtml;
			html += '<div class="media-item-icon">📁</div>';
			html += '<div class="media-item-name">' + item.name + '</div>';
			html += '</div>';
		});
        
        // Изображения
        images.forEach(function(item) {
            var thumbPath = item.thumb || item.path;
            html += '<div class="media-item media-image" ';
            html += 'data-path="' + item.path + '" ';
            html += 'data-full="' + item.path + '" ';
            html += 'data-size="' + formatSize(item.size) + '">';
            html += '<img src="' + thumbPath + '" alt="' + item.name + '" loading="lazy">';
            html += '<div class="media-item-name">' + item.name + '</div>';
            html += '</div>';
        });
        
        if (html === '') {
            html = '<p class="media-empty">Файлов нет</p>';
        }
        
        $('#media-grid').html(html);
    }
    
    // ============================================
    // ФОРМАТ РАЗМЕРА
    // ============================================
    
    function formatSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    }
    
    // ============================================
    // НАВИГАЦИЯ ПО ПАПКАМ
    // ============================================
    
    $(document).on('click', '.media-folder', function() {
        var path = $(this).data('scan');
        loadFiles(path);
    });

	    // ============================================
    // УДАЛЕНИЕ ПАПКИ
    // ============================================
    
    $(document).on('click', '[data-delete-folder]', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var path = $(this).data('delete-folder');
        var name = $(this).data('name');
        
        if (!confirm('Удалить папку "' + name + '" и ВСЁ её содержимое?\nЭто действие нельзя отменить!')) {
            return;
        }
        
        $.ajax({
            url: '/Adm/api/media.api.php',
            type: 'POST',
            data: { action: 'delete_folder', path: path },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAdminNotification('Папка удалена (файлов: ' + (response.deleted || 0) + ')', 'success');
                    loadFiles(currentPath);
                } else {
                    showAdminNotification(response.message, 'danger');
                }
            },
            error: function() {
                showAdminNotification('Ошибка удаления папки', 'danger');
            }
        });
    });
    
    // ============================================
    // ПЕРЕИМЕНОВАНИЕ ПАПКИ
    // ============================================
    
    $(document).on('click', '[data-rename-folder]', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var path = $(this).data('rename-folder');
        var oldName = $(this).data('name');
        
        var newName = prompt('Введите новое имя папки:', oldName);
        
        if (!newName || newName === oldName) return;
        
        $.ajax({
            url: '/Adm/api/media.api.php',
            type: 'POST',
            data: { action: 'rename_folder', path: path, new_name: newName },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAdminNotification('Папка переименована', 'success');
                    loadFiles(currentPath);
                } else {
                    showAdminNotification(response.message, 'danger');
                }
            },
            error: function() {
                showAdminNotification('Ошибка переименования', 'danger');
            }
        });
    });
    
    // ============================================
    // ОТКРЫТИЕ ИЗОБРАЖЕНИЯ
    // ============================================
    
    $(document).on('click', '.media-image', function() {
        var path = $(this).data('path');
        var full = $(this).data('full');
        var size = $(this).data('size');
        
        currentImage = path;
        
        $('#media-modal-image').attr('src', full);
        $('#media-modal-path').text('/' + full);
        $('#media-modal-size').text(size);
        $('#media-modal').addClass('active');
    });
    
    // Закрытие модального окна
    $('#media-modal-close, #media-modal').on('click', function(e) {
        if (e.target === this || e.target.id === 'media-modal-close') {
            $('#media-modal').removeClass('active');
            currentImage = null;
        }
    });
    
    // ============================================
    // КОПИРОВАНИЕ ПУТИ
    // ============================================
    
    $('#btn-copy-path').on('click', function() {
        var path = '/' + currentImage;
        
        if (navigator.clipboard) {
            navigator.clipboard.writeText(path).then(function() {
                showAdminNotification('Путь скопирован: ' + path, 'success');
            });
        } else {
            // Fallback
            var $temp = $('<input>');
            $('body').append($temp);
            $temp.val(path).select();
            document.execCommand('copy');
            $temp.remove();
            showAdminNotification('Путь скопирован', 'success');
        }
    });
    
    // ============================================
    // УДАЛЕНИЕ ИЗОБРАЖЕНИЯ
    // ============================================
    
    $('#btn-delete-image').on('click', function() {
        if (!currentImage) return;
        
        if (!confirm('Удалить изображение?\nЭто действие нельзя отменить!')) {
            return;
        }
        
        $.ajax({
            url: '/Adm/api/media.api.php',
            type: 'POST',
            data: { action: 'delete', path: currentImage },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAdminNotification('Изображение удалено', 'success');
                    $('#media-modal').removeClass('active');
                    loadFiles(currentPath);
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
    // ЗАГРУЗКА ФАЙЛОВ
    // ============================================
    
    // Кнопка «Загрузить»
    $('#btn-upload').on('click', function() {
        $('#file-input').click();
    });
    
    // Выбор файлов
    $('#file-input').on('change', function() {
        var files = this.files;
        if (files.length > 0) {
            uploadFiles(files);
        }
        $(this).val('');
    });
    
    // Drag-and-drop
    var $dropzone = $('#dropzone');
    
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
            uploadFiles(files);
        }
    });
    
    // Функция загрузки
	function uploadFiles(files) {
		// Определяем папку для загрузки:
		// - Если мы в корне /uploads/ → папка "site"
		// - Если мы в /uploads/news/ → папка "news"
		// - Если мы в /uploads/news/2026-09/ → папка "news/2026-09"
		var folder = currentPath || 'site';
		
		var total = files.length;
		var uploaded = 0;
		
		showAdminNotification('Загрузка ' + total + ' файл(ов) в "' + folder + '"...', 'info');
		
		for (var i = 0; i < files.length; i++) {
			var formData = new FormData();
			formData.append('action', 'upload');
			formData.append('folder', folder);
			formData.append('file', files[i]);
			
			$.ajax({
				url: '/Adm/api/media.api.php',
				type: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				dataType: 'json',
				success: function(response) {
					if (response.success) {
						uploaded++;
					} else {
						showAdminNotification('Ошибка: ' + response.message, 'danger');
					}
				},
				complete: function() {
					if (uploaded === total || (uploaded + 1) === total) {
						showAdminNotification('Загружено: ' + uploaded + ' из ' + total, 'success');
						loadFiles(currentPath);
					}
				}
			});
		}
	}
    
    // ============================================
    // ИНИЦИАЛИЗАЦИЯ
    // ============================================
    
    loadFiles('');
    
    console.log('Media module loaded');
});
</script>