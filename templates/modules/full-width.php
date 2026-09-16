<?php
/**
 * Шаблон модуля на всю ширину
 */

define('MODULE_RENDERED', true);

require_once LAYOUT_DIR . '/header.php';
?>

<div class="container-fluid">
    <?php
    if ($moduleData['has_css']) {
        $cssUrl = '/modules/' . $module['module_name'] . '/style.css';
        echo '<link rel="stylesheet" href="' . $cssUrl . '">';
    }
    ?>
    
    <div class="module-content-full">
          <?php
			ob_start();
			require_once $moduleData['index'];
			$moduleContent = ob_get_clean();

			$blockRenderer = new BlockRenderer();
			echo $blockRenderer->process($moduleContent);
		?>
    </div>
    
    <?php
    if ($moduleData['has_js']) {
        $jsUrl = '/modules/' . $module['module_name'] . '/script.js';
        echo '<script src="' . $jsUrl . '"></script>';
    }
    ?>
</div>

<?php
require_once LAYOUT_DIR . '/footer.php';