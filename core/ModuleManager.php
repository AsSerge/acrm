<?php
/**
 * Класс ModuleManager - управление модулями
 * Сканирование, регистрация, загрузка, фильтрация по доступу
 * + Автоочистка модулей, которых нет на диске
 */

class ModuleManager
{
    private $db;
    private $modules = [];
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->loadModules();
    }
    
    /**
     * Загрузка всех модулей из реестра
     */
    private function loadModules()
    {
        $modules = $this->db->fetchAll(
            "SELECT * FROM modules_registry ORDER BY sort_order, module_name"
        );
        
        foreach ($modules as $module) {
            $this->modules[$module['module_name']] = $module;
        }
    }
    
    /**
     * Получение модуля по имени папки
     */
    public function getModule($name)
    {
        return $this->modules[$name] ?? null;
    }
    
    /**
     * Получение модуля по slug
     */
    public function getModuleBySlug($slug)
    {
        foreach ($this->modules as $module) {
            if ($module['slug'] === $slug && $module['is_active']) {
                return $module;
            }
        }
        return null;
    }
    
    /**
     * Получение всех модулей
     */
    public function getAllModules()
    {
        return $this->modules;
    }
    
    /**
     * Получение модулей по типу
     */
    public function getModulesByType($type)
    {
        $result = [];
        foreach ($this->modules as $name => $module) {
            if ($type === 'frontend' && $module['is_frontend']) {
                $result[$name] = $module;
            } elseif ($type === 'admin' && $module['is_admin']) {
                $result[$name] = $module;
            }
        }
        return $result;
    }
    
    /**
     * Получение активных фронтенд-модулей
     */
    public function getFrontendModules($activeOnly = true)
    {
        $result = [];
        foreach ($this->modules as $name => $module) {
            if ($module['is_frontend'] && (!$activeOnly || $module['is_active'])) {
                $result[$name] = $module;
            }
        }
        return $result;
    }
    
    /**
     * Проверка доступа к модулю по группам пользователя
     */
    public function hasAccess($module, $user = null)
    {
        if ($module['access_groups'] === null || $module['access_groups'] === '') {
            return true;
        }
        
        $allowedGroups = json_decode($module['access_groups'], true);
        if (!is_array($allowedGroups) || empty($allowedGroups)) {
            return true;
        }
        
        if (!$user) {
            return false;
        }
        
        $userGroupId = $user['group_id'] ?? null;
        return $userGroupId && in_array($userGroupId, $allowedGroups);
    }
    
    /**
     * Автоматическое сканирование папки /modules/
     * Регистрирует новые модули, обновляет существующие,
     * УДАЛЯЕТ мёртвые записи (которых нет на диске)
     */
    public function scanModules()
    {
        if (!is_dir(MODULES_DIR)) {
            return ['added' => 0, 'updated' => 0, 'deleted' => 0, 'total' => 0];
        }
        
        $items = scandir(MODULES_DIR);
        $added = 0;
        $updated = 0;
        $total = 0;
        $foundModules = [];
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $fullPath = MODULES_DIR . '/' . $item;
            if (!is_dir($fullPath)) continue;
            
            // Проверяем наличие index.php
            $indexFile = $fullPath . '/index.php';
            if (!file_exists($indexFile)) continue;
            
            $total++;
            $foundModules[] = $item;
            
            // Проверяем, зарегистрирован ли модуль
            $existing = $this->db->fetchOne(
                "SELECT id FROM modules_registry WHERE module_name = ?",
                [$item]
            );
            
            $modulePath = '/modules/' . $item . '/';
            
            if (!$existing) {
                // Регистрируем новый модуль
                $this->db->insert('modules_registry', [
                    'module_name' => $item,
                    'title' => $this->humanizeName($item),
                    'slug' => $this->generateSlug($item),
                    'module_type' => 'frontend',
                    'module_path' => $modulePath,
                    'is_active' => 1,
                    'is_frontend' => 1,
                    'is_admin' => 0,
                    'template' => 'default',
                    'version' => '1.0.0',
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                $added++;
            } else {
                // Обновляем путь
                $this->db->update(
                    'modules_registry',
                    ['module_path' => $modulePath],
                    'module_name = ?',
                    [$item]
                );
                $updated++;
            }
        }
        
        // ============================================
        // АВТООЧИСТКА: удаляем записи, которых нет на диске
        // ============================================
        $deleted = $this->cleanOrphanedModules($foundModules);
        
        // Перезагружаем список
        $this->loadModules();
        
        return [
            'added' => $added,
            'updated' => $updated,
            'deleted' => $deleted,
            'total' => $total
        ];
    }
    
    /**
     * Удаление записей модулей, которых нет на диске
     * 
     * @param array $foundModules - список модулей, найденных на диске
     * @return int - количество удалённых записей
     */
    private function cleanOrphanedModules($foundModules)
    {
        // Получаем все фронтенд-модули из БД
        $dbModules = $this->db->fetchAll(
            "SELECT id, module_name FROM modules_registry WHERE is_frontend = 1"
        );
        
        $deleted = 0;
        
        foreach ($dbModules as $dbModule) {
            // Если модуля нет среди найденных на диске — удаляем
            if (!in_array($dbModule['module_name'], $foundModules)) {
                // Проверяем, привязан ли модуль к меню
                $menuCount = $this->db->fetchOne(
                    "SELECT COUNT(*) as count FROM menu_structure WHERE module_link = ?",
                    ['/' . $dbModule['module_name']]
                )['count'] ?? 0;
                
                // Если модуль привязан к меню — отвязываем пункты (обнуляем ссылку)
                if ($menuCount > 0) {
                    $this->db->update(
                        'menu_structure',
                        ['module_link' => 'javascript:void(0)', 'link_type' => 'none'],
                        'module_link = ?',
                        ['/' . $dbModule['module_name']]
                    );
                }
                
                // Удаляем запись модуля
                $this->db->delete('modules_registry', 'id = ?', [$dbModule['id']]);
                $deleted++;
            }
        }
        
        return $deleted;
    }
    
    /**
     * Регистрация модуля вручную
     */
    public function registerModule($moduleName, $data = [])
    {
        $modulePath = MODULES_DIR . '/' . $moduleName;
        if (!is_dir($modulePath) || !file_exists($modulePath . '/index.php')) {
            return false;
        }
        
        $existing = $this->db->fetchOne(
            "SELECT id FROM modules_registry WHERE module_name = ?",
            [$moduleName]
        );
        
        if ($existing) {
            return false;
        }
        
        $defaults = [
            'module_name' => $moduleName,
            'title' => $data['title'] ?? $this->humanizeName($moduleName),
            'slug' => $data['slug'] ?? $this->generateSlug($moduleName),
            'module_type' => $data['module_type'] ?? 'frontend',
            'module_path' => '/modules/' . $moduleName . '/',
            'is_active' => $data['is_active'] ?? 1,
            'is_frontend' => $data['is_frontend'] ?? 1,
            'is_admin' => $data['is_admin'] ?? 0,
            'template' => $data['template'] ?? 'default',
            'version' => $data['version'] ?? '1.0.0',
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'access_groups' => $data['access_groups'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('modules_registry', $defaults);
        $this->loadModules();
        
        return true;
    }
    
    /**
     * Обновление метаданных модуля
     */
    public function updateModule($moduleName, $data)
    {
        return $this->db->update(
            'modules_registry',
            $data,
            'module_name = ?',
            [$moduleName]
        );
    }
    
    /**
     * Удаление модуля из реестра (не с диска)
     */
    public function unregisterModule($moduleName)
    {
        $result = $this->db->delete('modules_registry', 'module_name = ?', [$moduleName]);
        $this->loadModules();
        return $result;
    }
    
    /**
     * Включение/отключение модуля
     */
    public function setModuleStatus($moduleName, $isActive)
    {
        return $this->updateModule($moduleName, ['is_active' => $isActive ? 1 : 0]);
    }
    
    /**
     * Загрузка модуля (получение путей к файлам)
     */
    public function loadModule($moduleName)
    {
        $module = $this->getModule($moduleName);
        if (!$module) {
            return false;
        }
        
        $modulePath = ROOT_DIR . $module['module_path'];
        
        if (!is_dir($modulePath)) {
            return false;
        }
        
        $indexFile = $modulePath . 'index.php';
        if (!file_exists($indexFile)) {
            return false;
        }
        
        $cssFile = $modulePath . 'style.css';
        $jsFile = $modulePath . 'script.js';
        
        return [
            'module' => $module,
            'path' => $modulePath,
            'index' => $indexFile,
            'css' => file_exists($cssFile) ? $cssFile : null,
            'js' => file_exists($jsFile) ? $jsFile : null,
            'has_css' => file_exists($cssFile),
            'has_js' => file_exists($jsFile)
        ];
    }
    
    /**
     * Проверка, существует ли физический файл модуля
     */
    public function moduleExistsOnDisk($moduleName)
    {
        $path = MODULES_DIR . '/' . $moduleName . '/index.php';
        return file_exists($path);
    }
    
    /**
     * Генерация slug из имени
     */
    private function generateSlug($name)
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        if (empty($slug)) {
            $slug = 'module';
        }
        
        $originalSlug = $slug;
        $counter = 1;
        $maxAttempts = 100;
        
        while ($this->slugExists($slug) && $counter <= $maxAttempts) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        if ($counter > $maxAttempts) {
            $slug = $originalSlug . '-' . time();
        }
        
        return $slug;
    }
    
    /**
     * Проверка, существует ли модуль с таким slug
     */
    public function slugExists($slug, $excludeName = null)
    {
        $sql = "SELECT id FROM modules_registry WHERE slug = ?";
        $params = [$slug];
        
        if ($excludeName) {
            $sql .= " AND module_name != ?";
            $params[] = $excludeName;
        }
        
        $result = $this->db->fetchOne($sql, $params);
        return !empty($result);
    }
    
    /**
     * Человекочитаемое имя из имени папки
     */
    private function humanizeName($name)
    {
        $name = str_replace(['-', '_'], ' ', $name);
        $name = ucwords($name);
        return $name;
    }
    
    /**
     * Получение модулей, доступных пользователю
     */
    public function getAccessibleModules($user = null)
    {
        $result = [];
        foreach ($this->modules as $name => $module) {
            if ($module['is_frontend'] && $module['is_active'] && $this->hasAccess($module, $user)) {
                $result[$name] = $module;
            }
        }
        return $result;
    }
}