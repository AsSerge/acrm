<?php
/**
 * Глобальный фасад для быстрого доступа к ядру
 * Подключается в любой точке системы
 */

// Автозагрузка классов ядра
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/core/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Глобальные функции-хелперы

/**
 * Получение экземпляра Database
 */
function db() {
    return Database::getInstance();
}

/**
 * Получение экземпляра Auth
 */
function auth() {
    static $auth = null;
    if ($auth === null) {
        $auth = new Auth();
    }
    return $auth;
}

/**
 * Получение экземпляра Router
 */
function router() {
    static $router = null;
    if ($router === null) {
        $router = new Router();
    }
    return $router;
}

/**
 * Получение экземпляра ModuleManager
 */
function modules() {
    static $modules = null;
    if ($modules === null) {
        $modules = new ModuleManager();
    }
    return $modules;
}

/**
 * Экранирование вывода (XSS защита)
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Проверка CSRF-токена
 */
function csrf_token() {
    return auth()->generateCsrfToken();
}

/**
 * Генерация CSRF-поля для форм
 */
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

/**
 * Проверка CSRF
 */
function csrf_verify($token) {
    return auth()->verifyCsrfToken($token);
}

/**
 * Редирект
 */
function redirect($url, $permanent = false) {
    if ($permanent) {
        header('HTTP/1.1 301 Moved Permanently');
    }
    header('Location: ' . $url);
    exit;
}

/**
 * JSON-ответ
 */
function json_response($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Dump и die (для отладки)
 */
function dd($data) {
    echo '<pre style="background:#f8f9fa;padding:20px;border:1px solid #ddd;border-radius:4px;font-size:14px;font-family:monospace;">';
    var_dump($data);
    echo '</pre>';
    die();
}

// В конце файла /Adm/classes.php добавь:
spl_autoload_register(function ($class) {
    if ($class === 'MenuBuilder') {
        $file = __DIR__ . '/../core/' . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});