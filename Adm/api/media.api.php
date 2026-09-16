<?php
/**
 * API для модуля Media
 * Обрабатывает: scan, upload, delete
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

// Проверка прав на модуль media
if (!$auth->hasAdminAccess('media', 'edit')) {
    echo json_encode(['success' => false, 'message' => 'Недостаточно прав']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    
    // ============================================
    // СКАНИРОВАНИЕ ПАПКИ
    // ============================================
    
    case 'scan':
        $path = trim($_POST['path'] ?? $_GET['path'] ?? '');
        $path = ltrim($path, '/\\');
        
        // Защита от выхода за пределы /uploads/
        if (strpos($path, '..') !== false) {
            echo json_encode(['success' => false, 'message' => 'Недопустимый путь']);
            exit;
        }
        
        $fullPath = ROOT_DIR . '/uploads/' . ($path ? $path . '/' : '');
        
        if (!is_dir($fullPath)) {
            echo json_encode(['success' => false, 'message' => 'Папка не найдена: ' . $path]);
            exit;
        }
        
        $files = [];
        $items = scandir($fullPath);
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            if ($item[0] === '.') continue; // Скрытые файлы
            
            $itemPath = $fullPath . $item;
            $relativePath = $path ? $path . '/' . $item : $item;
            
            if (is_dir($itemPath)) {
                $files[] = [
                    'type' => 'dir',
                    'name' => $item,
                    'path' => $relativePath,
                ];
            } elseif (is_file($itemPath)) {
                $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico'])) continue;
                
                // Ищем миниатюру
                $thumb = null;
                $info = pathinfo($item);
                $thumbName = $info['filename'] . '_thumb.' . ($info['extension'] ?? '');
                $thumbPath = $fullPath . $thumbName;
                
                if (file_exists($thumbPath)) {
                    $thumb = '/uploads/' . ($path ? $path . '/' : '') . $thumbName;
                }
                
                $files[] = [
                    'type' => 'image',
                    'name' => $item,
                    'path' => '/uploads/' . $relativePath,
                    'thumb' => $thumb,
                    'size' => filesize($itemPath),
                    'modified' => date('d.m.Y H:i', filemtime($itemPath)),
                ];
            }
        }
        
        // Сортируем: сначала папки, потом файлы
        usort($files, function($a, $b) {
            if ($a['type'] !== $b['type']) {
                return $a['type'] === 'dir' ? -1 : 1;
            }
            return strcmp($a['name'], $b['name']);
        });
        
        echo json_encode([
            'success' => true,
            'files' => $files,
            'path' => $path,
        ]);
        break;
    
    // ============================================
    // ЗАГРУЗКА ИЗОБРАЖЕНИЯ
    // ============================================
    
    case 'upload':
        if (!isset($_FILES['file'])) {
            echo json_encode(['success' => false, 'message' => 'Файл не передан']);
            exit;
        }
        
        $folder = trim($_POST['folder'] ?? 'site');
        $folder = ltrim($folder, '/\\');
        
        // Разрешаем только определённые папки
        $allowedFolders = ['site', 'news', 'articles', 'gallery', 'users', 'modules', 'blocks'];
        $mainFolder = explode('/', $folder)[0];
        
        if (!in_array($mainFolder, $allowedFolders)) {
            echo json_encode(['success' => false, 'message' => 'Недопустимая папка: ' . $mainFolder]);
            exit;
        }
        
        // Загружаем через ImageUploader
        $result = ImageUploader::upload($_FILES['file'], $folder);
        
        if ($result['success']) {
            // Логируем
            $user = $auth->getUser();
            $db->insert('audit_log', [
                'user_id' => $user['id'],
                'action' => 'media_upload',
                'module' => 'media',
                'description' => "Загружено изображение: {$result['path']}",
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
        
        echo json_encode($result);
        break;
    
    // ============================================
    // УДАЛЕНИЕ ИЗОБРАЖЕНИЯ
    // ============================================
    
case 'delete':
      $path = trim($_POST['path'] ?? $_GET['path'] ?? '');
        
        if (empty($path)) {
            echo json_encode(['success' => false, 'message' => 'Не указан путь']);
            exit;
        }
        
        $path = ltrim($path, '/\\');
        
        // Приводим к виду uploads/...
        if (strpos($path, 'uploads/') !== 0) {
            $path = 'uploads/' . $path;
        }
        
        // Защита от выхода за пределы
        if (strpos($path, '..') !== false) {
            echo json_encode(['success' => false, 'message' => 'Недопустимый путь']);
            exit;
        }
        
        $result = ImageUploader::delete($path);
    
    if ($result) {
        // Логируем
        $user = $auth->getUser();
        $db->insert('audit_log', [
            'user_id' => $user['id'],
            'action' => 'media_delete',
            'module' => 'media',
            'description' => "Удалено изображение: {$path}",
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Изображение удалено']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Не удалось удалить файл: ' . $path]);
    }
    break;
    // ============================================
    // СОЗДАНИЕ ПАПКИ
    // ============================================
    
    case 'create_folder':
        $parent = trim($_POST['parent'] ?? '');
        $parent = ltrim($parent, '/\\');
        $name = trim($_POST['name'] ?? '');
        
        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Не указано имя папки']);
            exit;
        }
        
        // Защита
        if (strpos($parent, '..') !== false || strpos($name, '..') !== false) {
            echo json_encode(['success' => false, 'message' => 'Недопустимый путь']);
            exit;
        }
        
        // Очищаем имя
        $name = preg_replace('/[^a-z0-9_-]/i', '-', $name);
        $name = trim($name, '-');
        
        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Недопустимое имя папки']);
            exit;
        }
        
        $fullPath = ROOT_DIR . '/uploads/' . ($parent ? $parent . '/' : '') . $name;
        
        if (is_dir($fullPath)) {
            echo json_encode(['success' => false, 'message' => 'Папка уже существует']);
            exit;
        }
        
        if (!mkdir($fullPath, 0755, true)) {
            echo json_encode(['success' => false, 'message' => 'Не удалось создать папку']);
            exit;
        }
        
        echo json_encode(['success' => true, 'message' => 'Папка создана']);
        break;
    
    // ============================================
    // УДАЛЕНИЕ ПАПКИ
    // ============================================
    
    case 'delete_folder':
        $path = trim($_POST['path'] ?? $_GET['path'] ?? '');
        
        if (empty($path)) {
            echo json_encode(['success' => false, 'message' => 'Не указан путь']);
            exit;
        }
        
        $path = ltrim($path, '/\\');
        
        // Приводим к виду uploads/...
        if (strpos($path, 'uploads/') !== 0) {
            $path = 'uploads/' . $path;
        }
        
        // Защита от выхода за пределы
        if (strpos($path, '..') !== false) {
            echo json_encode(['success' => false, 'message' => 'Недопустимый путь']);
            exit;
        }
        
        // ЗАЩИТА: папки верхнего уровня нельзя удалять
        $topFolders = ['site', 'news', 'articles', 'gallery', 'users', 'modules', 'blocks'];
        $parts = explode('/', $path);
        
        if ($parts[0] === 'uploads') {
            array_shift($parts);
        }
        
        if (count($parts) <= 1 && in_array($parts[0], $topFolders)) {
            echo json_encode([
                'success' => false,
                'message' => 'Нельзя удалять папки верхнего уровня'
            ]);
            exit;
        }
        
        // Удаляем через ImageUploader
        $result = ImageUploader::deleteFolder($path);
        
        if ($result['success']) {
            $user = $auth->getUser();
            $db->insert('audit_log', [
                'user_id' => $user['id'],
                'action' => 'media_delete_folder',
                'module' => 'media',
                'description' => "Удалена папка: {$path} (файлов: {$result['deleted']})",
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
        
        echo json_encode($result);
        break;
    
    // ============================================
    // ПЕРЕИМЕНОВАНИЕ ПАПКИ
    // ============================================
    
    case 'rename_folder':
        $path = trim($_POST['path'] ?? '');
        $newName = trim($_POST['new_name'] ?? '');
        
        if (empty($path) || empty($newName)) {
            echo json_encode(['success' => false, 'message' => 'Не указан путь или новое имя']);
            exit;
        }
        
        $path = ltrim($path, '/\\');
        
        // Приводим к виду uploads/...
        if (strpos($path, 'uploads/') !== 0) {
            $path = 'uploads/' . $path;
        }
        
        // Защита от выхода за пределы
        if (strpos($path, '..') !== false) {
            echo json_encode(['success' => false, 'message' => 'Недопустимый путь']);
            exit;
        }
        
        // ЗАЩИТА: папки верхнего уровня нельзя переименовывать
        $topFolders = ['site', 'news', 'articles', 'gallery', 'users', 'modules', 'blocks'];
        $parts = explode('/', $path);
        
        if ($parts[0] === 'uploads') {
            array_shift($parts);
        }
        
        if (count($parts) <= 1 && in_array($parts[0], $topFolders)) {
            echo json_encode([
                'success' => false,
                'message' => 'Нельзя переименовывать папки верхнего уровня'
            ]);
            exit;
        }
        
        // Переименовываем
        $result = ImageUploader::renameFolder($path, $newName);
        
        if ($result['success']) {
            $user = $auth->getUser();
            $db->insert('audit_log', [
                'user_id' => $user['id'],
                'action' => 'media_rename_folder',
                'module' => 'media',
                'description' => "Переименована папка: {$path} → {$newName}",
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
        
        echo json_encode($result);
        break;



    // ============================================
    // НЕИЗВЕСТНОЕ ДЕЙСТВИЕ
    // ============================================
    
    default:
        echo json_encode(['success' => false, 'message' => 'Неизвестное действие: ' . $action]);
}