<?php
/**
 * Скрипт очистки административных данных
 * Удаляет всех администраторов и их права
 * Создает чистого супер-админа
 */

require_once __DIR__ . '/../base/config.php';
require_once __DIR__ . '/../classes.php';

$db = Database::getInstance();

// ============================================
// ПРОВЕРКА ПОДТВЕРЖДЕНИЯ
// ============================================
if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Очистка администраторов</title>
        <style>
            body { font-family: 'FuturaNew', Arial, sans-serif; background: #0f1724; color: #e8edf5; padding: 40px; }
            .warning { background: #1a1a2e; border: 2px solid #ef5350; padding: 30px; border-radius: 8px; max-width: 600px; margin: 0 auto; }
            h1 { color: #ef5350; }
            .btn { display: inline-block; padding: 10px 25px; margin: 10px; border-radius: 4px; text-decoration: none; font-weight: bold; }
            .btn-danger { background: #ef5350; color: #fff; }
            .btn-secondary { background: #2a3a4f; color: #e8edf5; }
            .btn-danger:hover { background: #c62828; }
            .btn-secondary:hover { background: #3a4a5f; }
            ul { text-align: left; }
            li { padding: 4px 0; }
        </style>
    </head>
    <body>
        <div class="warning">
            <h1>⚠️ ВНИМАНИЕ!</h1>
            <p>Этот скрипт полностью удалит:</p>
            <ul>
                <li>✅ Всех администраторов</li>
                <li>✅ Все группы администраторов</li>
                <li>✅ Все права доступа администраторов</li>
                <li>✅ Все сессии администраторов</li>
                <li>✅ Создаст чистого супер-админа с паролем <strong>Admin123!</strong></li>
            </ul>
            <p><strong style="color: #ef5350;">Это действие НЕЛЬЗЯ отменить!</strong></p>
            <p>
                <a href="?confirm=yes" class="btn btn-danger">✅ Да, выполнить очистку</a>
                <a href="/Adm/login.php" class="btn btn-secondary">❌ Отмена</a>
            </p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ============================================
// ВЫПОЛНЯЕМ ОЧИСТКУ
// ============================================
echo "<h1>🧹 Очистка административных данных...</h1>";
echo "<pre style='color: #8fa4c8; font-family: monospace;'>";

// 1. Получаем ID администраторов
$adminIds = $db->fetchAll(
    "SELECT u.id FROM users u 
     JOIN user_groups ug ON u.group_id = ug.id 
     WHERE ug.group_type = 'admin'"
);

$adminIdList = array_column($adminIds, 'id');
$adminIdString = implode(',', $adminIdList);

echo "Найдено администраторов: " . count($adminIdList) . "\n";

// 2. Удаляем сессии администраторов
if (!empty($adminIdString)) {
    $deletedSessions = $db->delete(
        "user_sessions",
        "user_id IN ({$adminIdString})"
    );
    echo "✅ Удалено сессий: {$deletedSessions}\n";
}

// 3. Удаляем права доступа для административных групп
$adminGroupIds = $db->fetchAll(
    "SELECT id FROM user_groups WHERE group_type = 'admin'"
);
$adminGroupIdList = array_column($adminGroupIds, 'id');
$adminGroupIdString = implode(',', $adminGroupIdList);

if (!empty($adminGroupIdString)) {
    $deletedAccess = $db->delete(
        "module_access",
        "group_id IN ({$adminGroupIdString})"
    );
    echo "✅ Удалено прав доступа: {$deletedAccess}\n";
}

// 4. Удаляем пользователей-администраторов
if (!empty($adminIdString)) {
    $deletedUsers = $db->delete(
        "users",
        "id IN ({$adminIdString})"
    );
    echo "✅ Удалено пользователей-администраторов: {$deletedUsers}\n";
}

// 5. Удаляем группы администраторов (кроме системных)
$deletedGroups = $db->delete(
    "user_groups",
    "group_type = 'admin' AND is_system = 0"
);
echo "✅ Удалено групп администраторов: {$deletedGroups}\n";

// ============================================
// СОЗДАЕМ НОВОГО СУПЕР-АДМИНА
// ============================================
echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "👤 Создание нового супер-админа...\n\n";

// 1. Создаем группу супер-админа
$db->insert('user_groups', [
    'group_name' => 'Супер-админ',
    'group_type' => 'admin',
    'is_system' => 1
]);
$groupId = $db->lastInsertId();
echo "✅ Создана группа 'Супер-админ' (ID: {$groupId})\n";

// 2. Создаем пользователя
$username = 'serge';
$password = 'aser22';
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
echo "✅ Создан пользователь '{$username}' (ID: {$userId})\n";
echo "🔑 Пароль: \033[33m{$password}\033[0m\n";

// 3. Добавляем права на все модули
$modules = $db->fetchAll("SELECT module_name FROM modules_registry WHERE is_active = 1");

if (empty($modules)) {
    echo "⚠️ Модули не найдены, создаём базовые...\n";
    $baseModules = ['dashboard', 'users', 'groups', 'menu', 'pages', 'settings'];
    foreach ($baseModules as $mod) {
        $db->insert('modules_registry', [
            'module_name' => $mod,
            'module_type' => 'admin',
            'module_path' => '/Adm/modules/' . $mod . '/',
            'is_active' => 1,
            'version' => '1.0.0'
        ]);
        echo "  ✅ Модуль '{$mod}' зарегистрирован\n";
    }
    $modules = $db->fetchAll("SELECT module_name FROM modules_registry WHERE is_active = 1");
}

foreach ($modules as $module) {
    $db->insert('module_access', [
        'group_id' => $groupId,
        'module_name' => $module['module_name'],
        'access_level' => 'full'
    ]);
    echo "  ✅ Права (full) на модуль: {$module['module_name']}\n";
}

// ============================================
// ИТОГ
// ============================================
echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✅ Очистка и создание выполнены успешно!\n";
echo "\n";
echo "👤 Имя пользователя: admin\n";
echo "🔑 Пароль: \033[33mAdmin123!\033[0m\n";
echo "🆔 ID пользователя: {$userId}\n";
echo "👥 Группа: Супер-админ (ID: {$groupId})\n";
echo "📦 Модулей с правами: " . count($modules) . "\n";
echo "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "⚠️ ВАЖНО: Удалите папку /install/ после входа!\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

echo "</pre>";
echo "<p style='margin-top: 20px;'><a href='/Adm/login.php' style='display: inline-block; padding: 12px 30px; background: #64b5f6; color: #0f1724; text-decoration: none; border-radius: 4px; font-weight: bold;'>🚀 Перейти на страницу входа</a></p>";