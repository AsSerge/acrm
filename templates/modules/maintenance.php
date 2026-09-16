<?php
/**
 * Шаблон модуля для режима обслуживания
 * 
 * Без шапки, меню, подвала.
 * Используется только для страниц-заглушек.
 * 
 * Доступные переменные:
 * $module — данные модуля из modules_registry
 * $moduleData — пути к файлам (index, css, js)
 * $pageTitle, $pageDescription — SEO
 */

define('MODULE_RENDERED', true);

$siteName = Setting::get('site_name', 'ZoomCRM');
$accentColor = Setting::get('accent_color', '#64b5f6');
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(Setting::get('site_language', 'ru')) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? $siteName) ?></title>
    <?php if (!empty($pageDescription)): ?>
        <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
    <?php endif; ?>
    
    <link rel="stylesheet" href="/assets/css/grid.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    
    <?php if ($moduleData['has_css']): ?>
        <link rel="stylesheet" href="/modules/<?= htmlspecialchars($module['module_name']) ?>/style.css">
    <?php endif; ?>
    
    <style>
        :root {
            --color-accent: <?= htmlspecialchars($accentColor) ?>;
            --header-accent: <?= htmlspecialchars($accentColor) ?>;
        }
        
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #2c3e50 100%);
            color: #ffffff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
            font-family: 'FuturaNew', Arial, sans-serif;
        }
        
        a {
            color: var(--color-accent);
        }
    </style>
    
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
</head>
<body>
    <?php
    // Подключаем модуль с обработкой блоков
    ob_start();
    require $moduleData['index'];
    $moduleContent = ob_get_clean();
    
    $blockRenderer = new BlockRenderer();
    echo $blockRenderer->process($moduleContent);
    ?>
    
    <?php if ($moduleData['has_js']): ?>
        <script src="/modules/<?= htmlspecialchars($module['module_name']) ?>/script.js"></script>
    <?php endif; ?>
</body>
</html>