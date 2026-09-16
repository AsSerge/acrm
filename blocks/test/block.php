<?php
/**
 * Блок "Тестовый блок"
 * 
 * Доступные переменные:
 * $params — массив параметров из data-* атрибутов
 * $blockName — имя блока (test)
 * $uniqid — уникальный ID экземпляра блока
 */

// Получаем параметры
$count = $params['count'] ?? 3;

// Логика блока
// ...

// Вывод HTML
?>

<div class="test-block">
    <p>Блок работает! Параметр count = <?= htmlspecialchars($params['count'] ?? 'не задан') ?></p>
    <p>Уникальный ID: <?= htmlspecialchars($uniqid) ?></p>
    <p>Здесь еще какой-то текст, который есть в каждом блоке</p>
    
    
        <?php   
            $siteName = Setting::get('site_name');
            $phone = Setting::get('contact_phone');
            $email = Setting::get('contact_email');
        ?>

    <h2>Добро пожаловать в <?= htmlspecialchars($siteName) ?></h2>
    <p>Телефон: <?= htmlspecialchars($phone) ?></p>
    
</div>