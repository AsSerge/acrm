<?php
/**
 * Модуль Articles - создание статьи
 */

if (!$auth->hasAdminAccess('articles', 'edit')) {
    echo '<div class="admin-alert admin-alert-danger">У вас нет прав для создания статей.</div>';
    return;
}

$db = Database::getInstance();
$contentManager = new ContentManager();

$error = '';

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $content = $_POST['content'] ?? '';
    $metaTitle = trim($_POST['meta_title'] ?? '');
    $metaDescription = trim($_POST['meta_description'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    // Дата публикации
    $publishedAt = trim($_POST['published_at'] ?? '');
    if (empty($publishedAt)) {
        $publishedAt = date('Y-m-d H:i:s');
    } else {
        $publishedAt = str_replace('T', ' ', $publishedAt) . ':00';
    }
    
    // Создаём через ContentManager
    $result = $contentManager->create([
        'type' => 'article',
        'title' => $title,
        'slug' => $slug,
        'excerpt' => $excerpt,
        'content' => $content,
        'meta_title' => $metaTitle,
        'meta_description' => $metaDescription,
        'is_active' => $isActive,
        'published_at' => $publishedAt
    ]);
    
    if ($result['success']) {
        // Логируем
        $user = $auth->getUser();
        $db->insert('audit_log', [
            'user_id' => $user['id'],
            'action' => 'article_create',
            'module' => 'articles',
            'description' => "Создана статья \"{$title}\" (ID: {$result['id']})",
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        // Редирект на редактирование
        echo '<script>window.location.href = "?route=articles&action=edit&id=' . $result['id'] . '";</script>';
        exit;
    } else {
        $error = $result['message'];
    }
}
?>

<link rel="stylesheet" href="/Adm/modules/articles/style.css">

<div class="module-articles">
    <div class="module-header">
        <h2>➕ Создание статьи</h2>
        <a href="?route=articles" class="btn-admin btn-admin-secondary">← Назад к списку</a>
    </div>
    
    <?php if ($error): ?>
        <div class="admin-alert admin-alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <div class="admin-card">
        <form method="POST" class="admin-form">
            <div class="row">
                <div class="col-xs-12 col-md-8">
                    <div class="admin-form-group">
                        <label for="title">Заголовок статьи *</label>
                        <input type="text" id="title" name="title" class="admin-form-control" 
                               value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" 
                               placeholder="Например: Как мы построили модульную CMS" required autofocus>
                    </div>
                </div>
                
                <div class="col-xs-12 col-md-4">
                    <div class="admin-form-group">
                        <label for="slug">URL (slug)</label>
                        <input type="text" id="slug" name="slug" class="admin-form-control" 
                               value="<?= htmlspecialchars($_POST['slug'] ?? '') ?>" 
                               placeholder="auto-generate">
                        <div class="form-hint">Оставьте пустым — сгенерируется автоматически</div>
                    </div>
                </div>
            </div>
            
            <div class="admin-form-group">
                <label for="excerpt">Краткое описание</label>
                <textarea id="excerpt" name="excerpt" class="admin-form-control" rows="2" 
                          placeholder="Краткое описание для списка статей"><?= htmlspecialchars($_POST['excerpt'] ?? '') ?></textarea>
            </div>
            
            <div class="row">
                <div class="col-xs-12 col-md-6">
                    <div class="admin-form-group">
                        <label for="published_at">Дата публикации</label>
                        <input type="datetime-local" id="published_at" name="published_at" class="admin-form-control" 
                               value="<?= htmlspecialchars($_POST['published_at'] ?? date('Y-m-d\TH:i')) ?>">
                    </div>
                </div>
                
                <div class="col-xs-12 col-md-6">
                    <div class="admin-form-group" style="padding-top: 24px;">
                        <label class="admin-checkbox">
                            <input type="checkbox" name="is_active" value="1" checked>
                            <span class="checkmark"></span>
                            Активна
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="admin-form-group">
                <label for="content">Содержимое статьи *</label>
                <textarea id="content" name="content" class="admin-form-control" rows="15" 
                          placeholder="Текст статьи. Можно использовать HTML и блоки."><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
                <div class="form-hint">
                    💡 Для вставки галереи используйте:
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
                               value="<?= htmlspecialchars($_POST['meta_title'] ?? '') ?>" 
                               placeholder="Оставьте пустым — будет использован заголовок">
                    </div>
                </div>
                
                <div class="col-xs-12 col-md-6">
                    <div class="admin-form-group">
                        <label for="meta_description">Meta Description</label>
                        <textarea id="meta_description" name="meta_description" class="admin-form-control" rows="2" 
                                  placeholder="Краткое описание для поисковиков"><?= htmlspecialchars($_POST['meta_description'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
            
            <div class="admin-form-group">
                <button type="submit" class="btn-admin btn-admin-success">Создать статью</button>
                <a href="?route=articles" class="btn-admin btn-admin-secondary">Отмена</a>
            </div>
        </form>
    </div>
</div>