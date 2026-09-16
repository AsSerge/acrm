<?php
/**
 * Шаблон модуля с сайдбаром
 */

define('MODULE_RENDERED', true);

require_once LAYOUT_DIR . '/header.php';
?>

<div class="container">
    <?php
    if ($moduleData['has_css']) {
        $cssUrl = '/modules/' . $module['module_name'] . '/style.css';
        echo '<link rel="stylesheet" href="' . $cssUrl . '">';
    }
    ?>
    
    <div class="module-with-sidebar">
        <div class="module-main">
        <?php
			ob_start();
			require_once $moduleData['index'];
			$moduleContent = ob_get_clean();

			$blockRenderer = new BlockRenderer();
			echo $blockRenderer->process($moduleContent);
		?>
        </div>
        <aside class="module-sidebar">
            <h3>Боковая панель</h3>
            <p>Здесь может быть что угодно.</p>
        </aside>
    </div>
    
    <?php
    if ($moduleData['has_js']) {
        $jsUrl = '/modules/' . $module['module_name'] . '/script.js';
        echo '<script src="' . $jsUrl . '"></script>';
    }
    ?>
</div>

<style>
.module-with-sidebar {
    display: flex;
    gap: 40px;
    margin: 20px 0;
}
.module-main {
    flex: 2;
}
.module-sidebar {
    flex: 1;
    background: var(--color-bg);
    padding: 20px;
    border-radius: 8px;
}
@media (max-width: 768px) {
    .module-with-sidebar {
        flex-direction: column;
    }
}
</style>

<?php
require_once LAYOUT_DIR . '/footer.php';