<?php
/**
 * Классы для админ-панели
 */

require_once __DIR__ . '/../classes.php';

class AdminAuth extends Auth
{
    /**
     * Проверка, является ли пользователь Супер-админом
     * Проверяет флаг is_super_admin в таблице users
     */
    public function isSuperAdmin()
    {
        if (!$this->isLoggedIn()) return false;
        $user = $this->getUser();
        return $user && isset($user['is_super_admin']) && $user['is_super_admin'] == 1;
    }
    
    /**
     * Проверка, является ли пользователь администратором (включая супер-админа)
     */
    public function isAdmin()
    {
        if (!$this->isLoggedIn()) return false;
        $user = $this->getUser();
        return $user && $user['group_type'] === 'admin';
    }
    
    /**
     * Проверка, имеет ли пользователь доступ к управлению пользователями
     * Только Супер-админ
     */
    public function canManageUsers()
    {
        return $this->isSuperAdmin();
    }
    
    /**
     * Проверка, имеет ли пользователь доступ к управлению группами
     * Только Супер-админ
     */
    public function canManageGroups()
    {
        return $this->isSuperAdmin();
    }
    
    /**
     * Проверка доступа к админ-модулю
     */
    public function hasAdminAccess($moduleName, $requiredLevel = 'view')
    {
        // Супер-админ имеет полный доступ ко всему
        if ($this->isSuperAdmin()) {
            return true;
        }
        
        return $this->hasAccess($moduleName, $requiredLevel);
    }
    
    /**
     * Вход в админку
     */
    public function adminLogin($username, $password)
    {
        $db = Database::getInstance();
        $user = $db->fetchOne(
            "SELECT u.*, ug.group_name, ug.group_type 
             FROM users u 
             JOIN user_groups ug ON u.group_id = ug.id 
             WHERE (u.username = ? OR u.email = ?) AND u.is_active = 1",
            [$username, $username]
        );
        
        if (!$user) return false;
        if (!password_verify($password, $user['password_hash'])) return false;
        if ($user['group_type'] !== 'admin') return false;
        
        // Обновляем время последнего входа
        $db->update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);
        
        // Сохраняем в сессии
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_group'] = $user['group_id'];
        
        $this->user = $user;
        $this->isLoggedIn = true;
        $this->loadPermissions();
        
        $this->logAction('admin_login', 'auth', 'Admin ' . $user['username'] . ' logged in');
        
        return true;
    }
}