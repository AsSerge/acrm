<?php
/**
 * Пример модуля фронтенда
 * Доступен по URL: /example или /example/action
 */

$action = $router->getAction();
$param1 = $router->getParam(0);
$param2 = $router->getParam(1);

// Получаем настройки темы из БД
$themeSettings = [
    'card_bg' => '#f8f9fa',
    'card_border' => '#e0e0e0',
    'heading_color' => '#1a1a2e',
    'button_bg' => '#1a1a2e'
];

$settings = db()->fetchAll("SELECT setting_key, setting_value FROM system_settings");
foreach ($settings as $setting) {
    if (strpos($setting['setting_key'], 'theme_') === 0) {
        $key = str_replace('theme_', '', $setting['setting_key']);
        $themeSettings[$key] = $setting['setting_value'];
    }
}

// ==========================================
// ДЕЙСТВИЕ: INFO
// ==========================================
if ($action === 'info') {
    ?>
    <div class="module-example">
        <div class="module-header">
            <h2>📋 Информация о модуле</h2>
        </div>
        
        <div class="module-card">
            <h3>Системная информация</h3>
            <table class="info-table">
                <tr><td><strong>Имя модуля:</strong></td><td>example</td></tr>
                <tr><td><strong>Версия:</strong></td><td>1.0.0</td></tr>
                <tr><td><strong>Тип:</strong></td><td>frontend</td></tr>
                <tr><td><strong>PHP версия:</strong></td><td><?= phpversion() ?></td></tr>
                <tr><td><strong>Время сервера:</strong></td><td><?= date('Y-m-d H:i:s') ?></td></tr>
                <tr><td><strong>Пользователь:</strong></td><td>
                    <?php if ($auth && $auth->isLoggedIn()): ?>
                        <?= htmlspecialchars($auth->getUser()['username']) ?> (группа: <?= $auth->getUser()['group_name'] ?>)
                    <?php else: ?>
                        <span style="color: #6c757d;">Не авторизован</span>
                    <?php endif; ?>
                </td></tr>
            </table>
        </div>
        
        <div class="module-card">
            <h3>Доступные действия</h3>
            <ul class="action-list">
                <li><strong>/example</strong> - Главная страница</li>
                <li><strong>/example/hello/{name}</strong> - Приветствие</li>
                <li><strong>/example/hello/{name}/{param}</strong> - Приветствие с параметром</li>
                <li><strong>/example/info</strong> - Информация</li>
            </ul>
        </div>
        
        <p><a href="/example" class="btn btn-primary">← Вернуться назад</a></p>
    </div>
    <?php
    return;
}

// ==========================================
// ОСНОВНАЯ СТРАНИЦА МОДУЛЯ
// ==========================================
?>

<div class="module-example">
    <div class="module-header">
        <h2>📦 Модуль Example</h2>
        <p class="subtitle">Демонстрация гибкой настройки фронтенда</p>
    </div>
    
    <!-- Информация о маршруте -->
    <div class="module-card" style="background: <?= $themeSettings['card_bg'] ?>; border-color: <?= $themeSettings['card_border'] ?>;">
        <h3>📍 Текущий маршрут</h3>
        <p><strong>Модуль:</strong> <?= $router->getModule() ?></p>
        <p><strong>Действие:</strong> <?= $action ?></p>
        <p><strong>Параметры:</strong> <?= implode(', ', $router->getParams()) ?: 'нет' ?></p>
    </div>
    
    <!-- Приветствие -->
    <?php if ($action === 'hello' && $param1): ?>
        <div class="module-card success">
            <h3>👋 Приветствие</h3>
            <p class="greeting">Привет, <strong><?= htmlspecialchars($param1) ?></strong>!</p>
            <?php if ($param2): ?>
                <p class="greeting-extra">Параметр: <?= htmlspecialchars($param2) ?></p>
            <?php endif; ?>
        </div>
    <?php elseif ($action === 'hello'): ?>
        <div class="module-card warning">
            <h3>⚠️ Не указано имя</h3>
            <p>Используйте: <code>/example/hello/Имя</code></p>
            <p>Пример: <a href="/example/hello/World">/example/hello/World</a></p>
        </div>
    <?php endif; ?>
    
    <!-- Навигация -->
    <div class="module-card">
        <h3>🧭 Навигация</h3>
        <div class="nav-links">
            <a href="/example" class="nav-link">Главная</a>
            <a href="/example/hello/World" class="nav-link">Привет World</a>
            <a href="/example/hello/User" class="nav-link">Привет User</a>
            <a href="/example/hello/Admin/123" class="nav-link">Привет Admin</a>
            <a href="/example/info" class="nav-link">Информация</a>
        </div>
    </div>
    
    <!-- AJAX -->
    <div class="module-card">
        <h3>🔄 AJAX-запрос</h3>
        <button id="example-ajax-btn" class="btn btn-primary">Отправить AJAX</button>
        <span id="example-result" style="margin-left: 15px;"></span>
    </div>
    
    <!-- Настройки темы -->
    <div class="module-card">
        <h3>⚙️ Настройки темы</h3>
        <div class="settings-grid">
            <?php foreach ($themeSettings as $key => $value): ?>
                <div class="setting-item">
                    <span class="setting-label"><?= $key ?>:</span>
                    <span class="setting-value"><?= htmlspecialchars($value) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Сетка -->
    <div class="module-card">
        <h3>📐 Сетка (12 колонок)</h3>
        <div class="row">
            <div class="col-xs-12 col-sm-6 col-md-4">
                <div class="grid-item" style="background: #e3f2fd;">col-4</div>
            </div>
            <div class="col-xs-12 col-sm-6 col-md-4">
                <div class="grid-item" style="background: #e8f5e9;">col-4</div>
            </div>
            <div class="col-xs-12 col-sm-6 col-md-4">
                <div class="grid-item" style="background: #fff3e0;">col-4</div>
            </div>
        </div>
        <div class="row">
            <div class="col-xs-12 col-md-6">
                <div class="grid-item" style="background: #fce4ec;">col-6</div>
            </div>
            <div class="col-xs-12 col-md-6">
                <div class="grid-item" style="background: #f3e5f5;">col-6</div>
            </div>
        </div>
        <div class="row">
            <div class="col-xs-12">
                <div class="grid-item" style="background: #e0f7fa;">col-12 (полная ширина)</div>
            </div>
        </div>
    </div>
</div>