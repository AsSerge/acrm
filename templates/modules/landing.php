<?php
/**
 * Шаблон лендинга (без шапки и подвала)
 */

define('MODULE_RENDERED', true);

// Подключаем только стили
?>
<!DOCTYPE html>
<html lang="<?= DEFAULT_LANGUAGE ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - <?= SITE_NAME ?></title>
    <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
    
    <link rel="stylesheet" href="/assets/css/grid.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    
    <?php
    if ($moduleData['has_css']) {
        echo '<link rel="stylesheet" href="/modules/' . $module['module_name'] . '/style.css">';
    }
    ?>
    
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
</head>
<body class="landing-page">
        <?php
			ob_start();
			require_once $moduleData['index'];
			$moduleContent = ob_get_clean();

			$blockRenderer = new BlockRenderer();
			echo $blockRenderer->process($moduleContent);
		?>
    
    <?php
    if ($moduleData['has_js']) {
        echo '<script src="/modules/' . $module['module_name'] . '/script.js"></script>';
    }
    ?>
</body>
</html>