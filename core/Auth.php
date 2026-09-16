<?php
/**
 * Класс Auth - авторизация и управление доступом (RBAC)
 */

class Auth
{
    private $db;
    protected $user = null;
    protected $isLoggedIn = false;
    private $userPermissions = [];
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->initSession();
        $this->loadUser();
    }
    
    /**
     * Инициализация сессии
     */
    private function initSession()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Загрузка текущего пользователя
     */
    private function loadUser()
    {
        if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
            $user = $this->db->fetchOne(
                "SELECT u.*, ug.group_name, ug.group_type 
                 FROM users u 
                 JOIN user_groups ug ON u.group_id = ug.id 
                 WHERE u.id = ? AND u.is_active = 1",
                [$_SESSION['user_id']]
            );
            
            if ($user) {
                $this->user = $user;
                $this->isLoggedIn = true;
                $this->loadPermissions();
                return true;
            } else {
                unset($_SESSION['user_id']);
            }
        }
        return false;
    }
    
    /**
     * Загрузка прав доступа пользователя
     */
    protected function loadPermissions()
    {
        if (!$this->user) return;
        
        $permissions = $this->db->fetchAll(
            "SELECT module_name, access_level 
             FROM module_access 
             WHERE group_id = ?",
            [$this->user['group_id']]
        );
        
        $this->userPermissions = [];
        foreach ($permissions as $perm) {
            $this->userPermissions[$perm['module_name']] = $perm['access_level'];
        }
    }
    
    /**
     * Проверка авторизации
     */
    public function isLoggedIn()
    {
        return $this->isLoggedIn;
    }
    
    /**
     * Получение данных текущего пользователя
     */
    public function getUser()
    {
        return $this->user;
    }
    
    /**
     * Проверка, является ли пользователь администратором
     * (группа типа 'admin')
     */
    public function isAdmin()
    {
        if (!$this->isLoggedIn()) return false;
        $user = $this->getUser();
        return $user && isset($user['group_type']) && $user['group_type'] === 'admin';
    }
    
    /**
     * Проверка права доступа к модулю
     */
    public function hasAccess($moduleName, $requiredLevel = 'view')
    {
        // Супер-админ (is_super_admin = 1) имеет полный доступ ко всему
        if ($this->user && isset($this->user['is_super_admin']) && $this->user['is_super_admin'] == 1) {
            return true;
        }
        
        // Проверяем права
        $level = $this->userPermissions[$moduleName] ?? 'none';
        $levels = ['none' => 0, 'view' => 1, 'edit' => 2, 'full' => 3];
        
        return ($levels[$level] ?? 0) >= ($levels[$requiredLevel] ?? 0);
    }
    
    /**
     * Авторизация пользователя
     */
    public function login($username, $password)
    {
        $user = $this->db->fetchOne(
            "SELECT u.*, ug.group_name, ug.group_type 
             FROM users u 
             JOIN user_groups ug ON u.group_id = ug.id 
             WHERE (u.username = ? OR u.email = ?) AND u.is_active = 1",
            [$username, $username]
        );
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Обновляем время последнего входа
            $this->db->update(
                'users',
                ['last_login' => date('Y-m-d H:i:s')],
                'id = ?',
                [$user['id']]
            );
            
            // Сохраняем в сессии
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_group'] = $user['group_id'];
            
            $this->user = $user;
            $this->isLoggedIn = true;
            $this->loadPermissions();
            
            $this->logAction('login', 'auth', 'User ' . $user['username'] . ' logged in');
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Выход из системы
     */
    public function logout()
    {
        if ($this->user) {
            $this->logAction('logout', 'auth', 'User ' . $this->user['username'] . ' logged out');
        }
        
        $_SESSION = [];
        session_destroy();
        $this->user = null;
        $this->isLoggedIn = false;
        $this->userPermissions = [];
    }
    
    /**
     * Логирование действий
     */
    protected function logAction($action, $module, $description)
    {
        $userId = $this->user ? $this->user['id'] : null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        $this->db->insert('audit_log', [
            'user_id' => $userId,
            'action' => $action,
            'module' => $module,
            'description' => $description,
            'ip_address' => $ip,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Генерация CSRF-токена
     */
    public function generateCsrfToken()
    {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_time'] = time();
        return $token;
    }
    
    /**
     * Проверка CSRF-токена
     */
    public function verifyCsrfToken($token)
    {
        if (!isset($_SESSION['csrf_token'])) return false;
        if ($_SESSION['csrf_token'] !== $token) return false;
        if ((time() - $_SESSION['csrf_token_time']) > CSRF_TOKEN_LIFETIME) return false;
        return true;
    }
    
    /**
     * Хеширование пароля
     */
    public function hashPassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}