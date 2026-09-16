<?php
/**
 * API для модуля Menu
 */

require_once __DIR__ . '/../../base/config.php';
require_once __DIR__ . '/../classes.php';

spl_autoload_register(function ($class) {
    if ($class === 'MenuBuilder') {
        $file = __DIR__ . '/../../core/' . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

$db = Database::getInstance();
$auth = new AdminAuth();

if (!$auth->isAdmin()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Не авторизован']);
    exit;
}

if (!$auth->hasAdminAccess('menu', 'edit')) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Недостаточно прав']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$status = isset($_POST['status']) ? (int)$_POST['status'] : 0;

header('Content-Type: application/json');

$menuBuilder = new MenuBuilder();

switch ($action) {
    case 'toggle':
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Не указан ID пункта меню']);
            exit;
        }
        
        $menuBuilder->update($id, ['is_active' => $status]);
        
        $user = $auth->getUser();
        $db->insert('audit_log', [
            'user_id' => $user['id'],
            'action' => 'menu_toggle',
            'module' => 'menu',
            'description' => "Статус пункта меню ID {$id} изменён на " . ($status ? 'активен' : 'скрыт'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Статус пункта меню изменён']);
        break;
    
    case 'delete':
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Не указан ID пункта меню']);
            exit;
        }
        
        // Проверяем, есть ли дочерние пункты
        if ($menuBuilder->hasChildren($id)) {
            echo json_encode(['success' => false, 'message' => 'Нельзя удалить пункт, у которого есть дочерние элементы']);
            exit;
        }
        
        $item = $menuBuilder->getItem($id);
        $title = $item ? $item['menu_title'] : 'unknown';
        
        $menuBuilder->delete($id);
        
        $user = $auth->getUser();
        $db->insert('audit_log', [
            'user_id' => $user['id'],
            'action' => 'menu_delete',
            'module' => 'menu',
            'description' => "Удалён пункт меню {$title} (ID: {$id})",
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Пункт меню успешно удалён']);
        break;
    
		case 'sort':
			$order = isset($_POST['order']) ? $_POST['order'] : [];
			
			if (empty($order)) {
				echo json_encode(['success' => false, 'message' => 'Нет данных для сортировки']);
				exit;
			}
			
			foreach ($order as $item) {
				$id = (int)$item['id'];
				// Если parent_id = 0 или null, сохраняем как NULL в БД
				$parentId = isset($item['parent_id']) && $item['parent_id'] > 0 ? (int)$item['parent_id'] : null;
				$sortOrder = (int)$item['sort_order'];
				
				$menuBuilder->update($id, [
					'parent_id' => $parentId,
					'sort_order' => $sortOrder
				]);
			}
			
			$user = $auth->getUser();
			$db->insert('audit_log', [
				'user_id' => $user['id'],
				'action' => 'menu_sort',
				'module' => 'menu',
				'description' => "Изменён порядок пунктов меню",
				'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
				'created_at' => date('Y-m-d H:i:s')
			]);
			
			echo json_encode(['success' => true, 'message' => 'Порядок меню сохранён']);
			break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Неизвестное действие']);
}