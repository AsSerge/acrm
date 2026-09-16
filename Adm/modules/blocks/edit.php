<?php
/**
 * Модуль Blocks - редактирование блока
 */

if (!$auth->hasAdminAccess('blocks', 'edit')) {
    echo '<div class="admin-alert admin-alert-danger">У вас нет прав для редактирования блоков.</div>';
    return;
}

$db = Database::getInstance();
$blockManager = new BlockManager();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo '<div class="admin-alert admin-alert-danger">Не указан ID блока.</div>';
    return;
}

$block = $blockManager->getBlockById($id);

if (!$block) {
    echo '<div class="admin-alert admin-alert-danger">Блок не найден.</div>';
    return;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $version = trim($_POST['version'] ?? '1.0.0');
    $author = trim($_POST['author'] ?? 'admin');
    $params = trim($_POST['params'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    // Валидация
    if (empty($title)) {
        $error = 'Название блока обязательно';
    } elseif (!empty($params)) {
        // Проверяем, что params — валидный JSON
        $decoded = json_decode($params, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $error = 'Поле «Параметры» должно содержать валидный JSON: ' . json_last_error_msg();
        }
    }
    
    if (empty($error)) {
        $blockManager->updateBlock($block['block_name'], [
            'title' => $title,
            'description' => $description,
            'version' => $version,
            'author' => $author,
            'params' => !empty($params) ? $params : null,
            'is_active' => $isActive,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        // Логируем
        $user = $auth->getUser();
        $db->insert('audit_log', [
            'user_id' => $user['id'],
            'action' => 'block_edit',
            'module' => 'blocks',
            'description' => "Отредактирован блок {$block['block_name']} ({$title})",
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        $success = "Блок «{$title}» успешно обновлён!";
        
        // Обновляем данные
        $block = $blockManager->getBlockById($id);
    }
}

// Проверяем, есть ли файлы блока
$blockPath = ROOT_DIR . '/blocks/' . $block['block_name'];
$hasBlockPhp = file_exists($blockPath . '/block.php');
$hasStyleCss = file_exists($blockPath . '/style.css');
$hasScriptJs = file_exists($blockPath . '/script.js');

// Предупреждение о таблицах
$possibleTables = [
    'block_' . str_replace('-', '_', $block['block_name']),
    'block_' . str_replace('-', '_', $block['block_name']) . '_*',
];
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

.block-files {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 12px;
}

.block-file-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 14px;
    background: var(--admin-bg-primary);
    border: 1px solid var(--admin-border);
    border-radius: 4px;
    font-family: 'Consolas', 'Courier New', monospace;
    font-size: 13px;
}

.block-file-item .file-name {
    color: var(--admin-text-primary);
}

.block-file-item .file-status {
    font-size: 12px;
}

.block-file-item .file-status.ok {
    color: var(--admin-success);
}

.block-file-item .file-status.missing {
    color: var(--admin-danger);
}

.params-help {
    background: var(--admin-bg-primary);
    border: 1px solid var(--admin-border);
    border-radius: 4px;
    padding: 12px 16px;
    font-family: 'Consolas', 'Courier New', monospace;
    font-size: 12px;
    color: var(--admin-text-secondary);
    margin-top: 8px;
    white-space: pre-wrap;
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
            Редактирование блока
        </h2>
        <a href="?route=blocks" class="btn-admin btn-admin-secondary">← Назад к списку</a>
    </div>
    
    <?php if ($error): ?>
        <div class="admin-alert admin-alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="admin-alert admin-alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    
    <div class="admin-card">
        <form method="POST" class="admin-form">
            <div class="row">
                <div class="col-xs-12 col-md-6">
                    <div class="admin-form-group">
                        <label>Имя блока (папка)</label>
                        <input type="text" class="admin-form-control" value="<?= htmlspecialchars($block['block_name']) ?>" disabled>
                        <div class="form-hint">Имя папки в /blocks/. Изменение недоступно.</div>
                    </div>
                </div>
                
                <div class="col-xs-12 col-md-6">
                    <div class="admin-form-group">
                        <label>Путь</label>
                        <input type="text" class="admin-form-control" value="/blocks/<?= htmlspecialchars($block['block_name']) ?>/" disabled>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-xs-12 col-md-6">
                    <div class="admin-form-group">
                        <label for="title">Название блока *</label>
                        <input type="text" id="title" name="title" class="admin-form-control" 
                               value="<?= htmlspecialchars($block['title']) ?>" required>
                    </div>
                </div>
                
                <div class="col-xs-12 col-md-6">
                    <div class="admin-form-group">
                        <label for="version">Версия</label>
                        <input type="text" id="version" name="version" class="admin-form-control" 
                               value="<?= htmlspecialchars($block['version']) ?>">
                    </div>
                </div>
            </div>
            
            <div class="admin-form-group">
                <label for="description">Описание</label>
                <textarea id="description" name="description" class="admin-form-control" rows="2"><?= htmlspecialchars($block['description']) ?></textarea>
            </div>
            
            <div class="row">
                <div class="col-xs-12 col-md-6">
                    <div class="admin-form-group">
                        <label for="author">Автор</label>
                        <input type="text" id="author" name="author" class="admin-form-control" 
                               value="<?= htmlspecialchars($block['author']) ?>">
                    </div>
                </div>
                
                <div class="col-xs-12 col-md-6">
                    <div class="admin-form-group" style="padding-top: 24px;">
                        <label class="admin-checkbox">
                            <input type="checkbox" name="is_active" value="1" <?= $block['is_active'] ? 'checked' : '' ?>>
                            <span class="checkmark"></span>
                            Активен
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="admin-form-group">
                <label for="params">Параметры (JSON)</label>
                <textarea id="params" name="params" class="admin-form-control" rows="6" 
                          placeholder='{"count": {"type": "int", "default": 3, "label": "Количество"}}'><?= htmlspecialchars($block['params'] ?? '') ?></textarea>
                <div class="form-hint">
                    Опционально. JSON с описанием параметров блока.<br>
                    Используется только для справки в админке — разработчик знает, какие <code>data-*</code> передавать.
                </div>
                <div class="params-help">Пример:
{
    "count": {
        "type": "int",
        "default": 3,
        "label": "Количество записей"
    },
    "category": {
        "type": "string",
        "default": "",
        "label": "Категория"
    }
}</div>
            </div>
            
            <div class="admin-form-group">
                <label>Файлы блока</label>
                <div class="block-files">
                    <div class="block-file-item">
                        <span class="file-name">📄 block.php</span>
                        <span class="file-status <?= $hasBlockPhp ? 'ok' : 'missing' ?>">
                            <?= $hasBlockPhp ? '✅ существует' : '❌ отсутствует' ?>
                        </span>
                    </div>
                    <div class="block-file-item">
                        <span class="file-name">🎨 style.css</span>
                        <span class="file-status <?= $hasStyleCss ? 'ok' : 'missing' ?>">
                            <?= $hasStyleCss ? '✅ существует' : '❌ отсутствует' ?>
                        </span>
                    </div>
                    <div class="block-file-item">
                        <span class="file-name">⚙️ script.js</span>
                        <span class="file-status <?= $hasScriptJs ? 'ok' : 'missing' ?>">
                            <?= $hasScriptJs ? '✅ существует' : '❌ отсутствует' ?>
                        </span>
                    </div>
                </div>
                <div class="form-hint" style="margin-top: 8px;">
                    📝 Для редактирования кода используйте <a href="?route=modules&action=editor&file=blocks/<?= urlencode($block['block_name']) ?>/block.php">редактор кода</a>.
                </div>
            </div>
            
            <div class="admin-alert admin-alert-warning">
                <strong>⚠️ Напоминание о таблицах БД</strong><br>
                Если блок использует таблицы, они должны иметь префикс:<br>
                <code>block_<?= str_replace('-', '_', $block['block_name']) ?></code> или 
                <code>block_<?= str_replace('-', '_', $block['block_name']) ?>_*</code><br>
                <small>Например: <code>block_news</code>, <code>block_news_category</code></small><br><br>
                При удалении блока таблицы <strong>НЕ удаляются автоматически</strong>. Удалите их вручную через phpMyAdmin.
            </div>
            
            <div class="admin-form-group">
                <button type="submit" class="btn-admin btn-admin-success">Сохранить</button>
                <a href="?route=blocks" class="btn-admin btn-admin-secondary">Отмена</a>
                <a href="?route=modules&action=editor&file=blocks/<?= urlencode($block['block_name']) ?>/block.php" class="btn-admin btn-admin-primary">
                    <svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="16 18 22 12 16 6"/>
                        <polyline points="8 6 2 12 8 18"/>
                    </svg>
                    Редактировать код
                </a>
            </div>
        </form>
    </div>
</div>