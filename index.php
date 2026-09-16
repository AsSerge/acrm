<?php
/**
 * Точка входа фронтенда
 * Роутер: модули + SEO + доступ + шаблоны + режим обслуживания
 */

require_once __DIR__ . '/base/config.php';
require_once __DIR__ . '/classes.php';

spl_autoload_register(function ($class) {
    $file = CORE_DIR . '/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

$db = Database::getInstance();
$auth = new Auth();
$router = new Router();
$moduleManager = new ModuleManager();
$menuBuilder = new MenuBuilder();

// ============================================
// ЗАГРУЖАЕМ НАСТРОЙКИ
// ============================================
Setting::load();

// ============================================
// ПРОВЕРКА РЕЖИМА ОБСЛУЖИВАНИЯ
// ============================================

$maintenanceMode = Setting::getBool('maintenance_mode', false);
$maintenanceModule = Setting::get('maintenance_module', '');
$currentUser = $auth->getUser();

// Если режим включён И пользователь не админ
if ($maintenanceMode && (!$currentUser || $currentUser['group_type'] !== 'admin')) {
    
    // Если модуль выбран — показываем его
    if (!empty($maintenanceModule)) {
        
        // Ищем модуль по slug
        $mmModule = $moduleManager->getModuleBySlug($maintenanceModule);
        
        if ($mmModule) {
            $mmData = $moduleManager->loadModule($mmModule['module_name']);
            
            if ($mmData) {
                // SEO
                $pageTitle = $mmModule['meta_title'] ?: $mmModule['title'];
                $pageDescription = $mmModule['meta_description'] ?: Setting::get('seo_meta_description', '');
                
                // Используем специальный шаблон для режима обслуживания
                $module = $mmModule;
                $moduleData = $mmData;
                
                require_once ROOT_DIR . '/templates/modules/maintenance.php';
                exit;
            }
        }
    }
    
    // Если модуль не выбран или не найден — показываем заглушку
    // ... (дальше без изменений)
    // Если модуль не выбран или не найден — показываем заглушку
    http_response_code(503);
    
    $siteName = Setting::get('site_name', 'ZoomCRM');
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Сайт на обслуживании — <?= htmlspecialchars($siteName) ?></title>
        <link rel="stylesheet" href="/assets/css/grid.css">
        <link rel="stylesheet" href="/assets/css/style.css">
        <style>
            body {
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                margin: 0;
                background: linear-gradient(135deg, #1a1a2e 0%, #2c3e50 100%);
                font-family: 'FuturaNew', Arial, sans-serif;
                color: #fff;
            }
            .maintenance-box {
                text-align: center;
                padding: 60px 40px;
                max-width: 600px;
            }
            .maintenance-icon {
                font-size: 80px;
                margin-bottom: 20px;
            }
            .maintenance-box h1 {
                font-size: 32px;
                font-weight: 600;
                margin: 0 0 16px 0;
                letter-spacing: 1px;
            }
            .maintenance-box p {
                font-size: 16px;
                opacity: 0.8;
                line-height: 1.6;
                margin: 0 0 8px 0;
            }
            .maintenance-box .hint {
                font-size: 13px;
                opacity: 0.5;
                margin-top: 30px;
            }
        </style>
    </head>
    <body>
        <div class="maintenance-box">
            <div class="maintenance-icon">🛠️</div>
            <h1>Сайт на обслуживании</h1>
            <p>Мы проводим технические работы.</p>
            <p>Пожалуйста, зайдите позже.</p>
            <div class="hint">
                <?php if ($currentUser && $currentUser['group_type'] === 'admin'): ?>
                    Вы вошли как администратор — <a href="?bypass=1" style="color: #64b5f6;">обойти режим</a>
                <?php endif; ?>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ============================================
// АВТОМАТИЧЕСКОЕ ОБНАРУЖЕНИЕ МОДУЛЕЙ
// ============================================
$allModules = $moduleManager->getAllModules();
if (empty($allModules)) {
    $moduleManager->scanModules();
    $allModules = $moduleManager->getAllModules();
}

// ============================================
// ОПРЕДЕЛЯЕМ ЗАПРОШЕННЫЙ МОДУЛЬ
// ============================================

$moduleName = $router->getModule();
$action = $router->getAction();
$params = $router->getParams();

// Формируем slug
if (empty($moduleName) || $moduleName === 'home' || $moduleName === '/') {
    $slug = 'home';
} else {
    $slug = $moduleName;
}

// ============================================
// ИЩЕМ МОДУЛЬ В РЕЕСТРЕ
// ============================================

$module = $moduleManager->getModuleBySlug($slug);

// Если модуль не найден — 404
if (!$module || !$module['is_active'] || !$module['is_frontend']) {
    http_response_code(404);
    
    $pageTitle = 'Страница не найдена';
    $pageDescription = 'Запрошенная страница не найдена';
    
    require_once LAYOUT_DIR . '/header.php';
    echo '<div class="container">';
    echo '<div class="alert alert-danger" style="margin-top: 40px; text-align: center;">';
    echo '<h2 style="font-size: 24px; margin-bottom: 12px;">404 — Страница не найдена</h2>';
    echo '<p style="color: var(--color-text-light);">Запрошенная страница не существует или была удалена.</p>';
    echo '<p><a href="/" class="btn btn-primary" style="margin-top: 16px;">Вернуться на главную</a></p>';
    echo '</div>';
    echo '</div>';
    require_once LAYOUT_DIR . '/footer.php';
    exit;
}

// ============================================
// ПРОВЕРКА ДОСТУПА
// ============================================

$user = $auth->getUser();

if (!$moduleManager->hasAccess($module, $user)) {
    http_response_code(403);
    
    $pageTitle = 'Доступ запрещён';
    $pageDescription = 'У вас нет прав для просмотра этой страницы';
    
    require_once LAYOUT_DIR . '/header.php';
    echo '<div class="container">';
    echo '<div class="alert alert-danger" style="margin-top: 40px; text-align: center;">';
    echo '<h2 style="font-size: 24px; margin-bottom: 12px;">⛔ Доступ запрещён</h2>';
    echo '<p style="color: var(--color-text-light);">У вас нет прав для просмотра этой страницы.</p>';
    echo '<p><a href="/" class="btn btn-primary" style="margin-top: 16px;">Вернуться на главную</a></p>';
    echo '</div>';
    echo '</div>';
    require_once LAYOUT_DIR . '/footer.php';
    exit;
}

// ============================================
// ЗАГРУЖАЕМ МОДУЛЬ
// ============================================

$moduleData = $moduleManager->loadModule($module['module_name']);

if (!$moduleData) {
    http_response_code(500);
    
    $pageTitle = 'Ошибка загрузки';
    $pageDescription = 'Не удалось загрузить модуль';
    
    require_once LAYOUT_DIR . '/header.php';
    echo '<div class="container">';
    echo '<div class="alert alert-danger" style="margin-top: 40px; text-align: center;">';
    echo '<h2>Ошибка загрузки модуля</h2>';
    echo '<p>Файл модуля не найден или повреждён.</p>';
    echo '</div>';
    echo '</div>';
    require_once LAYOUT_DIR . '/footer.php';
    exit;
}

// ============================================
// SEO И ЗАГОЛОВКИ
// ============================================

$pageTitle = $module['meta_title'] ?: $module['title'];
$pageDescription = $module['meta_description'] ?: Setting::get('seo_meta_description', '');

// ============================================
// ПОДКЛЮЧАЕМ ШАБЛОН
// ============================================

$templateFile = ROOT_DIR . '/templates/modules/' . ($module['template'] ?: 'default') . '.php';

if (!file_exists($templateFile)) {
    $templateFile = ROOT_DIR . '/templates/modules/default.php';
}

require_once $templateFile;

// ============================================
// ЕСЛИ ШАБЛОН НЕ ЗАГРУЗИЛСЯ — FALLBACK
// ============================================

if (!defined('MODULE_RENDERED')) {
    require_once LAYOUT_DIR . '/header.php';
    
    echo '<div class="container">';
    
    if ($moduleData['has_css']) {
        echo '<link rel="stylesheet" href="/modules/' . $module['module_name'] . '/style.css">';
    }
    
    require_once $moduleData['index'];
    
    if ($moduleData['has_js']) {
        echo '<script src="/modules/' . $module['module_name'] . '/script.js"></script>';
    }
    
    echo '</div>';
    
    require_once LAYOUT_DIR . '/footer.php';
}