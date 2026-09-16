<?php
/**
 * Выход из системы (для пользователей и администраторов)
 */

require_once __DIR__ . '/base/config.php';
require_once __DIR__ . '/classes.php';

$auth = new Auth();
$auth->logout();

// Редирект на главную
header('Location: /');
exit;