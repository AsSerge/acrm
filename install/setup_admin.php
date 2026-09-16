<?php
/**
 * Установочный скрипт - создание главного администратора
 * Запускается один раз, затем файл нужно удалить
 */

require_once __DIR__ . '/../base/config.php';
require_once __DIR__ . '/../classes.php';

// Проверяем, что файл не был уже использован
$db = Database::getInstance();

// Проверяем, есть ли уже администраторы
$adminExists = $db->fetchOne(
    "SELECT u.id FROM users u 
     JOIN user_groups ug ON u.group_id = ug.id 
     WHERE ug.group_type = 'admin' AND ug.is_system = 1"
);

if ($adminExists) {
    die('⚠️ Администратор уже существует. Удалите этот файл для безопасности.');
}

// ============================================
// 1. СОЗДАЕМ ГРУППУ СУПЕР-АДМИНА (если нет)
// ============================================
$group = $db->fetchOne("SELECT id FROM user_groups WHERE is_system = 1 AND group_type = 'admin'");

if (!$group) {
    $db->insert('user_groups', [
        'group_name' => 'Супер-админ',
        'group_type' => 'admin',
        'is_system' => 1
    ]);
    $groupId = $db->lastInsertId();
    echo "✅ Создана группа 'Супер-админ' (ID: {$groupId})<br>";
} else {
    $groupId = $group['id'];
    echo "✅ Группа 'Супер-админ' уже существует (ID: {$groupId})<br>";
}

// ============================================
// 2. СОЗДАЕМ ПОЛЬЗОВАТЕЛЯ (если нет)
// ============================================
$user = $db->fetchOne("SELECT id FROM users WHERE username = 'admin'");

if (!$user) {
    $username = 'admin';
    $password = 'Admin123!';
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    $db->insert('users', [
        'username' => $username,
        'email' => 'admin@zoomcrm.local',
        'password_hash' => $passwordHash,
        'group_id' => $groupId,
        'is_active' => 1,
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    $userId = $db->lastInsertId();
    echo "✅ Создан пользователь 'admin' (ID: {$userId})<br>";
    echo "✅ Пароль: <strong>Admin123!</strong><br>";
} else {
    $userId = $user['id'];
    echo "✅ Пользователь 'admin' уже существует (ID: {$userId})<br>";
    
    // Обновляем пароль на всякий случай
    $password = 'Admin123!';
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $db->update('users', ['password_hash' => $passwordHash], 'id = ?', [$userId]);
    echo "✅ Пароль обновлён: <strong>Admin123!</strong><br>";
}

// ============================================
// 3. НАСТРАИВАЕМ ПРАВА ДОСТУПА
// ============================================
echo "<br>📋 Настройка прав доступа:<br>";

// Получаем список всех модулей
$modules = $db->fetchAll("SELECT module_name FROM modules_registry WHERE is_active = 1");

if (empty($modules)) {
    // Если модулей нет - добавляем базовые
    echo "⚠️ Модули не найдены, создаём базовые...<br>";
    
    $baseModules = ['dashboard', 'users', 'groups', 'menu', 'pages', 'settings'];
    foreach ($baseModules as $mod) {
        // Проверяем, есть ли уже такой модуль
        $exists = $db->fetchOne("SELECT id FROM modules_registry WHERE module_name = ?", [$mod]);
        if (!$exists) {
            $db->insert('modules_registry', [
                'module_name' => $mod,
                'module_type' => 'admin',
                'module_path' => '/Adm/modules/' . $mod . '/',
                'is_active' => 1,
                'version' => '1.0.0'
            ]);
            echo "  ✅ Модуль '{$mod}' зарегистрирован<br>";
        }
    }
    
    // Обновляем список модулей
    $modules = $db->fetchAll("SELECT module_name FROM modules_registry WHERE is_active = 1");
}

// Добавляем права для группы на все модули (если нет)
foreach ($modules as $module) {
    $moduleName = $module['module_name'];
    
    // Проверяем, есть ли уже права
    $exists = $db->fetchOne(
        "SELECT id FROM module_access WHERE group_id = ? AND module_name = ?",
        [$groupId, $moduleName]
    );
    
    if (!$exists) {
        $db->insert('module_access', [
            'group_id' => $groupId,
            'module_name' => $moduleName,
            'access_level' => 'full'
        ]);
        echo "  ✅ Права (full) для модуля '{$moduleName}'<br>";
    } else {
        // Обновляем на full, если было другое
        $db->update(
            'module_access',
            ['access_level' => 'full'],
            'group_id = ? AND module_name = ?',
            [$groupId, $moduleName]
        );
        echo "  ✅ Права обновлены (full) для модуля '{$moduleName}'<br>";
    }
}

// ============================================
// 4. ДОБАВЛЯЕМ СТРАНИЦУ ВХОДА В МЕНЮ (если нет)
// ============================================
$menuExists = $db->fetchOne(
    "SELECT id FROM menu_structure WHERE module_link = '/Adm/login.php'"
);

if (!$menuExists) {
    $db->insert('menu_structure', [
        'parent_id' => null,
        'menu_title' => 'Вход в админку',
        'module_link' => '/Adm/login.php',
        'sort_order' => 999,
        'is_active' => 1,
        'menu_type' => 'frontend',
        'access_groups' => null
    ]);
    echo "  ✅ Пункт меню 'Вход в админку' создан<br>";
}

// ============================================
// 5. ИТОГОВЫЙ ОТЧЕТ
// ============================================
echo "<hr>";
echo "<h2 style='color: #4caf50;'>✅ Установка завершена!</h2>";
echo "<p><strong>👤 Имя пользователя:</strong> admin</p>";
echo "<p><strong>🔑 Пароль:</strong> <strong style='color: #ffa726;'>Admin123!</strong></p>";
echo "<p><strong>🆔 ID пользователя:</strong> {$userId}</p>";
echo "<p><strong>👥 Группа:</strong> Супер-админ (ID: {$groupId})</p>";
echo "<p><strong>📦 Модулей с правами:</strong> " . count($modules) . "</p>";
echo "<hr>";
echo "<p style='color: #ef5350; font-weight: bold;'>⚠️ ВАЖНО: Удалите этот файл (/install/setup_admin.php) после входа!</p>";
echo "<p><a href='/Adm/login.php' style='font-size: 18px; color: #64b5f6; text-decoration: none; border: 1px solid #64b5f6; padding: 8px 20px; border-radius: 4px;'>🚀 Перейти на страницу входа</a></p>";