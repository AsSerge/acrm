<?php
/**
 * API для модуля Example
 * Обрабатывает AJAX-запросы
 */

require_once __DIR__ . '/../base/config.php';

// Автозагрузка классов
spl_autoload_register(function ($class) {
    $file = CORE_DIR . '/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Инициализация
$db = Database::getInstance();
$auth = new Auth();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Проверяем, авторизован ли пользователь (если требуется)
// if (!$auth->isLoggedIn()) {
//     echo json_encode(['success' => false, 'message' => 'Не авторизован']);
//     exit;
// }

header('Content-Type: application/json');

switch ($action) {
    case 'test':
        $value = $_POST['value'] ?? '';
        echo json_encode([
            'success' => true,
            'message' => 'Получено: ' . htmlspecialchars($value),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        break;
    
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Неизвестное действие: ' . $action
        ]);
}