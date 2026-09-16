<?php
/**
 * Основной конфигурационный файл
 * Пути и настройки системы
 */

// ============================================
// ПУТИ К КАТАЛОГАМ (абсолютные и относительные)
// ============================================

define('ROOT_DIR', dirname(__DIR__));
define('CORE_DIR', ROOT_DIR . '/core');
define('BASE_DIR', ROOT_DIR . '/base');
define('MODULES_DIR', ROOT_DIR . '/modules');
define('ADMIN_DIR', ROOT_DIR . '/Adm');
define('ADMIN_MODULES_DIR', ADMIN_DIR . '/modules');
define('LAYOUT_DIR', ROOT_DIR . '/layout');
define('ASSETS_DIR', ROOT_DIR . '/assets');
define('VENDOR_DIR', ROOT_DIR . '/vendor');
define('STORAGE_DIR', ROOT_DIR . '/storage');

// ============================================
// URL-АДРЕСА
// ============================================

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('SITE_URL', $protocol . '://' . $host);
define('ADMIN_URL', SITE_URL . '/Adm');
define('ASSETS_URL', SITE_URL . '/assets');

// ============================================
// НАСТРОЙКИ БАЗЫ ДАННЫХ
// ============================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'zoomcrm');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_CHARSET', 'utf8mb4');

// ============================================
// СИСТЕМНЫЕ НАСТРОЙКИ
// ============================================

define('SITE_NAME', 'ZoomCRM');
define('SITE_DESCRIPTION', 'Универсальная модульная CMS');
define('DEFAULT_LANGUAGE', 'ru');
define('TIMEZONE', 'Europe/Moscow');

// Безопасность
define('SALT', 'your-unique-salt-string-change-me');
define('SESSION_LIFETIME', 3600 * 24); // 24 часа
define('CSRF_TOKEN_LIFETIME', 3600);

// Режим разработки (true = показывать ошибки)
define('DEBUG_MODE', true);

// ============================================
// НАСТРОЙКИ СЕССИЙ
// ============================================

ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Включить при HTTPS

// ============================================
// НАСТРОЙКИ ОШИБОК
// ============================================

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Установка временной зоны
date_default_timezone_set(TIMEZONE);