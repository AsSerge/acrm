<?php
/**
 * Выход из админ-панели
 */

require_once __DIR__ . '/../base/config.php';
require_once __DIR__ . '/classes.php';

$auth = new AdminAuth();
$auth->logout();

// Редирект на страницу входа
header('Location: /Adm/login.php');
exit;