<?php
/**
 * Модуль Pages - создание страницы
 */

if (!$auth->hasAdminAccess('pages', 'edit')) {
    echo '<div class="admin-alert admin-alert-danger">У вас нет прав для создания страниц.</div>';
    return;
}

$db = Database::getInstance();
$pageManager = new PageManager();

$error = '';
$success = '';

// Получаем список шаблонов из манифеста
$manifestFile = ROOT_DIR . '/templates/pages/_manifest.php';
if (file_exists($manifestFile)) {
    $templates = require $manifestFile;
} else {
    $templates = ['default' => 'Стандартный'];
}

// Получаем список PHP-файлов страниц из /modules/
function scanPageFiles($dir, $base = '') {
    $result = [];
    if (!is_dir($dir)) return $result;
    
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . '/' . $item;
        $relativePath = $base . ($base ? '/' : '') . $item;
        
        if (is_dir($path)) {
            $result = array_merge($result, scanPageFiles($path, $relativePath));
        } elseif ($item === 'index.php' || pathinfo($item, PATHINFO_EXTENSION) === 'php') {
            $result[] = $relativePath;
        }
    }
    return $result;
}

$pageFiles = scanPageFiles(MODULES_DIR);

// Получаем список групп для выбора
$allGroups = $db->fetchAll("SELECT id, group_name, group_type FROM user_groups ORDER BY group_type, group_name");

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $pageFile = trim($_POST['page_file'] ?? '');
    $template = $_POST['template'] ?? 'default';
    $metaTitle = trim($_POST['meta_title'] ?? '');
    $metaDescription = trim($_POST['meta_description'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $sortOrder = (int)($_POST['sort_order'] ?? 0);
    
    // Обработка access_groups
    $accessGroups = null;
    if (isset($_POST['access_groups']) && is_array($_POST['access_groups'])) {
        if (in_array('all', $_POST['access_groups'])) {
            $accessGroups = null;
        } else {
            $groups = array_filter($_POST['access_groups'], function($val) {
                return $val !== '';
            });
            $accessGroups = !empty($groups) ? json_encode($groups) : null;
        }
    }
    
    // Валидация
    if (empty($title)) {
        $error = 'Заголовок страницы обязателен';
    } elseif (empty($pageFile)) {
        $error = 'Выберите PHP-файл страницы';
    } else {
        if (empty($slug)) {
            $slug = $pageManager->generateSlug($title);
        } else {
            if ($pageManager->slugExists($slug)) {
                $error = 'Страница с таким URL уже существует';
            }
        }
        
        if (empty($error)) {
            $data = [
                'title' => $title,
                'slug' => $slug,
                'page_file' => $pageFile,
                'template' => $template,
                'meta_title' => $metaTitle,
                'meta_description' => $metaDescription,
                'is_active' => $isActive,
                'sort_order' => $sortOrder,
                'access_groups' => $accessGroups,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $pageManager->create($data);
            
            $user = $auth->getUser();
            $db->insert('audit_log', [
                'user_id' => $user['id'],
                'action' => 'page_create',
                'module' => 'pages',
                'description' => "Создана страница {$title} (slug: {$slug}, file: {$pageFile})",
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $success = "Страница {$title} успешно создана!";
            $_POST = [];
        }
    }
}
?>

<style>
.module-pages .module-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 12px;
}

.module-pages .module-header h2 {
    font-size: 22px;
    font-weight: 600;
    color: var(--admin-text-primary);
    margin: 0;
}

.admin-form .row {
    margin-bottom: 8px;
}

.admin-form .form-hint {
    color: var(--admin-text-muted);
    font-size: 12px;
    margin-top: 4px;
}

.groups-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 8px;
}

@media (max-width: 768px) {
    .module-pages .module-header {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
    }
}
</style>

<div class="module-pages">
    <div class="module-header">
        <h2>
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 8px;">
                <path d="M7 20h10a2 2 0 002-2V6a2 2 0 00-2-2H7a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                <path d="M7 4h6v4H7z"/>
            </svg>
            Добавление страницы
        </h2>
        <a href="?route=pages" class="btn-admin btn-admin-secondary">← Назад к списку</a>
    </div>
    
    <?php if ($error): ?>
        <div class="admin-alert admin-alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="admin-alert admin-alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    
    <div class="admin-card">
        <form method="POST" class="admin-form" id="pageForm">
            <div class="row">
                <div class="col-xs-12 col-md-6">
                    <div class="admin-form-group">
                        <label for="title">Заголовок страницы *</label>
                        <input type="text" id="title" name="title" class="admin-form-control" 
                               value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" 
                               placeholder="О компании" required>
                    </div>
                </div>
                
                <div class="col-xs-12 col-md-6">
                    <div class="admin-form-group">
                        <label for="slug">URL (адрес страницы)</label>
                        <input type="text" id="slug" name="slug" class="admin-form-control" 
                               value="<?= htmlspecialchars($_POST['slug'] ?? '') ?>" 
                               placeholder="about">
                        <div class="form-hint">
                            Если оставить пустым, будет сгенерирован из заголовка.
                            Используйте латинские буквы, цифры и дефисы.
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-xs-12 col-md-6">
                    <div class="admin-form-group">
                        <label for="page_file">PHP-файл страницы *</label>
                        <select id="page_file" name="page_file" class="admin-form-control" required>
                            <option value="">— Выберите файл —</option>
                            <?php foreach ($pageFiles as $file): ?>
                                <option value="<?= htmlspecialchars($file) ?>" <?= ($_POST['page_file'] ?? '') === $file ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($file) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-hint">
                            Файл должен находиться в папке /modules/. 
                            Например: about/index.php
                        </div>
                    </div>
                </div>
                
                <div class="col-xs-12 col-md-6">
                    <div class="admin-form-group">
                        <label for="template">Шаблон страницы</label>
                        <select id="template" name="template" class="admin-form-control">
                            <?php foreach ($templates as $key => $label): ?>
                                <option value="<?= $key ?>" <?= ($_POST['template'] ?? 'default') == $key ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-hint">Шаблон — это обёртка, в которую вставляется содержимое страницы.</div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-xs-12 col-md-6">
                    <div class="admin-form-group">
                        <label for="meta_title">SEO-заголовок</label>
                        <input type="text" id="meta_title" name="meta_title" class="admin-form-control" 
                               value="<?= htmlspecialchars($_POST['meta_title'] ?? '') ?>" 
                               placeholder="Заголовок для поисковиков">
                        <div class="form-hint">Если не указан, используется заголовок страницы.</div>
                    </div>
                </div>
                
                <div class="col-xs-12 col-md-6">
                    <div class="admin-form-group">
                        <label for="meta_description">SEO-описание</label>
                        <textarea id="meta_description" name="meta_description" class="admin-form-control" rows="2" 
                                  placeholder="Краткое описание для поисковиков"><?= htmlspecialchars($_POST['meta_description'] ?? '') ?></textarea>
                        <div class="form-hint">Рекомендуемая длина: 150-160 символов.</div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-xs-12 col-md-6">
                    <div class="admin-form-group">
                        <label for="sort_order">Порядок сортировки</label>
                        <input type="number" id="sort_order" name="sort_order" class="admin-form-control" 
                               value="<?= htmlspecialchars($_POST['sort_order'] ?? 0) ?>">
                    </div>
                </div>
                
                <div class="col-xs-12 col-md-6">
                    <div class="admin-form-group" style="padding-top: 24px;">
                        <label class="admin-checkbox">
                            <input type="checkbox" name="is_active" value="1" <?= isset($_POST['is_active']) ? 'checked' : 'checked' ?>>
                            <span class="checkmark"></span>
                            Активна (доступна на сайте)
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Доступ для групп -->
            <div class="admin-form-group">
                <label>Доступ для групп</label>
                <div class="groups-grid">
                    <label class="admin-checkbox">
                        <input type="checkbox" class="group-checkbox" value="all" 
                               <?= !isset($_POST['access_groups']) || in_array('all', $_POST['access_groups']) ? 'checked' : '' ?>>
                        <span class="checkmark"></span>
                        🌍 Все (публичный)
                    </label>
                    
                    <?php foreach ($allGroups as $group): ?>
                        <label class="admin-checkbox">
                            <input type="checkbox" name="access_groups[]" value="<?= $group['id'] ?>"
                                <?= isset($_POST['access_groups']) && is_array($_POST['access_groups']) && in_array($group['id'], $_POST['access_groups']) ? 'checked' : '' ?>>
                            <span class="checkmark"></span>
                            <?= htmlspecialchars($group['group_name']) ?> (<?= $group['group_type'] ?>)
                        </label>
                    <?php endforeach; ?>
                </div>
                <div class="form-hint">
                    <strong>🌍 Все (публичный):</strong> видят все (включая гостей)<br>
                    <strong>Выбраны конкретные группы:</strong> видят только пользователи из этих групп<br>
                    <strong>Ничего не выбрано:</strong> видят только авторизованные пользователи (любые группы)
                </div>
            </div>
            
            <div class="admin-form-group">
                <button type="submit" class="btn-admin btn-admin-success">Создать страницу</button>
                <a href="?route=pages" class="btn-admin btn-admin-secondary">Отмена</a>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    // Логика для чекбокса "Все (публичный)"
    $('.group-checkbox').on('change', function() {
        var $this = $(this);
        var isAllChecked = $this.prop('checked');
        
        if (isAllChecked) {
            $('input[name="access_groups[]"]').prop('checked', false);
        }
    });
    
    $('input[name="access_groups[]"]').on('change', function() {
        var anyChecked = $('input[name="access_groups[]"]:checked').length > 0;
        if (anyChecked) {
            $('.group-checkbox').prop('checked', false);
        }
    });
});
</script>