<?php
/**
 * API для модуля Settings
 * Обрабатывает: save, generate_robots, generate_sitemap
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

// Проверка прав на модуль settings
if (!$auth->hasAdminAccess('settings', 'edit')) {
    echo json_encode(['success' => false, 'message' => 'Недостаточно прав']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    
    // ============================================
    // СОХРАНЕНИЕ НАСТРОЕК
    // ============================================
    
    case 'save':
        $tab = $_POST['tab'] ?? 'main';
        
        // Определяем, какие поля сохранять в зависимости от вкладки
        $fieldsByTab = [
            'main' => [
                'site_name', 'site_description', 'site_keywords',
                'site_email', 'site_language', 'site_timezone'
            ],
            'appearance' => [
                'logo_text', 'accent_color', 'default_template'
            ],
            'contacts' => [
                'contact_phone', 'contact_email', 'contact_address', 'contact_working_hours'
            ],
            'social' => [
                'social_vk', 'social_telegram', 'social_whatsapp',
                'social_youtube', 'social_instagram'
            ],
            'seo' => [
                'seo_meta_title', 'seo_meta_description',
                'seo_google_analytics', 'seo_yandex_metrika'
            ],
            'system' => [
                'maintenance_mode', 'maintenance_module'
            ],
        ];
        
        if (!isset($fieldsByTab[$tab])) {
            echo json_encode(['success' => false, 'message' => 'Неизвестная вкладка']);
            exit;
        }
        
        $savedCount = 0;
        
        foreach ($fieldsByTab[$tab] as $field) {
            // Для чекбокса: если не пришло — значит '0'
            if ($field === 'maintenance_mode') {
                $value = isset($_POST[$field]) ? (int)$_POST[$field] : 0;
            } else {
                $value = $_POST[$field] ?? null;
                // Если поле не пришло — пропускаем
                if ($value === null) continue;
            }
            
            // Обязательное поле
            if ($field === 'site_name' && empty($value)) {
                echo json_encode(['success' => false, 'message' => 'Название сайта обязательно']);
                exit;
            }
            
            if (Setting::set($field, $value)) {
                $savedCount++;
            }
        }
        
        // Логируем
        $user = $auth->getUser();
        $db->insert('audit_log', [
            'user_id' => $user['id'],
            'action' => 'settings_save',
            'module' => 'settings',
            'description' => "Сохранены настройки (вкладка: {$tab}, полей: {$savedCount})",
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => "Настройки сохранены ({$savedCount} полей)"
        ]);
        break;
    
    // ============================================
    // ГЕНЕРАЦИЯ ROBOTS.TXT
    // ============================================
    
    case 'generate_robots':
        $siteUrl = SITE_URL;
        
        $robots = "User-agent: *\n";
        $robots .= "Disallow: /Adm/\n";
        $robots .= "Disallow: /base/\n";
        $robots .= "Disallow: /core/\n";
        $robots .= "Disallow: /storage/\n";
        $robots .= "Disallow: /vendor/\n";
        $robots .= "Disallow: /api/\n";
        $robots .= "\n";
        $robots .= "Allow: /\n";
        $robots .= "Allow: /assets/\n";
        $robots .= "\n";
        $robots .= "Sitemap: {$siteUrl}/sitemap.xml\n";
        $robots .= "\n";
        $robots .= "# Сгенерировано: " . date('Y-m-d H:i:s') . "\n";
        
        $robotsFile = ROOT_DIR . '/robots.txt';
        $result = file_put_contents($robotsFile, $robots);
        
        if ($result === false) {
            echo json_encode(['success' => false, 'message' => 'Не удалось записать robots.txt']);
            exit;
        }
        
        // Сохраняем содержимое в настройках (для истории)
        Setting::set('robots_txt', $robots);
        
        // Логируем
        $user = $auth->getUser();
        $db->insert('audit_log', [
            'user_id' => $user['id'],
            'action' => 'settings_robots',
            'module' => 'settings',
            'description' => 'Перегенерирован robots.txt',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'robots.txt успешно создан'
        ]);
        break;
    
    // ============================================
    // ГЕНЕРАЦИЯ SITEMAP.XML
    // ============================================
    
    case 'generate_sitemap':
        $siteUrl = rtrim(SITE_URL, '/');
        
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        // 1. Главная
        $xml .= "  <url>\n";
        $xml .= "    <loc>{$siteUrl}/</loc>\n";
        $xml .= "    <lastmod>" . date('Y-m-d') . "</lastmod>\n";
        $xml .= "    <changefreq>daily</changefreq>\n";
        $xml .= "    <priority>1.0</priority>\n";
        $xml .= "  </url>\n";
        
        // 2. Модули (активные, фронтенд)
        $moduleManager = new ModuleManager();
        $frontendModules = $moduleManager->getFrontendModules(true);
        
        foreach ($frontendModules as $name => $module) {
            $slug = trim($module['slug'], '/');
            if (empty($slug)) continue;
            
            $xml .= "  <url>\n";
            $xml .= "    <loc>{$siteUrl}/{$slug}</loc>\n";
            $xml .= "    <lastmod>" . date('Y-m-d') . "</lastmod>\n";
            $xml .= "    <changefreq>weekly</changefreq>\n";
            $xml .= "    <priority>0.8</priority>\n";
            $xml .= "  </url>\n";
        }
        
        // 3. Блоки с новостями/статьями (если есть таблицы block_*)
        // Пока пропускаем — добавим, когда появятся блоки
        
        $xml .= '</urlset>' . "\n";
        
        $sitemapFile = ROOT_DIR . '/sitemap.xml';
        $result = file_put_contents($sitemapFile, $xml);
        
        if ($result === false) {
            echo json_encode(['success' => false, 'message' => 'Не удалось записать sitemap.xml']);
            exit;
        }
        
        // Сохраняем дату генерации
        Setting::set('sitemap_last_generated', date('Y-m-d H:i:s'));
        
        // Логируем
        $user = $auth->getUser();
        $db->insert('audit_log', [
            'user_id' => $user['id'],
            'action' => 'settings_sitemap',
            'module' => 'settings',
            'description' => 'Перегенерирован sitemap.xml',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        $totalUrls = count($frontendModules) + 1;
        
        echo json_encode([
            'success' => true,
            'message' => 'sitemap.xml успешно создан (' . $totalUrls . ' URL)'
        ]);
        break;

		    // ============================================
		// ЗАГРУЗКА ИЗОБРАЖЕНИЯ (логотип, favicon)
		// ============================================
		
		case 'upload_image':
			if (!isset($_FILES['file'])) {
				echo json_encode(['success' => false, 'message' => 'Файл не передан']);
				exit;
			}
			
			$type = $_POST['type'] ?? '';
			$folder = $_POST['folder'] ?? 'site';
			
			if (!in_array($type, ['logo', 'favicon'])) {
				echo json_encode(['success' => false, 'message' => 'Неизвестный тип']);
				exit;
			}
			
			// Загружаем
			$result = ImageUploader::upload($_FILES['file'], $folder, [
				'sizes' => [], // без миниатюр
			]);
			
			if (!$result['success']) {
				echo json_encode($result);
				exit;
			}
			
			// Сохраняем путь
			$settingKey = ($type === 'logo') ? 'logo_image' : 'favicon';
			Setting::set($settingKey, $result['path']);
			
			// Логируем
			$user = $auth->getUser();
			$db->insert('audit_log', [
				'user_id' => $user['id'],
				'action' => 'settings_upload_' . $type,
				'module' => 'settings',
				'description' => "Загружен {$type}: {$result['path']}",
				'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
				'created_at' => date('Y-m-d H:i:s'),
			]);
			
			echo json_encode([
				'success' => true,
				'message' => ucfirst($type) . ' загружен',
				'path' => $result['path'],
			]);
			break;
		
		// ============================================
		// УДАЛЕНИЕ ИЗОБРАЖЕНИЯ
		// ============================================
		
		case 'delete_image':
			$type = $_POST['type'] ?? '';
			
			if (!in_array($type, ['logo', 'favicon'])) {
				echo json_encode(['success' => false, 'message' => 'Неизвестный тип']);
				exit;
			}
			
			$settingKey = ($type === 'logo') ? 'logo_image' : 'favicon';
			$path = Setting::get($settingKey);
			
			if (!empty($path)) {
				// Удаляем файл (если он в /uploads/)
				if (strpos($path, '/uploads/') === 0) {
					ImageUploader::delete($path);
				}
				
				// Очищаем настройку
				Setting::set($settingKey, '');
			}
			
			echo json_encode([
				'success' => true,
				'message' => ucfirst($type) . ' удалён',
			]);
			break;
    
    // ============================================
    // НЕИЗВЕСТНОЕ ДЕЙСТВИЕ
    // ============================================
    
    default:
        echo json_encode(['success' => false, 'message' => 'Неизвестное действие: ' . $action]);
}