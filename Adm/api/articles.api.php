<?php
/**
 * API для модуля Articles
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

if (!$auth->hasAdminAccess('articles', 'edit')) {
    echo json_encode(['success' => false, 'message' => 'Недостаточно прав']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
$contentManager = new ContentManager();

switch ($action) {
    
    case 'delete':
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Не указан ID']);
            exit;
        }
        
        $article = $contentManager->getById($id);
        if (!$article) {
            echo json_encode(['success' => false, 'message' => 'Статья не найдена']);
            exit;
        }
        
        $contentManager->delete($id);
        
        $user = $auth->getUser();
        $db->insert('audit_log', [
            'user_id' => $user['id'],
            'action' => 'article_delete',
            'module' => 'articles',
            'description' => "Удалена статья \"{$article['title']}\" (ID: {$id})",
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Статья удалена']);
        break;
    
    case 'upload_cover':
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Не указан ID']);
            exit;
        }
        
        if (!isset($_FILES['cover'])) {
            echo json_encode(['success' => false, 'message' => 'Файл не передан']);
            exit;
        }
        
        $result = $contentManager->uploadCover($id, $_FILES['cover']);
        echo json_encode($result);
        break;
    
    case 'delete_cover':
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Не указан ID']);
            exit;
        }
        
        $result = $contentManager->deleteCover($id);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Обложка удалена']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Не удалось удалить обложку']);
        }
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Неизвестное действие: ' . $action]);
}