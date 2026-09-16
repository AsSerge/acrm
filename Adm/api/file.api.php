<?php
/**
 * API для работы с файлами проекта
 * Чтение, сохранение, создание, удаление, переименование
 * Доступ: только Супер-админ
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

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$filePath = trim($_POST['file'] ?? $_GET['file'] ?? '');
$content = $_POST['content'] ?? '';
$newName = trim($_POST['new_name'] ?? '');

header('Content-Type: application/json');

// Проверка авторизации
if (!$auth->isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Не авторизован']);
    exit;
}

// Только Супер-админ
if (!$auth->isSuperAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Доступ только для Супер-админа']);
    exit;
}

// ============================================
// ПРОВЕРКА ПУТИ К ФАЙЛУ
// ============================================

function validateFilePath($filePath, $projectRoot, $mustExist = true)
{
    $filePath = ltrim(trim($filePath), '/\\');
    
    if (empty($filePath)) {
        return false;
    }
    
    // Защита от выхода за пределы проекта
    if (strpos($filePath, '..') !== false) {
        return false;
    }
    
    // Разрешённые расширения
    $allowedExtensions = ['php', 'html', 'css', 'js', 'json', 'txt', 'md', 'bak'];
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    
    if ($mustExist && !in_array($ext, $allowedExtensions)) {
        return false;
    }
    
    // Полный путь
    $fullPath = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $filePath);
    
    if ($mustExist) {
        if (!file_exists($fullPath)) {
            return false;
        }
    } else {
        // Проверяем, что родительская папка существует
        $parentDir = dirname($fullPath);
        if (!is_dir($parentDir)) {
            return false;
        }
    }
    
    // Проверка, что путь внутри проекта
    $realParent = realpath(dirname($fullPath));
    $realRoot = realpath($projectRoot);
    
    if ($realParent === false || $realRoot === false) {
        return false;
    }
    
    $realParentNorm = strtolower(str_replace('\\', '/', $realParent));
    $realRootNorm = strtolower(str_replace('\\', '/', $realRoot));
    
    if (strpos($realParentNorm, $realRootNorm) !== 0) {
        return false;
    }
    
    return $fullPath;
}

// ============================================
// ОБРАБОТКА ДЕЙСТВИЙ
// ============================================

switch ($action) {
    case 'read':
        if (empty($filePath)) {
            echo json_encode(['success' => false, 'message' => 'Не указан файл']);
            exit;
        }
        
        $fullPath = validateFilePath($filePath, ROOT_DIR, true);
        
        if ($fullPath === false) {
            echo json_encode(['success' => false, 'message' => 'Недопустимый путь к файлу: ' . $filePath]);
            exit;
        }
        
        $fileContent = file_get_contents($fullPath);
        
        echo json_encode([
            'success' => true,
            'content' => $fileContent,
            'file' => $filePath,
            'size' => filesize($fullPath),
            'modified' => date('d.m.Y H:i:s', filemtime($fullPath))
        ]);
        break;
    
    case 'save':
        if (empty($filePath)) {
            echo json_encode(['success' => false, 'message' => 'Не указан файл']);
            exit;
        }
        
        $fullPath = validateFilePath($filePath, ROOT_DIR, true);
        
        if ($fullPath === false) {
            echo json_encode(['success' => false, 'message' => 'Недопустимый путь к файлу']);
            exit;
        }
        
        if (!is_writable($fullPath)) {
            echo json_encode(['success' => false, 'message' => 'Файл недоступен для записи']);
            exit;
        }
        
        // Создаём резервную копию
        copy($fullPath, $fullPath . '.bak');
        
        $result = file_put_contents($fullPath, $content);
        
        if ($result === false) {
            echo json_encode(['success' => false, 'message' => 'Ошибка сохранения файла']);
            exit;
        }
        
        $adminUser = $auth->getUser();
        $db->insert('audit_log', [
            'user_id' => $adminUser['id'],
            'action' => 'file_save',
            'module' => 'editor',
            'description' => "Отредактирован файл: {$filePath}",
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Файл сохранён (создана копия .bak)',
            'size' => filesize($fullPath),
            'modified' => date('d.m.Y H:i:s')
        ]);
        break;
    
    case 'scan':
        $scanPath = trim($_POST['path'] ?? $_GET['path'] ?? '');
        $scanPath = ltrim($scanPath, '/\\');
        
        if (strpos($scanPath, '..') !== false) {
            echo json_encode(['success' => false, 'message' => 'Недопустимый путь']);
            exit;
        }
        
        $fullPath = ROOT_DIR . ($scanPath ? DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $scanPath) : '');
        
        if (!is_dir($fullPath)) {
            echo json_encode(['success' => false, 'message' => 'Папка не найдена: ' . $scanPath]);
            exit;
        }
        
        $files = [];
        $items = scandir($fullPath);
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $itemPath = $fullPath . DIRECTORY_SEPARATOR . $item;
            $relativePath = $scanPath ? $scanPath . '/' . $item : $item;
            
            if (is_dir($itemPath)) {
                $files[] = [
                    'type' => 'dir',
                    'name' => $item,
                    'path' => $relativePath
                ];
            } else {
                $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
                if (in_array($ext, ['php', 'html', 'css', 'js', 'json', 'txt', 'md', 'bak'])) {
                    $files[] = [
                        'type' => 'file',
                        'name' => $item,
                        'path' => $relativePath,
                        'size' => filesize($itemPath),
                        'modified' => date('d.m.Y H:i', filemtime($itemPath))
                    ];
                }
            }
        }
        
        echo json_encode([
            'success' => true,
            'files' => $files,
            'path' => $scanPath
        ]);
        break;
    
    case 'create_file':
        if (empty($filePath)) {
            echo json_encode(['success' => false, 'message' => 'Не указан путь']);
            exit;
        }
        
        $fullPath = validateFilePath($filePath, ROOT_DIR, false);
        
        if ($fullPath === false) {
            echo json_encode(['success' => false, 'message' => 'Недопустимый путь']);
            exit;
        }
        
        if (file_exists($fullPath)) {
            echo json_encode(['success' => false, 'message' => 'Файл уже существует']);
            exit;
        }
        
        // Создаём файл с базовым содержимым
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $defaultContent = "<?php\n/**\n * {$filePath}\n */\n?>\n";
        
        if ($ext === 'html') {
            $defaultContent = "<!DOCTYPE html>\n<html>\n<head>\n    <title></title>\n</head>\n<body>\n\n</body>\n</html>\n";
        } elseif ($ext === 'css') {
            $defaultContent = "/* {$filePath} */\n\n";
        } elseif ($ext === 'js') {
            $defaultContent = "// {$filePath}\n\n";
        }
        
        $result = file_put_contents($fullPath, $defaultContent);
        
        if ($result === false) {
            echo json_encode(['success' => false, 'message' => 'Ошибка создания файла']);
            exit;
        }
        
        $adminUser = $auth->getUser();
        $db->insert('audit_log', [
            'user_id' => $adminUser['id'],
            'action' => 'file_create',
            'module' => 'editor',
            'description' => "Создан файл: {$filePath}",
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Файл создан',
            'file' => $filePath
        ]);
        break;
    
    case 'create_folder':
        if (empty($filePath)) {
            echo json_encode(['success' => false, 'message' => 'Не указан путь']);
            exit;
        }
        
        $filePath = ltrim(trim($filePath), '/\\');
        
        if (strpos($filePath, '..') !== false) {
            echo json_encode(['success' => false, 'message' => 'Недопустимый путь']);
            exit;
        }
        
        $fullPath = ROOT_DIR . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $filePath);
        
        if (file_exists($fullPath)) {
            echo json_encode(['success' => false, 'message' => 'Папка уже существует']);
            exit;
        }
        
        $result = mkdir($fullPath, 0755, true);
        
        if (!$result) {
            echo json_encode(['success' => false, 'message' => 'Ошибка создания папки']);
            exit;
        }
        
        $adminUser = $auth->getUser();
        $db->insert('audit_log', [
            'user_id' => $adminUser['id'],
            'action' => 'folder_create',
            'module' => 'editor',
            'description' => "Создана папка: {$filePath}",
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Папка создана',
            'folder' => $filePath
        ]);
        break;
    
    case 'delete':
        if (empty($filePath)) {
            echo json_encode(['success' => false, 'message' => 'Не указан путь']);
            exit;
        }
        
        $filePath = ltrim(trim($filePath), '/\\');
        
        if (strpos($filePath, '..') !== false) {
            echo json_encode(['success' => false, 'message' => 'Недопустимый путь']);
            exit;
        }
        
        $fullPath = ROOT_DIR . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $filePath);
        
        if (!file_exists($fullPath)) {
            echo json_encode(['success' => false, 'message' => 'Файл или папка не найдены']);
            exit;
        }
        
        // Нельзя удалить корень проекта
        $realPath = realpath($fullPath);
        $realRoot = realpath(ROOT_DIR);
        if ($realPath === $realRoot) {
            echo json_encode(['success' => false, 'message' => 'Нельзя удалить корень проекта']);
            exit;
        }
        
        if (is_dir($fullPath)) {
            // Удаляем папку (только если пуста)
            $items = scandir($fullPath);
            $items = array_diff($items, ['.', '..']);
            
            if (!empty($items)) {
                echo json_encode(['success' => false, 'message' => 'Папка не пуста. Удалите содержимое сначала.']);
                exit;
            }
            
            $result = rmdir($fullPath);
        } else {
            // Удаляем файл и его .bak
            if (file_exists($fullPath . '.bak')) {
                unlink($fullPath . '.bak');
            }
            $result = unlink($fullPath);
        }
        
        if (!$result) {
            echo json_encode(['success' => false, 'message' => 'Ошибка удаления']);
            exit;
        }
        
        $adminUser = $auth->getUser();
        $db->insert('audit_log', [
            'user_id' => $adminUser['id'],
            'action' => 'file_delete',
            'module' => 'editor',
            'description' => "Удалён: {$filePath}",
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Удалено успешно'
        ]);
        break;
    
    case 'rename':
        if (empty($filePath) || empty($newName)) {
            echo json_encode(['success' => false, 'message' => 'Не указано новое имя']);
            exit;
        }
        
        $filePath = ltrim(trim($filePath), '/\\');
        $newName = trim($newName);
        
        if (strpos($filePath, '..') !== false || strpos($newName, '..') !== false) {
            echo json_encode(['success' => false, 'message' => 'Недопустимый путь']);
            exit;
        }
        
        // Новое имя не должно содержать слеши
        if (strpos($newName, '/') !== false || strpos($newName, '\\') !== false) {
            echo json_encode(['success' => false, 'message' => 'Имя не должно содержать слеши']);
            exit;
        }
        
        $fullPath = ROOT_DIR . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $filePath);
        
        if (!file_exists($fullPath)) {
            echo json_encode(['success' => false, 'message' => 'Файл не найден']);
            exit;
        }
        
        $parentDir = dirname($fullPath);
        $newFullPath = $parentDir . DIRECTORY_SEPARATOR . $newName;
        
        if (file_exists($newFullPath)) {
            echo json_encode(['success' => false, 'message' => 'Файл с таким именем уже существует']);
            exit;
        }
        
        $result = rename($fullPath, $newFullPath);
        
        if (!$result) {
            echo json_encode(['success' => false, 'message' => 'Ошибка переименования']);
            exit;
        }
        
        $adminUser = $auth->getUser();
        $db->insert('audit_log', [
            'user_id' => $adminUser['id'],
            'action' => 'file_rename',
            'module' => 'editor',
            'description' => "Переименован: {$filePath} → {$newName}",
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Переименовано успешно',
            'new_path' => dirname($filePath) . '/' . $newName
        ]);
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Неизвестное действие: ' . $action]);
}