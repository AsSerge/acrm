<?php
/**
 * API для модуля Modules
 * Сканирование, toggle, delete
 */

require_once __DIR__ . '/../../base/config.php';
require_once __DIR__ . '/../classes.php';

spl_autoload_register(function ($class) {
    $file = ROOT_DIR . '/core/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

$db = Database::getInstance();
$auth = new AdminAuth();

header('Content-Type: application/json');

if (!$auth->isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Не авторизован']);
    exit;
}

if (!$auth->hasAdminAccess('modules', 'edit')) {
    echo json_encode(['success' => false, 'message' => 'Недостаточно прав']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$status = isset($_POST['status']) ? (int)$_POST['status'] : 0;

$moduleManager = new ModuleManager();

switch ($action) {
    case 'scan':
        $result = $moduleManager->scanModules();
        
        $message = 'Сканирование завершено. ';
        if ($result['added'] > 0) {
            $message .= 'Добавлено: ' . $result['added'] . '. ';
        }
        $message .= 'Всего найдено: ' . $result['total'] . '.';
        
        $user = $auth->getUser();
        $db->insert('audit_log', [
            'user_id' => $user['id'],
            'action' => 'modules_scan',
            'module' => 'modules',
            'description' => $message,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'result' => $result
        ]);
        break;
    
    case 'toggle':
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Не указан ID']);
            exit;
        }
        
        $module = $db->fetchOne("SELECT module_name, title FROM modules_registry WHERE id = ?", [$id]);
        if (!$module) {
            echo json_encode(['success' => false, 'message' => 'Модуль не найден']);
            exit;
        }
        
        $db->update('modules_registry', ['is_active' => $status], 'id = ?', [$id]);
        
        $user = $auth->getUser();
        $db->insert('audit_log', [
            'user_id' => $user['id'],
            'action' => 'module_toggle',
            'module' => 'modules',
            'description' => "Статус модуля {$module['module_name']} изменён на " . ($status ? 'активен' : 'скрыт'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Статус модуля изменён']);
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Неизвестное действие']);
}