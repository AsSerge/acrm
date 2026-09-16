<?php
/**
 * Модуль Blocks - создание блока
 */

if (!$auth->hasAdminAccess('blocks', 'edit')) {
    echo '<div class="admin-alert admin-alert-danger">У вас нет прав для создания блоков.</div>';
    return;
}

$db = Database::getInstance();
$blockManager = new BlockManager();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $author = trim($_POST['author'] ?? 'admin');
    
    // Валидация
    if (empty($name)) {
        $error = 'Имя блока обязательно';
    } elseif (!$blockManager->validateName($name)) {
        $error = 'Имя блока должно содержать только строчные латинские буквы, цифры и дефис (например: news, feedback-form)';
    } elseif (empty($title)) {
        $error = 'Название блока обязательно';
    } else {
        $result = $blockManager->createBlock($name, $title, $description, $author);
        
        if ($result['success']) {
            // Логируем
            $user = $auth->getUser();
            $db->insert('audit_log', [
                'user_id' => $user['id'],
                'action' => 'block_create',
                'module' => 'blocks',
                'description' => "Создан блок {$name} ({$title})",
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $success = $result['message'];
            $_POST = [];
        } else {
            $error = $result['message'];
        }
    }
}
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

.admin-form .row { margin-bottom: 8px; }
.admin-form .form-hint { color: var(--admin-text-muted); font-size: 12px; margin-top: 4px; }

.code-preview {
    background: var(--admin-bg-primary);
    border: 1px solid var(--admin-border);
    border-radius: 4px;
    padding: 12px 16px;
    font-family: 'Consolas', 'Courier New', monospace;
    font-size: 13px;
    color: var(--admin-text-secondary);
    margin-top: 8px;
}

@media (max-width: 768px) {
    .module-blocks .module-header {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
    }
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
            Создание блока
        </h2>
        <a href="?route=blocks" class="btn-admin btn-admin-secondary">← Назад к списку</a>
    </div>
    
    <?php if ($error): ?>
        <div class="admin-alert admin-alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="admin-alert admin-alert-success">
            <?= htmlspecialchars($success) ?>
            <br><br>
            <a href="?route=blocks" class="btn-admin btn-admin-primary">← Перейти к списку блоков</a>
        </div>
    <?php else: ?>
    
    <div class="admin-card">
        <form method="POST" class="admin-form">
            <div class="row">
                <div class="col-xs-12 col-md-6">
                    <div class="admin-form-group">
                        <label for="name">Имя блока *</label>
                        <input type="text" id="name" name="name" class="admin-form-control" 
                               value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" 
                               placeholder="например: news" 
                               pattern="[a-z][a-z0-9-]*"
                               required>
                        <div class="form-hint">
                            Только <strong>строчные латинские буквы</strong>, цифры и дефис.<br>
                            Это имя папки: <code>/blocks/news/</code>
                        </div>
                    </div>
                </div>
                
                <div class="col-xs-12 col-md-6">
                    <div class="admin-form-group">
                        <label for="title">Название блока *</label>
                        <input type="text" id="title" name="title" class="admin-form-control" 
                               value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" 
                               placeholder="например: Лента новостей" 
                               required>
                        <div class="form-hint">Человекочитаемое название для админки</div>
                    </div>
                </div>
            </div>
            
            <div class="admin-form-group">
                <label for="description">Описание</label>
                <textarea id="description" name="description" class="admin-form-control" rows="3" 
                          placeholder="Краткое описание назначения блока"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>
            
            <div class="admin-form-group">
                <label for="author">Автор</label>
                <input type="text" id="author" name="author" class="admin-form-control" 
                       value="<?= htmlspecialchars($_POST['author'] ?? 'admin') ?>">
            </div>
            
            <div class="admin-alert admin-alert-info">
                <strong>📁 Будет создана структура:</strong>
                <div class="code-preview">
/blocks/<span id="preview-name">news</span>/<br>
├── block.php      ← логика + HTML<br>
├── style.css      ← стили<br>
└── script.js      ← скрипты
                </div>
            </div>
            
            <div class="admin-form-group">
                <button type="submit" class="btn-admin btn-admin-success">Создать блок</button>
                <a href="?route=blocks" class="btn-admin btn-admin-secondary">Отмена</a>
            </div>
        </form>
    </div>
    
    <?php endif; ?>
</div>

<script>
$(document).ready(function() {
    // Обновляем превью имени папки
    $('#name').on('input', function() {
        var val = $(this).val().trim() || 'news';
        $('#preview-name').text(val);
    });
});
</script>