<?php
/**
 * Модуль главной страницы (home)
 * Доступен по URL: /home или как главная страница по умолчанию
 */

// Получаем настройки сайта из БД
$siteName = SITE_NAME;
$siteDescription = SITE_DESCRIPTION;

// Получаем список активных модулей для отображения
$allModules = $moduleManager->getAllModules();
$frontendModules = [];
foreach ($allModules as $name => $module) {
    if ($module['module_type'] === 'frontend' && $name !== 'home') {
        $frontendModules[$name] = $module;
    }
}
?>

<div class="home-module">
    <!-- Hero-секция -->
    <div class="home-hero">
        <div class="home-hero-content">
            <h1 class="home-title">Добро пожаловать в <?= htmlspecialchars($siteName) ?></h1>
            <p class="home-subtitle"><?= htmlspecialchars($siteDescription) ?></p>
            <p class="home-description">
                Универсальная многопользовательская CMS с модульной архитектурой.<br>
                Система управления контентом с гибкой настройкой и RBAC.
            </p>
            <div class="home-actions">
                <a href="/example" class="btn btn-primary">Попробовать модуль</a>
                <a href="/Adm/login.php" class="btn btn-secondary">Войти в админку</a>
            </div>
        </div>
    </div>
    
    <!-- Информационные карточки -->
    <div class="home-features">
        <div class="row">
            <div class="col-xs-12 col-sm-6 col-md-4">
                <div class="feature-card">
                    <div class="feature-icon">📦</div>
                    <h3>Модульная архитектура</h3>
                    <p>Каждый модуль — независимая единица. Легко добавлять, удалять и обновлять.</p>
                </div>
            </div>
            <div class="col-xs-12 col-sm-6 col-md-4">
                <div class="feature-card">
                    <div class="feature-icon">🔐</div>
                    <h3>RBAC доступ</h3>
                    <p>Гибкая система управления доступом с группами пользователей и правами на модули.</p>
                </div>
            </div>
            <div class="col-xs-12 col-sm-6 col-md-4">
                <div class="feature-card">
                    <div class="feature-icon">📱</div>
                    <h3>Mobile First</h3>
                    <p>Адаптивный дизайн с приоритетом мобильных устройств. Гамбургер-меню и резиновая сетка.</p>
                </div>
            </div>
            <div class="col-xs-12 col-sm-6 col-md-4">
                <div class="feature-card">
                    <div class="feature-icon">⚡</div>
                    <h3>Быстрая загрузка</h3>
                    <p>Оптимизация запросов, кэширование и ленивая загрузка изображений.</p>
                </div>
            </div>
            <div class="col-xs-12 col-sm-6 col-md-4">
                <div class="feature-card">
                    <div class="feature-icon">🛡️</div>
                    <h3>Безопасность</h3>
                    <p>Защита от XSS, CSRF, SQL-инъекций. Логирование действий пользователей.</p>
                </div>
            </div>
            <div class="col-xs-12 col-sm-6 col-md-4">
                <div class="feature-card">
                    <div class="feature-icon">🎨</div>
                    <h3>Гибкая настройка</h3>
                    <p>Легко менять внешний вид через CSS-переменные. Шрифты и цвета под вашим контролем.</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Доступные модули -->
    <?php if (!empty($frontendModules)): ?>
    <div class="home-modules">
        <h2>Доступные модули</h2>
        <div class="modules-grid">
            <?php foreach ($frontendModules as $name => $module): ?>
                <a href="/<?= $name ?>" class="module-link">
                    <div class="module-link-card">
                        <span class="module-link-icon">📁</span>
                        <span class="module-link-name"><?= $name ?></span>
                        <span class="module-link-version">v<?= $module['version'] ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Техническая информация -->
    <div class="home-tech">
        <div class="tech-info">
            <h3>Техническая информация</h3>
            <div class="tech-grid">
                <div class="tech-item">
                    <span class="tech-label">PHP версия</span>
                    <span class="tech-value"><?= phpversion() ?></span>
                </div>
                <div class="tech-item">
                    <span class="tech-label">Время сервера</span>
                    <span class="tech-value"><?= date('Y-m-d H:i:s') ?></span>
                </div>
                <div class="tech-item">
                    <span class="tech-label">Активных модулей</span>
                    <span class="tech-value"><?= count($frontendModules) + 1 ?></span>
                </div>
                <div class="tech-item">
                    <span class="tech-label">Статус</span>
                    <span class="tech-value status-online">● Online</span>
                </div>
            </div>
        </div>
    </div>
</div>