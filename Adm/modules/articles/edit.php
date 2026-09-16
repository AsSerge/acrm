<?php
/**
 * Модуль Articles - редактирование статьи
 */

if (!$auth->hasAdminAccess('articles', 'edit')) {
    echo '<div class="admin-alert admin-alert-danger">У вас нет прав для редактирования статей.</div>';
    return;
}

$db = Database::getInstance();
$contentManager = new ContentManager();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo '<div class="admin-alert admin-alert-danger">Не указан ID статьи.</div>';
    return;
}

$article = $contentManager->getById($id);

if (!$article || $article['type'] !== 'article') {
    echo '<div class="admin-alert admin-alert-danger">Статья не найдена.</div>';
    return;
}

$error = '';
$success = '';

// ============================================
// ОБРАБОТКА СОХРАНЕНИЯ
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $content = $_POST['content'] ?? '';
    $metaTitle = trim($_POST['meta_title'] ?? '');
    $metaDescription = trim($_POST['meta_description'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    // Дата публикации
    $publishedAt = trim($_POST['published_at'] ?? '');
    if (!empty($publishedAt)) {
        $publishedAt = str_replace('T', ' ', $publishedAt);
        if (strlen($publishedAt) === 16) {
            $publishedAt .= ':00';
        }
    } else {
        $publishedAt = $article['published_at'];
    }
    
    $result = $contentManager->update($id, [
        'title' => $title,
        'slug' => $slug,
        'excerpt' => $excerpt,
        'content' => $content,
        'meta_title' => $metaTitle,
        'meta_description' => $metaDescription,
        'is_active' => $isActive,
        'published_at' => $publishedAt,
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    
    if ($result['success']) {
        // Логируем
        $user = $auth->getUser();
        $db->insert('audit_log', [
            'user_id' => $user['id'],
            'action' => 'article_edit',
            'module' => 'articles',
            'description' => "Отредактирована статья \"{$title}\" (ID: {$id})",
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        $success = 'Статья обновлена!';
        $article = $contentManager->getById($id);
    } else {
        $error = $result['message'];
    }
}

// Форматируем дату для input
$publishedAtInput = '';
if (!empty($article['published_at'])) {
    $publishedAtInput = date('Y-m-d\TH:i', strtotime($article['published_at']));
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
            Редактирование статьи
        </h2>
        <a href="?route=articles" class="btn-admin btn-admin-secondary">← К списку</a>
    </div>
    
    <?php if ($error): ?>
        <div class="admin-alert admin-alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="admin-alert admin-alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    
    <!-- ============================================ -->
    <!-- ОБЛОЖКА (опционально для статьи) -->
    <!-- ============================================ -->
    
    <div class="admin-card">
        <div class="admin-card-header">
            <h3>🖼️ Обложка (опционально)</h3>
        </div>
        
        <div class="cover-upload-wrapper">
            <div class="cover-preview" id="cover-preview" style="<?= empty($article['cover']) ? 'display: none;' : '' ?>">
                <?php if (!empty($article['cover'])): ?>
                    <img src="<?= htmlspecialchars($article['cover']) ?>" alt="Обложка">
                <?php endif; ?>
            </div>
            
            <input type="hidden" id="cover_path" value="<?= htmlspecialchars($article['cover'] ?? '') ?>">
            
            <div class="cover-actions">
                <button type="button" class="btn-admin btn-admin-primary" id="btn-upload-cover">
                    <svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                        <polyline points="17 8 12 3 7 8"/>
                        <line x1="12" y1="3" x2="12" y2="15"/>
                    </svg>
                    <?= empty($article['cover']) ? 'Загрузить обложку' : 'Заменить обложку' ?>
                </button>
                <?php if (!empty($article['cover'])): ?>
                    <button type="button" class="btn-admin btn-admin-danger" id="btn-delete-cover">
                        Удалить
                    </button>
                <?php endif; ?>
            </div>
            
            <input type="file" id="cover-input" accept="image/*" style="display: none;">
            
            <div class="form-hint" style="margin-top: 8px;">
                📐 <strong>Рекомендуемый размер:</strong> 800×600 px (4:3)<br>
                📁 <strong>Форматы:</strong> JPG, PNG, WEBP
            </div>
        </div>
    </div>
    
    <!-- ============================================ -->
    <!-- ОСНОВНАЯ ФОРМА -->
    <!-- ============================================ -->
    
    <div class="admin-card">
        <form method="POST" class="admin-form">
            <input type="hidden" name="action" value="update">
            
            <div class="row">
                <div class="col-xs-12 col-md-8">
                    <div class="admin-form-group">
                        <label for="title">Заголовок *</label>
                        <input type="text" id="title" name="title" class="admin-form-control" 
                               value="<?= htmlspecialchars($article['title']) ?>" required>
                    </div>
                </div>
                
                <div class="col-xs-12 col-md-4">
                    <div class="admin-form-group">
                        <label for="slug">URL (slug)</label>
                        <input type="text" id="slug" name="slug" class="admin-form-control" 
                               value="<?= htmlspecialchars($article['slug']) ?>">
                    </div>
                </div>
            </div>
            
            <div class="admin-form-group">
                <label for="excerpt">Краткое описание</label>
                <textarea id="excerpt" name="excerpt" class="admin-form-control" rows="2"><?= htmlspecialchars($article['excerpt']) ?></textarea>
            </div>
            
            <div class="row">
                <div class="col-xs-12 col-md-6">
                    <div class="admin-form-group">
                        <label for="published_at">Дата публикации</label>
                        <input type="datetime-local" id="published_at" name="published_at" class="admin-form-control" 
                               value="<?= htmlspecialchars($publishedAtInput) ?>">
                    </div>
                </div>
                
                <div class="col-xs-12 col-md-6">
                    <div class="admin-form-group" style="padding-top: 24px;">
                        <label class="admin-checkbox">
                            <input type="checkbox" name="is_active" value="1" <?= $article['is_active'] ? 'checked' : '' ?>>
                            <span class="checkmark"></span>
                            Активна (опубликована)
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="admin-form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; flex-wrap: wrap; gap: 8px;">
                    <label for="content">Содержимое статьи *</label>
                    <button type="button" class="btn-admin btn-admin-secondary" id="btn-open-ace">
                        <svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="16 18 22 12 16 6"/>
                            <polyline points="8 6 2 12 8 18"/>
                        </svg>
                        Редактировать код (Ace)
                    </button>
                </div>
                <textarea id="content" name="content" class="admin-form-control" rows="20"><?= htmlspecialchars($article['content']) ?></textarea>
                <div class="form-hint">
                    💡 Для вставки галереи в текст:
                    <code>&lt;div data-block="gallery" data-gallery-id="1"&gt;&lt;/div&gt;</code>
                </div>
            </div>
            
            <hr style="border: none; border-top: 1px solid var(--admin-border); margin: 24px 0;">
            
            <h3 style="font-size: 16px; color: var(--admin-text-primary); margin-bottom: 16px;">🔍 SEO</h3>
            
            <div class="row">
                <div class="col-xs-12 col-md-6">
                    <div class="admin-form-group">
                        <label for="meta_title">Meta Title</label>
                        <input type="text" id="meta_title" name="meta_title" class="admin-form-control" 
                               value="<?= htmlspecialchars($article['meta_title']) ?>">
                    </div>
                </div>
                
                <div class="col-xs-12 col-md-6">
                    <div class="admin-form-group">
                        <label for="meta_description">Meta Description</label>
                        <textarea id="meta_description" name="meta_description" class="admin-form-control" rows="2"><?= htmlspecialchars($article['meta_description']) ?></textarea>
                    </div>
                </div>
            </div>
            
            <div class="admin-form-group">
                <button type="submit" class="btn-admin btn-admin-success">💾 Сохранить</button>
                <a href="?route=articles" class="btn-admin btn-admin-secondary">Отмена</a>
            </div>
        </form>
    </div>
</div>

<!-- ============================================ -->
<!-- МОДАЛЬНОЕ ОКНО ACE-РЕДАКТОРА -->
<!-- ============================================ -->

<div class="ace-modal" id="ace-modal">
    <div class="ace-modal-content">
        <div class="ace-modal-header">
            <h3>📝 Редактор кода</h3>
            <div class="ace-modal-actions">
                <button type="button" class="btn-admin btn-admin-success" id="ace-save">
                    💾 Применить
                </button>
                <button type="button" class="btn-admin btn-admin-secondary" id="ace-close">
                    Закрыть
                </button>
            </div>
        </div>
        <div id="ace-editor" class="ace-editor"></div>
        <div class="ace-modal-footer">
            <small style="color: var(--admin-text-muted);">
                Ctrl+S — применить · ESC — закрыть
            </small>
        </div>
    </div>
</div>

<!-- Ace Editor -->
<script src="/vendor/ace/ace.js"></script>
<script src="/vendor/ace/mode-html.js"></script>
<script src="/vendor/ace/theme-tomorrow_night.js"></script>

<script>
$(document).ready(function() {
    var articleId = <?= (int)$id ?>;
    var aceEditor = null;
    
    // ============================================
    // ACE-РЕДАКТОР
    // ============================================
    
    $('#btn-open-ace').on('click', function() {
        // Инициализируем Ace, если ещё не инициализирован
        if (!aceEditor) {
            aceEditor = ace.edit('ace-editor');
            aceEditor.setTheme('ace/theme/tomorrow_night');
            aceEditor.session.setMode('ace/mode/html');
            aceEditor.setOptions({
                fontSize: '14px',
                fontFamily: 'Consolas, "Courier New", monospace',
                showPrintMargin: false,
                wrap: true,
                tabSize: 4,
                useSoftTabs: true
            });
        }
        
        // Устанавливаем текущий контент
        aceEditor.setValue($('#content').val(), -1);
        
        // Показываем модальное окно
        $('#ace-modal').addClass('active');
        
        // Фокус
        setTimeout(function() {
            aceEditor.focus();
        }, 100);
    });
    
    // Применить изменения
    $('#ace-save').on('click', function() {
        if (aceEditor) {
            $('#content').val(aceEditor.getValue());
            showAdminNotification('Изменения применены. Не забудьте сохранить статью!', 'info');
        }
        $('#ace-modal').removeClass('active');
    });
    
    // Закрыть без применения
    $('#ace-close').on('click', function() {
        $('#ace-modal').removeClass('active');
    });
    
    // Закрытие по клику на фон
    $('#ace-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).removeClass('active');
        }
    });
    
    // Ctrl+S в Ace — применить
    $(document).on('keydown', function(e) {
        if ($('#ace-modal').hasClass('active')) {
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                $('#ace-save').click();
            } else if (e.key === 'Escape') {
                $('#ace-close').click();
            }
        }
    });
    
    // ============================================
    // ЗАГРУЗКА ОБЛОЖКИ
    // ============================================
    
    $('#btn-upload-cover').on('click', function() {
        $('#cover-input').click();
    });
    
    $('#cover-input').on('change', function() {
        if (!this.files.length) return;
        
        var formData = new FormData();
        formData.append('action', 'upload_cover');
        formData.append('id', articleId);
        formData.append('cover', this.files[0]);
        
        showAdminNotification('Загрузка обложки...', 'info');
        
        $.ajax({
            url: '/Adm/api/articles.api.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#cover_path').val(response.path);
                    $('#cover-preview').html('<img src="' + response.path + '?t=' + Date.now() + '" alt="Обложка">').show();
                    $('#btn-upload-cover').html('Заменить обложку');
                    
                    // Добавляем кнопку удаления, если её нет
                    if ($('#btn-delete-cover').length === 0) {
                        $('.cover-actions').append('<button type="button" class="btn-admin btn-admin-danger" id="btn-delete-cover">Удалить</button>');
                    }
                    
                    showAdminNotification('Обложка загружена', 'success');
                } else {
                    showAdminNotification(response.message, 'danger');
                }
            },
            error: function() {
                showAdminNotification('Ошибка загрузки обложки', 'danger');
            }
        });
        
        $(this).val('');
    });
    
    // Удаление обложки
    $(document).on('click', '#btn-delete-cover', function() {
        if (!confirm('Удалить обложку?')) return;
        
        $.ajax({
            url: '/Adm/api/articles.api.php',
            type: 'POST',
            data: { action: 'delete_cover', id: articleId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#cover_path').val('');
                    $('#cover-preview').hide().html('');
                    $('#btn-upload-cover').html('Загрузить обложку');
                    $('#btn-delete-cover').remove();
                    showAdminNotification('Обложка удалена', 'success');
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