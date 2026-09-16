<?php
/**
 * Модуль Menu - редактирование пункта меню
 */

if (!$auth->hasAdminAccess('menu', 'edit')) {
    echo '<div class="admin-alert admin-alert-danger">У вас нет прав для редактирования пунктов меню.</div>';
    return;
}

$db = Database::getInstance();
$menuBuilder = new MenuBuilder();
$moduleManager = new ModuleManager();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo '<div class="admin-alert admin-alert-danger">Не указан ID пункта меню.</div>';
    return;
}

$item = $menuBuilder->getItem($id);

if (!$item) {
    echo '<div class="admin-alert admin-alert-danger">Пункт меню не найден.</div>';
    return;
}

$allGroups = $db->fetchAll("SELECT id, group_name, group_type FROM user_groups ORDER BY group_type, group_name");
$parentOptions = $menuBuilder->getParentOptions($id);

$frontendModules = $moduleManager->getFrontendModules(true);

$linkType = $item['link_type'] ?? 'module';

// Определяем текущие значения
$currentModuleSlug = null;
$currentExternalLink = null;

if ($linkType === 'external') {
    $currentExternalLink = $item['module_link'];
} elseif ($linkType === 'module') {
    $currentModuleSlug = trim($item['module_link'], '/');
}

$selectedGroups = [];
if (!empty($item['access_groups'])) {
    $selectedGroups = json_decode($item['access_groups'], true);
    if (!is_array($selectedGroups)) {
        $selectedGroups = [];
    }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $menuTitle = trim($_POST['menu_title'] ?? '');
    $linkType = $_POST['link_type'] ?? 'module';
    $moduleLink = trim($_POST['module_link'] ?? '');
    
    // Правильная обработка parent_id
    $parentId = null;
    if (isset($_POST['parent_id']) && $_POST['parent_id'] !== '' && $_POST['parent_id'] !== '0') {
        $parentId = (int)$_POST['parent_id'];
        
        // ЗАЩИТА: нельзя сделать пункт родителем самого себя
        if ($parentId === $id) {
            $error = 'Нельзя сделать пункт родителем самого себя';
            $parentId = $item['parent_id']; // откатываем
        }
    }
    
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
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
    
    if (empty($menuTitle)) {
        $error = 'Название пункта обязательно';
    } elseif ($linkType === 'module' && empty($moduleLink)) {
        $error = 'Выберите модуль';
    } elseif ($linkType === 'external' && empty($moduleLink)) {
        $error = 'Введите внешнюю ссылку';
    } else {
        $finalLink = '';
        
        if ($linkType === 'module') {
            $module = $moduleManager->getModuleBySlug($moduleLink);
            if (!$module) {
                $error = 'Модуль не найден';
            } else {
                $finalLink = '/' . $module['slug'];
            }
        } elseif ($linkType === 'external') {
            $finalLink = $moduleLink;
        } else {
            $finalLink = 'javascript:void(0)';
        }
        
        if (empty($error)) {
            $menuBuilder->update($id, [
                'menu_title' => $menuTitle,
                'module_link' => $finalLink,
                'link_type' => $linkType,
                'parent_id' => $parentId,
                'is_active' => $isActive,
                'access_groups' => $accessGroups,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            $user = $auth->getUser();
            $db->insert('audit_log', [
                'user_id' => $user['id'],
                'action' => 'menu_edit',
                'module' => 'menu',
                'description' => "Отредактирован пункт меню {$menuTitle}" . ($parentId ? " (родитель: {$parentId})" : " (корневой)"),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $success = "Пункт меню «{$menuTitle}» успешно обновлён!";
            
            // Обновляем данные
            $item = $menuBuilder->getItem($id);
            $linkType = $item['link_type'];
            $currentModuleSlug = null;
            $currentExternalLink = null;
            
            if ($linkType === 'external') {
                $currentExternalLink = $item['module_link'];
            } elseif ($linkType === 'module') {
                $currentModuleSlug = trim($item['module_link'], '/');
            }
            
            $selectedGroups = [];
            if (!empty($item['access_groups'])) {
                $selectedGroups = json_decode($item['access_groups'], true);
                if (!is_array($selectedGroups)) {
                    $selectedGroups = [];
                }
            }
        }
    }
}
?>

<style>
.module-menu .module-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 12px;
}
.module-menu .module-header h2 {
    font-size: 22px;
    font-weight: 600;
    color: var(--admin-text-primary);
    margin: 0;
}
.admin-form .row { margin-bottom: 8px; }
.admin-form .form-hint { color: var(--admin-text-muted); font-size: 12px; margin-top: 4px; }
.groups-grid { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 8px; }
@media (max-width: 768px) {
    .module-menu .module-header {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
    }
}
</style>

<div class="module-menu">
    <div class="module-header">
        <h2>✏️ Редактирование пункта меню</h2>
        <a href="?route=menu" class="btn-admin btn-admin-secondary">← Назад к списку</a>
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
                        <label for="menu_title">Название пункта *</label>
                        <input type="text" id="menu_title" name="menu_title" class="admin-form-control" 
                               value="<?= htmlspecialchars($item['menu_title']) ?>" required>
                    </div>
                </div>
                
                <div class="col-xs-12 col-md-6">
                    <div class="admin-form-group">
                        <label for="link_type">Тип ссылки</label>
                        <select id="link_type" name="link_type" class="admin-form-control">
                            <option value="module" <?= $linkType === 'module' ? 'selected' : '' ?>>📦 Модуль (страница сайта)</option>
                            <option value="external" <?= $linkType === 'external' ? 'selected' : '' ?>>🔗 Внешняя ссылка</option>
                            <option value="none" <?= $linkType === 'none' ? 'selected' : '' ?>>📁 Без ссылки (только группировка подпунктов)</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="row" id="module_select_wrapper">
                <div class="col-xs-12">
                    <div class="admin-form-group">
                        <label for="module_link_module">Модуль *</label>
                        <select id="module_link_module" class="admin-form-control">
                            <option value="">— Выберите модуль —</option>
                            <?php foreach ($frontendModules as $name => $m): ?>
                                <option value="<?= $m['slug'] ?>" <?= $currentModuleSlug === $m['slug'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($m['title'] ?: $name) ?> (<?= htmlspecialchars($m['slug']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="row" id="external_link_wrapper" style="display: none;">
                <div class="col-xs-12">
                    <div class="admin-form-group">
                        <label for="module_link_external">Внешняя ссылка *</label>
                        <input type="text" id="module_link_external" class="admin-form-control" 
                               value="<?= htmlspecialchars($currentExternalLink ?? '') ?>" 
                               placeholder="https://example.com">
                    </div>
                </div>
            </div>
            
            <div class="row" id="none_link_wrapper" style="display: none;">
                <div class="col-xs-12">
                    <div class="admin-alert admin-alert-info" style="margin: 0;">
                        ℹ️ Этот пункт используется <strong>только для группировки подпунктов</strong>.<br>
                        Ссылка не ведёт на страницу.
                    </div>
                </div>
            </div>
            
            <input type="hidden" name="module_link" id="module_link" value="<?= htmlspecialchars($item['module_link']) ?>">
            
            <div class="row">
                <div class="col-xs-12 col-md-6">
                    <div class="admin-form-group">
                        <label for="parent_id">Родительский пункт</label>
                        <select id="parent_id" name="parent_id" class="admin-form-control">
                            <option value="">— Корневой (без родителя) —</option>
                            <?php foreach ($parentOptions as $pid => $title): ?>
                                <option value="<?= (int)$pid ?>" <?= $item['parent_id'] !== null && (int)$item['parent_id'] === (int)$pid ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($title) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-hint">
                            Текущий родитель: 
                            <strong>
                                <?php if ($item['parent_id'] === null): ?>
                                    нет (корневой)
                                <?php else: ?>
                                    ID <?= (int)$item['parent_id'] ?>
                                <?php endif; ?>
                            </strong>
                        </div>
                    </div>
                </div>
                
                <div class="col-xs-12 col-md-6">
                    <div class="admin-form-group" style="padding-top: 24px;">
                        <label class="admin-checkbox">
                            <input type="checkbox" name="is_active" value="1" <?= $item['is_active'] ? 'checked' : '' ?>>
                            <span class="checkmark"></span>
                            Активен
                        </label>
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
            </div>
            
            <div class="admin-form-group">
                <button type="submit" class="btn-admin btn-admin-success">Сохранить</button>
                <a href="?route=menu" class="btn-admin btn-admin-secondary">Отмена</a>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    function updateLinkType() {
        var type = $('#link_type').val();
        
        $('#module_select_wrapper').hide();
        $('#external_link_wrapper').hide();
        $('#none_link_wrapper').hide();
        
        if (type === 'module') {
            $('#module_select_wrapper').show();
            $('#module_link').val($('#module_link_module').val());
        } else if (type === 'external') {
            $('#external_link_wrapper').show();
            $('#module_link').val($('#module_link_external').val());
        } else {
            $('#none_link_wrapper').show();
            $('#module_link').val('javascript:void(0)');
        }
    }
    
    $('#link_type').on('change', updateLinkType);
    $('#module_link_module').on('change', function() {
        $('#module_link').val($(this).val());
    });
    $('#module_link_external').on('input', function() {
        $('#module_link').val($(this).val());
    });
    
    updateLinkType();
    
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