<?php
/**
 * API для модуля Blocks
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

if (!$auth->hasAdminAccess('blocks', 'edit')) {
    echo json_encode(['success' => false, 'message' => 'Недостаточно прав']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$status = isset($_POST['status']) ? (int)$_POST['status'] : 0;
$name = trim($_POST['name'] ?? '');

$blockManager = new BlockManager();

switch ($action) {
    case 'scan':
        $result = $blockManager->scanBlocks();
        
        $message = 'Сканирование завершено. ';
        if ($result['added'] > 0) {
            $message .= 'Добавлено: ' . $result['added'] . '. ';
        }
        if ($result['deleted'] > 0) {
            $message .= 'Удалено мёртвых: ' . $result['deleted'] . '. ';
        }
        $message .= 'Всего найдено: ' . $result['total'] . '.';
        
        $user = $auth->getUser();
        $db->insert('audit_log', [
            'user_id' => $user['id'],
            'action' => 'blocks_scan',
            'module' => 'blocks',
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
        
        $block = $db->fetchOne("SELECT block_name, title FROM blocks_registry WHERE id = ?", [$id]);
        if (!$block) {
            echo json_encode(['success' => false, 'message' => 'Блок не найден']);
            exit;
        }
        
        $db->update('blocks_registry', ['is_active' => $status], 'id = ?', [$id]);
        
        $user = $auth->getUser();
        $db->insert('audit_log', [
            'user_id' => $user['id'],
            'action' => 'block_toggle',
            'module' => 'blocks',
            'description' => "Статус блока {$block['block_name']} изменён на " . ($status ? 'активен' : 'скрыт'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Статус блока изменён']);
        break;
    
    case 'delete':
        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Не указано имя блока']);
            exit;
        }
        
        $block = $blockManager->getBlock($name);
        if (!$block) {
            echo json_encode(['success' => false, 'message' => 'Блок не найден']);
            exit;
        }
        
        $result = $blockManager->deleteBlock($name);
        
        if ($result['success']) {
            $user = $auth->getUser();
            $db->insert('audit_log', [
                'user_id' => $user['id'],
                'action' => 'block_delete',
                'module' => 'blocks',
                'description' => "Удалён блок {$name} (папка + запись)",
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
        
        echo json_encode($result);
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Неизвестное действие']);
}