<?php
/**
 * API для модуля Galleries
 * Обрабатывает: delete, add_image, delete_image, update_image, sort
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

// Проверка авторизации
if (!$auth->isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Не авторизован']);
    exit;
}

// Проверка прав на модуль galleries
if (!$auth->hasAdminAccess('galleries', 'edit')) {
    echo json_encode(['success' => false, 'message' => 'Недостаточно прав']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$galleryManager = new GalleryManager();

switch ($action) {
    
    // ============================================
    // УДАЛЕНИЕ ГАЛЕРЕИ
    // ============================================
    
    case 'delete':
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Не указан ID галереи']);
            exit;
        }
        
        $gallery = $galleryManager->getBasicById($id);
        if (!$gallery) {
            echo json_encode(['success' => false, 'message' => 'Галерея не найдена']);
            exit;
        }
        
        $title = $gallery['title'];
        $result = $galleryManager->delete($id);
        
        if ($result) {
            // Логируем
            $user = $auth->getUser();
            $db->insert('audit_log', [
                'user_id' => $user['id'],
                'action' => 'gallery_delete',
                'module' => 'galleries',
                'description' => "Удалена галерея \"{$title}\" (ID: {$id})",
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Галерея удалена']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Не удалось удалить галерею']);
        }
        break;
    
    // ============================================
    // ОБНОВЛЕНИЕ ГАЛЕРЕИ
    // ============================================
    
    case 'update':
        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
        
        if ($id <= 0 || empty($title)) {
            echo json_encode(['success' => false, 'message' => 'Не указаны обязательные поля']);
            exit;
        }
        
        $galleryManager->update($id, [
            'title' => $title,
            'description' => $description,
            'is_active' => $isActive,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Галерея обновлена']);
        break;
    
    // ============================================
    // ДОБАВЛЕНИЕ ИЗОБРАЖЕНИЯ
    // ============================================
    
    case 'add_image':
        $galleryId = (int)($_POST['gallery_id'] ?? 0);
        
        if ($galleryId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Не указан ID галереи']);
            exit;
        }
        
        if (!isset($_FILES['image'])) {
            echo json_encode(['success' => false, 'message' => 'Файл не передан']);
            exit;
        }
        
        $result = $galleryManager->addImage($galleryId, $_FILES['image'], [
            'alt' => trim($_POST['alt'] ?? ''),
            'caption' => trim($_POST['caption'] ?? '')
        ]);
        
        echo json_encode($result);
        break;
    
    // ============================================
    // УДАЛЕНИЕ ИЗОБРАЖЕНИЯ
    // ============================================
    
    case 'delete_image':
        $imageId = (int)($_POST['image_id'] ?? 0);
        
        if ($imageId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Не указан ID изображения']);
            exit;
        }
        
        $result = $galleryManager->deleteImage($imageId);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Изображение удалено']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Не удалось удалить изображение']);
        }
        break;
    
    // ============================================
    // ОБНОВЛЕНИЕ МЕТАДАННЫХ ИЗОБРАЖЕНИЯ
    // ============================================
    
    case 'update_image':
        $imageId = (int)($_POST['image_id'] ?? 0);
        $alt = trim($_POST['alt'] ?? '');
        $caption = trim($_POST['caption'] ?? '');
        
        if ($imageId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Не указан ID изображения']);
            exit;
        }
        
        $galleryManager->updateImage($imageId, [
            'image_alt' => $alt,
            'image_caption' => $caption
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Изображение обновлено']);
        break;
    
    // ============================================
    // СОРТИРОВКА
    // ============================================
    
    case 'sort':
        $order = $_POST['order'] ?? [];
        
        if (empty($order) || !is_array($order)) {
            echo json_encode(['success' => false, 'message' => 'Нет данных для сортировки']);
            exit;
        }
        
        $galleryManager->sortImages($order);
        
        echo json_encode(['success' => true, 'message' => 'Порядок сохранён']);
        break;
    
    // ============================================
    // НЕИЗВЕСТНОЕ ДЕЙСТВИЕ
    // ============================================
    
    default:
        echo json_encode(['success' => false, 'message' => 'Неизвестное действие: ' . $action]);
}