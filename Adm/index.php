<?php
/**
 * Главная страница админ-панели
 */

require_once __DIR__ . '/../base/config.php';
require_once __DIR__ . '/classes.php';

$auth = new AdminAuth();
if (!$auth->isAdmin()) {
    header('Location: /Adm/login.php');
    exit;
}

$adminUser = $auth->getUser();
$db = Database::getInstance();

$route = $_GET['route'] ?? 'dashboard';

// Если маршрут dashboard — подключаем модуль dashboard
if ($route === 'dashboard') {
    require_once __DIR__ . '/layout/header.php';
    require_once __DIR__ . '/modules/dashboard/index.php';
    require_once __DIR__ . '/layout/footer.php';
    exit;
}

// Остальная логика маршрутизации
$action = $_GET['action'] ?? 'index';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$modulePath = __DIR__ . '/modules/' . $route . '/';
$moduleFile = $modulePath . $action . '.php';

if (!file_exists($moduleFile)) {
    $moduleFile = $modulePath . 'index.php';
}

if (!file_exists($moduleFile)) {
    require_once __DIR__ . '/layout/header.php';
    echo '<div class="admin-alert admin-alert-danger">';
    echo '<h3>Модуль "' . htmlspecialchars($route) . '" не найден</h3>';
    echo '</div>';
    require_once __DIR__ . '/layout/footer.php';
    exit;
}

require_once __DIR__ . '/layout/header.php';

if (file_exists($modulePath . 'style.css')) {
    echo '<link rel="stylesheet" href="/Adm/modules/' . $route . '/style.css">';
}

require_once $moduleFile;

if (file_exists($modulePath . 'script.js')) {
    echo '<script src="/Adm/modules/' . $route . '/script.js"></script>';
}

require_once __DIR__ . '/layout/footer.php';