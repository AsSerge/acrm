<?php
/**
 * Модуль Galleries - создание галереи
 */

if (!$auth->hasAdminAccess('galleries', 'edit')) {
    echo '<div class="admin-alert admin-alert-danger">У вас нет прав для создания галерей.</div>';
    return;
}

$db = Database::getInstance();
$galleryManager = new GalleryManager();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if (empty($title)) {
        $error = 'Название галереи обязательно';
    } else {
        $galleryId = $galleryManager->create($title, $description);
        
        // Логируем
        $user = $auth->getUser();
        $db->insert('audit_log', [
            'user_id' => $user['id'],
            'action' => 'gallery_create',
            'module' => 'galleries',
            'description' => "Создана галерея \"{$title}\" (ID: {$galleryId})",
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        // Редирект на редактирование
		$success = "Галерея создана!";
		echo '<script>location.href = "?route=galleries&action=edit&id=' . $galleryId . '";</script>';
		exit;
    }
}
?>

<link rel="stylesheet" href="/Adm/modules/galleries/style.css">

<div class="module-galleries">
    <div class="module-header">
        <h2>➕ Создание галереи</h2>
        <a href="?route=galleries" class="btn-admin btn-admin-secondary">← Назад к списку</a>
    </div>
    
    <?php if ($error): ?>
        <div class="admin-alert admin-alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <div class="admin-card">
        <form method="POST" class="admin-form" style="max-width: 600px;">
            <div class="admin-form-group">
                <label for="title">Название галереи *</label>
                <input type="text" id="title" name="title" class="admin-form-control" 
                       value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" 
                       placeholder="Например: Фото со встречи" required autofocus>
            </div>
            
            <div class="admin-form-group">
                <label for="description">Описание</label>
                <textarea id="description" name="description" class="admin-form-control" rows="3" 
                          placeholder="Краткое описание галереи (опционально)"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>
            
            <div class="admin-form-group">
                <button type="submit" class="btn-admin btn-admin-success">Создать и перейти к загрузке фото</button>
                <a href="?route=galleries" class="btn-admin btn-admin-secondary">Отмена</a>
            </div>
        </form>
    </div>
</div>