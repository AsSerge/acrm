<?php
/**
 * Модуль Modules - редактирование метаданных
 */

if (!$auth->hasAdminAccess('modules', 'edit')) {
    echo '<div class="admin-alert admin-alert-danger">У вас нет прав для редактирования модулей.</div>';
    return;
}

$db = Database::getInstance();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo '<div class="admin-alert admin-alert-danger">Не указан ID модуля.</div>';
    return;
}

$module = $db->fetchOne("SELECT * FROM modules_registry WHERE id = ?", [$id]);

if (!$module) {
    echo '<div class="admin-alert admin-alert-danger">Модуль не найден.</div>';
    return;
}

// Получаем список шаблонов из манифеста
$manifestFile = ROOT_DIR . '/templates/modules/_manifest.php';
$templates = file_exists($manifestFile) ? require $manifestFile : ['default' => 'Стандартный'];

// Группы для доступа
$allGroups = $db->fetchAll("SELECT id, group_name, group_type FROM user_groups ORDER BY group_type, group_name");

// Разбираем access_groups
$selectedGroups = [];
if (!empty($module['access_groups'])) {
    $selectedGroups = json_decode($module['access_groups'], true);
    if (!is_array($selectedGroups)) {
        $selectedGroups = [];
    }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $metaTitle = trim($_POST['meta_title'] ?? '');
    $metaDescription = trim($_POST['meta_description'] ?? '');
    $template = $_POST['template'] ?? 'default';
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $isFrontend = isset($_POST['is_frontend']) ? 1 : 0;
    
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
    
    if (empty($title)) {
        $error = 'Название модуля обязательно';
    } elseif (empty($slug)) {
        $error = 'Slug обязателен';
    } else {
        // Проверка уникальности slug
        $exists = $db->fetchOne(
            "SELECT id FROM modules_registry WHERE slug = ? AND id != ?",
            [$slug, $id]
        );
        
        if ($exists) {
            $error = 'Модуль с таким slug уже существует';
        } else {
            $db->update('modules_registry', [
                'title' => $title,
                'slug' => $slug,
                'meta_title' => $metaTitle,
                'meta_description' => $metaDescription,
                'template' => $template,
                'is_active' => $isActive,
                'is_frontend' => $isFrontend,
                'access_groups' => $accessGroups,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$id]);
            
            $user = $auth->getUser();
            $db->insert('audit_log', [
                'user_id' => $user['id'],
                'action' => 'module_edit',
                'module' => 'modules',
                'description' => "Отредактирован модуль {$module['module_name']}",
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $success = "Модуль успешно обновлён!";
            $module = $db->fetchOne("SELECT * FROM modules_registry WHERE id = ?", [$id]);
            
            $selectedGroups = [];
            if (!empty($module['access_groups'])) {
                $selectedGroups = json_decode($module['access_groups'], true);
                if (!is_array($selectedGroups)) {
                    $selectedGroups = [];
                }
            }
        }
    }
}
?>

<style>
.module-modules .module-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 12px;
}

.module-modules .module-header h2 {
    font-size: 22px;
    font-weight: 600;
    color: var(--admin-text-primary);
    margin: 0;
}

.admin-form .row { margin-bottom: 8px; }
.admin-form .form-hint { color: var(--admin-text-muted); font-size: 12px; margin-top: 4px; }
.groups-grid { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 8px; }

@media (max-width: 768px) {
    .module-modules .module-header {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
    }
}
</style>

<div class="module-modules">
    <div class="module-header">
        <h2>✏️ Редактирование модуля</h2>
        <a href="?route=modules" class="btn-admin btn-admin-secondary">← Назад к списку</a>
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
                        <label>Имя модуля (папка)</label>
                        <input type="text" class="admin-form-control" value="<?= htmlspecialchars($module['module_name']) ?>" disabled>
                        <div class="form-hint">Имя папки в /modules/. Изменение недоступно.</div>
                    </div>
                </div>
                
                <div class="col-xs-12 col-md-6">
                    <div class="admin-form-group">
                        <label>Путь</label>
                        <input type="text" class="admin-form-control" value="<?= htmlspecialchars($module['module_path']) ?>" disabled>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-xs-12 col-md-6">
                    <div class="admin-form-group">
                        <label for="title">Название модуля *</label>
                        <input type="text" id="title" name="title" class="admin-form-control" 
                               value="<?= htmlspecialchars($module['title']) ?>" required>
                    </div>
                </div>
                
                <div class="col-xs-12 col-md-6">
                    <div class="admin-form-group">
                        <label for="slug">Slug (URL) *</label>
                        <input type="text" id="slug" name="slug" class="admin-form-control" 
                               value="<?= htmlspecialchars($module['slug']) ?>" required>
                        <div class="form-hint">URL модуля на сайте (например: about, contacts)</div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-xs-12 col-md-6">
                    <div class="admin-form-group">
                        <label for="template">Шаблон *</label>
                        <select id="template" name="template" class="admin-form-control">
                            <?php foreach ($templates as $key => $label): ?>
                                <option value="<?= $key ?>" <?= $module['template'] == $key ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="col-xs-12 col-md-6">
                    <div class="admin-form-group" style="padding-top: 24px;">
                        <label class="admin-checkbox" style="margin-bottom: 8px;">
                            <input type="checkbox" name="is_active" value="1" <?= $module['is_active'] ? 'checked' : '' ?>>
                            <span class="checkmark"></span>
                            Активен
                        </label>
                        
                        <label class="admin-checkbox">
                            <input type="checkbox" name="is_frontend" value="1" <?= $module['is_frontend'] ? 'checked' : '' ?>>
                            <span class="checkmark"></span>
                            Фронтенд-модуль (доступен на сайте)
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-xs-12 col-md-6">
                    <div class="admin-form-group">
                        <label for="meta_title">SEO-заголовок</label>
                        <input type="text" id="meta_title" name="meta_title" class="admin-form-control" 
                               value="<?= htmlspecialchars($module['meta_title'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="col-xs-12 col-md-6">
                    <div class="admin-form-group">
                        <label for="meta_description">SEO-описание</label>
                        <textarea id="meta_description" name="meta_description" class="admin-form-control" rows="2"><?= htmlspecialchars($module['meta_description'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
            
            <div class="admin-form-group">
                <label>Доступ для групп</label>
                <div class="groups-grid">
                    <label class="admin-checkbox">
                        <input type="checkbox" class="group-checkbox" value="all" 
                               <?= empty($selectedGroups) ? 'checked' : '' ?>>
                        <span class="checkmark"></span>
                        🌍 Все (публичный)
                    </label>
                    
                    <?php foreach ($allGroups as $group): ?>
                        <label class="admin-checkbox">
                            <input type="checkbox" name="access_groups[]" value="<?= $group['id'] ?>"
                                <?= in_array($group['id'], $selectedGroups) ? 'checked' : '' ?>>
                            <span class="checkmark"></span>
                            <?= htmlspecialchars($group['group_name']) ?> (<?= $group['group_type'] ?>)
                        </label>
                    <?php endforeach; ?>
                </div>
                <div class="form-hint">
                    <strong>🌍 Все (публичный):</strong> видят все (включая гостей)<br>
                    <strong>Выбраны группы:</strong> только пользователи из этих групп
                </div>
            </div>
            
            <div class="admin-form-group">
                <button type="submit" class="btn-admin btn-admin-success">Сохранить</button>
                <a href="?route=modules" class="btn-admin btn-admin-secondary">Отмена</a>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.group-checkbox').on('change', function() {
        if ($(this).prop('checked')) {
            $('input[name="access_groups[]"]').prop('checked', false);
        }
    });
    
    $('input[name="access_groups[]"]').on('change', function() {
        if ($('input[name="access_groups[]"]:checked').length > 0) {
            $('.group-checkbox').prop('checked', false);
        }
    });
});
</script>