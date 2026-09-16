<?php
/**
 * Модуль Help - страница помощи для администратора
 */

if (!$auth->hasAdminAccess('help', 'view')) {
    echo '<div class="admin-alert admin-alert-danger">У вас нет прав для просмотра этого раздела.</div>';
    return;
}

// Активная вкладка
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'start';

$tabs = [
    'start' => '🚀 Начало работы',
    'settings' => '⚙️ Настройки',
    'users' => '👤 Пользователи',
    'modules' => '📦 Модули',
    'blocks' => '🧩 Блоки',
    'menu' => '📋 Меню',
    'files' => '📁 Файловый менеджер',
	'api' => '🔧 API классов',
    'faq' => '❓ FAQ',
];

// Проверяем, что вкладка существует
if (!isset($tabs[$activeTab])) {
    $activeTab = 'start';
}

// Путь к файлу контента
$contentFile = __DIR__ . '/pages/' . $activeTab . '.php';
?>

<link rel="stylesheet" href="/Adm/modules/help/style.css">

<div class="module-help">
    <div class="module-header">
        <h2>
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 8px;">
                <circle cx="12" cy="12" r="10"/>
                <path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/>
                <line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
            Помощь администратору
        </h2>
    </div>
    
    <!-- Вкладки -->
    <div class="help-tabs">
        <?php foreach ($tabs as $key => $title): ?>
            <a href="?route=help&tab=<?= $key ?>" 
               class="help-tab <?= $activeTab === $key ? 'active' : '' ?>">
                <?= $title ?>
            </a>
        <?php endforeach; ?>
    </div>
    
    <!-- Контент вкладки -->
    <div class="admin-card help-content">
        <?php
        if (file_exists($contentFile)) {
            require $contentFile;
        } else {
            echo '<p style="color: var(--admin-text-muted); text-align: center; padding: 40px 0;">';
            echo 'Раздел в разработке.';
            echo '</p>';
        }
        ?>
    </div>
</div>