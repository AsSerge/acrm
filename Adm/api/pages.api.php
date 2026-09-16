<?php
/**
 * API для модуля Pages
 * Обрабатывает AJAX-запросы: toggle, delete
 */

require_once __DIR__ . '/../../base/config.php';
require_once __DIR__ . '/../classes.php';

// Автозагрузка классов
spl_autoload_register(function ($class) {
    $file = ROOT_DIR . '/core/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Инициализация
$db = Database::getInstance();
$auth = new AdminAuth();

// Проверка авторизации
if (!$auth->isAdmin()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Не авторизован']);
    exit;
}

// Проверка прав на модуль pages
if (!$auth->hasAdminAccess('pages', 'edit')) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Недостаточно прав']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$status = isset($_POST['status']) ? (int)$_POST['status'] : 0;

header('Content-Type: application/json');

switch ($action) {
    case 'toggle':
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Не указан ID страницы']);
            exit;
        }
        
        // Проверяем, существует ли страница
        $page = $db->fetchOne("SELECT id, title FROM pages WHERE id = ?", [$id]);
        if (!$page) {
            echo json_encode(['success' => false, 'message' => 'Страница не найдена']);
            exit;
        }
        
        // Обновляем статус
        $db->update('pages', ['is_active' => $status], 'id = ?', [$id]);
        
        // Логируем
        $adminUser = $auth->getUser();
        $db->insert('audit_log', [
            'user_id' => $adminUser['id'],
            'action' => 'page_toggle',
            'module' => 'pages',
            'description' => "Статус страницы {$page['title']} (ID: {$id}) изменён на " . ($status ? 'активна' : 'скрыта'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Статус страницы успешно изменён'
        ]);
        break;
    
    case 'delete':
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Не указан ID страницы']);
            exit;
        }
        
        // Получаем данные страницы
        $page = $db->fetchOne("SELECT id, title, page_file FROM pages WHERE id = ?", [$id]);
        if (!$page) {
            echo json_encode(['success' => false, 'message' => 'Страница не найдена']);
            exit;
        }
        
        // Проверяем, есть ли пункты меню, привязанные к этой странице
        $menuCount = $db->fetchOne(
            "SELECT COUNT(*) as count FROM menu_structure WHERE page_id = ?",
            [$id]
        )['count'] ?? 0;
        
        if ($menuCount > 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Нельзя удалить страницу, к которой привязаны пункты меню (' . $menuCount . ' шт.)'
            ]);
            exit;
        }
        
        // Удаляем страницу из БД
        $db->delete('pages', 'id = ?', [$id]);
        
        // Логируем
        $adminUser = $auth->getUser();
        $db->insert('audit_log', [
            'user_id' => $adminUser['id'],
            'action' => 'page_delete',
            'module' => 'pages',
            'description' => "Удалена страница {$page['title']} (ID: {$id})",
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Страница успешно удалена'
        ]);
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Неизвестное действие']);
}