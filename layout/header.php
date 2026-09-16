<?php
/**
 * Шапка сайта (фронтенд)
 * С поддержкой настроек и SEO
 */

// Загружаем настройки
Setting::load();

// Данные сайта
$siteName = Setting::get('site_name', 'ZoomCRM');
$siteDescription = Setting::get('site_description', '');
$siteLanguage = Setting::get('site_language', 'ru');
$logoText = Setting::get('logo_text', $siteName);
$accentColor = Setting::get('accent_color', '#64b5f6');

// SEO из модуля (передаются из index.php)
$moduleMetaTitle = isset($pageTitle) ? $pageTitle : '';
$moduleMetaDescription = isset($pageDescription) ? $pageDescription : '';

// Fallback для SEO
$seoMetaTitle = Setting::get('seo_meta_title', '');
$seoMetaDescription = Setting::get('seo_meta_description', '');

// Формируем финальный title
if (!empty($moduleMetaTitle) && $moduleMetaTitle !== $siteName) {
    $finalTitle = $moduleMetaTitle . ' — ' . $siteName;
} elseif (!empty($seoMetaTitle)) {
    $finalTitle = $seoMetaTitle;
} else {
    $finalTitle = $siteName;
    if (!empty($siteDescription)) {
        $finalTitle .= ' — ' . $siteDescription;
    }
}

// Формируем финальное description
if (!empty($moduleMetaDescription)) {
    $finalDescription = $moduleMetaDescription;
} elseif (!empty($seoMetaDescription)) {
    $finalDescription = $seoMetaDescription;
} else {
    $finalDescription = $siteDescription;
}

// Меню
$menuItems = [];
try {
    $db = Database::getInstance();
    $menuBuilder = new MenuBuilder();
    $user = isset($auth) ? $auth->getUser() : null;
    $menuItems = $menuBuilder->getTree('frontend', true, $user, false);
} catch (Exception $e) {
    $menuItems = [];
}

function renderFrontendMenu($items, $depth = 0) {
    if (empty($items)) return;
    echo '<ul class="menu-level-' . $depth . '">';
    foreach ($items as $item) {
        $hasChildren = isset($item['children']) && !empty($item['children']);
        $link = htmlspecialchars($item['module_link']);
        $title = htmlspecialchars($item['menu_title']);
        echo '<li' . ($hasChildren ? ' class="has-children"' : '') . '>';
        echo '<a href="' . $link . '">' . $title;
        if ($hasChildren) echo ' <span class="menu-arrow">▾</span>';
        echo '</a>';
        if ($hasChildren) renderFrontendMenu($item['children'], $depth + 1);
        echo '</li>';
    }
    if ($depth === 0) {
        global $auth;
        if ($auth && $auth->isLoggedIn()) {
            echo '<li><a href="/profile.php">Личный кабинет</a></li>';
            echo '<li><a href="/logout.php">Выйти</a></li>';
        } else {
            echo '<li><a href="/login.php">Войти</a></li>';
        }
    }
    echo '</ul>';
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($siteLanguage) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($finalTitle) ?></title>
    <?php if (!empty($finalDescription)): ?>
        <meta name="description" content="<?= htmlspecialchars($finalDescription) ?>">
    <?php endif; ?>
    <?php if (Setting::get('site_keywords')): ?>
        <meta name="keywords" content="<?= htmlspecialchars(Setting::get('site_keywords')) ?>">
    <?php endif; ?>
    
    <!-- Акцентный цвет -->
    <style>
        :root {
            --color-accent: <?= htmlspecialchars($accentColor) ?>;
            --header-accent: <?= htmlspecialchars($accentColor) ?>;
        }
    </style>
    
    <link rel="stylesheet" href="/assets/css/grid.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="/assets/js/script.js"></script>
    <script src="/assets/js/modules-loader.js"></script>
    
    <?php
    // Google Analytics
    $gaId = Setting::get('seo_google_analytics');
    if (!empty($gaId)):
    ?>
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?= htmlspecialchars($gaId) ?>"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '<?= htmlspecialchars($gaId) ?>');
        </script>
    <?php endif; ?>
    
    <?php
    // Яндекс.Метрика
    $ymId = Setting::get('seo_yandex_metrika');
    if (!empty($ymId)):
    ?>
        <script type="text/javascript">
            (function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
            m[i].l=1*new Date();
            for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
            k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})
            (window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym");
            ym(<?= (int)$ymId ?>, "init", {
                clickmap:true, trackLinks:true, accurateTrackBounce:true
            });
        </script>
        <noscript><div><img src="https://mc.yandex.ru/watch/<?= (int)$ymId ?>" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
    <?php endif; ?>

	<?php
		$favicon = Setting::get('favicon');
			if (!empty($favicon)):
			?>
    		<link rel="icon" href="<?= htmlspecialchars($favicon) ?>" type="image/x-icon">
	<?php endif; ?>

</head>
<body>
    <header class="site-header">
        <div class="container">
            <div class="header-inner">
				<div class="logo">
					<a href="/">
						<?php
						$logoImage = Setting::get('logo_image');
						if (!empty($logoImage)):
						?>
							<img src="<?= htmlspecialchars($logoImage) ?>" alt="<?= htmlspecialchars($logoText) ?>" class="logo-image">
						<?php else: ?>
							<h1><?= htmlspecialchars($logoText) ?></h1>
						<?php endif; ?>
					</a>
				</div>
                
                <button class="hamburger" id="hamburger" aria-label="Меню" aria-expanded="false">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                
                <nav class="main-nav" id="mainNav" role="navigation">
                    <?php if (!empty($menuItems)): ?>
                        <?php renderFrontendMenu($menuItems); ?>
                    <?php else: ?>
                        <ul>
                            <li><a href="/">Главная</a></li>
                            <li><a href="/login.php">Войти</a></li>
                        </ul>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </header>
    
    <main class="site-main">
        <div class="container">