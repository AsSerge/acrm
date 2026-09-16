<?php
/**
 * API для модуля Groups
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

if (!$auth->isAdmin()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Не авторизован']);
    exit;
}

// Только Супер-админ может управлять группами
if (!$auth->canManageGroups()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Недостаточно прав']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

header('Content-Type: application/json');

switch ($action) {
    case 'delete':
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Не указан ID группы']);
            exit;
        }
        
        $group = $db->fetchOne("SELECT id, group_name, is_system FROM user_groups WHERE id = ?", [$id]);
        if (!$group) {
            echo json_encode(['success' => false, 'message' => 'Группа не найдена']);
            exit;
        }
        
        if ($group['is_system']) {
            echo json_encode(['success' => false, 'message' => 'Нельзя удалить системную группу']);
            exit;
        }
        
        $userCount = $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE group_id = ?", [$id])['count'] ?? 0;
        if ($userCount > 0) {
            echo json_encode(['success' => false, 'message' => 'Нельзя удалить группу, в которой есть пользователи']);
            exit;
        }
        
        $db->delete('module_access', 'group_id = ?', [$id]);
        $db->delete('user_groups', 'id = ?', [$id]);
        
        $adminUser = $auth->getUser();
        $db->insert('audit_log', [
            'user_id' => $adminUser['id'],
            'action' => 'group_delete',
            'module' => 'groups',
            'description' => "Удалена группа {$group['group_name']} (ID: {$id})",
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Группа успешно удалена']);
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Неизвестное действие']);
}