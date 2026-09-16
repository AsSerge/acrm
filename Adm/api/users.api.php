<?php
/**
 * API для модуля Users
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

// Только Супер-админ может управлять пользователями
if (!$auth->canManageUsers()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Недостаточно прав']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$status = isset($_POST['status']) ? (int)$_POST['status'] : 0;

header('Content-Type: application/json');

switch ($action) {
    case 'filter':
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $groupFilter = isset($_GET['group']) ? (int)$_GET['group'] : 0;
        $statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
        
        $whereConditions = [];
        $params = [];
        
        if (!empty($search)) {
            $whereConditions[] = "(u.username LIKE ? OR u.email LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if ($groupFilter > 0) {
            $whereConditions[] = "u.group_id = ?";
            $params[] = $groupFilter;
        }
        
        if ($statusFilter === 'active') {
            $whereConditions[] = "u.is_active = 1";
        } elseif ($statusFilter === 'inactive') {
            $whereConditions[] = "u.is_active = 0";
        }
        
        $whereSQL = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        $sql = "
            SELECT u.*, ug.group_name, ug.group_type 
            FROM users u
            JOIN user_groups ug ON u.group_id = ug.id
            $whereSQL
            ORDER BY u.id DESC
        ";
        
        $users = $db->fetchAll($sql, $params);
        
        if (empty($users)) {
            $html = '<tr><td colspan="8" style="text-align: center; padding: 30px; color: var(--admin-text-muted);">Пользователи не найдены</td></tr>';
            $total = 0;
            $pagination = '';
        } else {
            $html = '';
            foreach ($users as $user) {
                $html .= '<tr data-user-id="' . $user['id'] . '">';
                $html .= '<td>' . $user['id'] . '</td>';
                $html .= '<td><strong>' . htmlspecialchars($user['username']) . '</strong></td>';
                $html .= '<td>' . htmlspecialchars($user['email']) . '</td>';
                $html .= '<td>' . htmlspecialchars($user['group_name']) . '</td>';
                $html .= '<td><span style="font-size: 11px; padding: 2px 8px; border-radius: 10px; background: ' . ($user['group_type'] === 'admin' ? 'rgba(100,181,246,0.2)' : 'rgba(255,167,38,0.2)') . '; color: ' . ($user['group_type'] === 'admin' ? 'var(--admin-accent)' : 'var(--admin-warning)') . ';">' . $user['group_type'] . '</span></td>';
                $html .= '<td><span class="status-' . ($user['is_active'] ? 'active' : 'inactive') . '">' . ($user['is_active'] ? '✅ Активен' : '🔒 Заблокирован') . '</span></td>';
                $html .= '<td>' . ($user['last_login'] ? date('d.m.Y H:i', strtotime($user['last_login'])) : '—') . '</td>';
                $html .= '<td><div class="action-buttons">';
                $html .= '<a href="?route=users&action=edit&id=' . $user['id'] . '" class="btn-admin btn-admin-sm btn-admin-primary" title="Редактировать">✏️</a>';
                $html .= '<button class="btn-admin btn-admin-sm btn-admin-' . ($user['is_active'] ? 'warning' : 'success') . '" data-toggle-user data-id="' . $user['id'] . '" data-status="' . $user['is_active'] . '" title="' . ($user['is_active'] ? 'Заблокировать' : 'Разблокировать') . '">' . ($user['is_active'] ? '🔒' : '🔓') . '</button>';
                $html .= '<button class="btn-admin btn-admin-sm btn-admin-danger" data-delete-user data-id="' . $user['id'] . '" data-username="' . htmlspecialchars($user['username']) . '" title="Удалить">🗑️</button>';
                $html .= '</div></td>';
                $html .= '</tr>';
            }
            $total = count($users);
            $pagination = '';
        }
        
        echo json_encode([
            'success' => true,
            'html' => $html,
            'total' => $total,
            'pagination' => $pagination
        ]);
        break;
    
    case 'toggle':
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Не указан ID пользователя']);
            exit;
        }
        
        $adminUser = $auth->getUser();
        if ($id == $adminUser['id']) {
            echo json_encode(['success' => false, 'message' => 'Нельзя изменить статус самого себя']);
            exit;
        }
        
        $db->update('users', ['is_active' => $status], 'id = ?', [$id]);
        
        $db->insert('audit_log', [
            'user_id' => $adminUser['id'],
            'action' => 'user_toggle',
            'module' => 'users',
            'description' => "Статус пользователя ID {$id} изменён на " . ($status ? 'активен' : 'заблокирован'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Статус пользователя успешно изменён']);
        break;
    
    case 'delete':
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Не указан ID пользователя']);
            exit;
        }
        
        $adminUser = $auth->getUser();
        if ($id == $adminUser['id']) {
            echo json_encode(['success' => false, 'message' => 'Нельзя удалить самого себя']);
            exit;
        }
        
        $user = $db->fetchOne("SELECT username FROM users WHERE id = ?", [$id]);
        $username = $user ? $user['username'] : 'unknown';
        
        $db->delete('users', 'id = ?', [$id]);
        
        $db->insert('audit_log', [
            'user_id' => $adminUser['id'],
            'action' => 'user_delete',
            'module' => 'users',
            'description' => "Удалён пользователь {$username} (ID: {$id})",
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Пользователь успешно удалён']);
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Неизвестное действие']);
}