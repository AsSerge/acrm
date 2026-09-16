<?php
/**
 * Класс MenuBuilder - построение дерева меню
 */

class MenuBuilder
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Получение всех пунктов меню в виде дерева с учётом прав доступа
     * 
     * @param string|null $menuType - УСТАРЕЛО, оставлено для совместимости
     * @param bool $activeOnly - только активные
     * @param array|null $user - данные пользователя
     * @param bool $forceShow - показывать все пункты (для админки)
     */
    public function getTree($menuType = null, $activeOnly = true, $user = null, $forceShow = false)
    {
        $sql = "SELECT * FROM menu_structure WHERE 1=1";
        $params = [];
        
        if ($activeOnly) {
            $sql .= " AND is_active = 1";
        }
        
        $sql .= " ORDER BY parent_id, sort_order, id";
        
        $items = $this->db->fetchAll($sql, $params);
        
        $filteredItems = [];
        foreach ($items as $item) {
            if ($this->hasAccess($item, $user, $forceShow)) {
                $filteredItems[] = $item;
            }
        }
        
        return $this->buildTree($filteredItems);
    }
    
    /**
     * Проверка доступа к пункту меню
     */
    private function hasAccess($item, $user = null, $forceShow = false)
    {
        if ($forceShow) {
            return true;
        }
        
        if ($item['access_groups'] === null || $item['access_groups'] === '') {
            return true;
        }
        
        if (!$user) {
            return false;
        }
        
        $allowedGroups = json_decode($item['access_groups'], true);
        if (!is_array($allowedGroups) || empty($allowedGroups)) {
            return false;
        }
        
        $userGroupId = $user['group_id'] ?? null;
        return $userGroupId && in_array($userGroupId, $allowedGroups);
    }
    
    /**
     * Построение дерева из плоского списка
     */
    private function buildTree($items, $parentId = null)
    {
        $branch = [];
        
        foreach ($items as $item) {
            if ($item['parent_id'] == $parentId) {
                $children = $this->buildTree($items, $item['id']);
                if ($children) {
                    $item['children'] = $children;
                }
                $branch[] = $item;
            }
        }
        
        return $branch;
    }
    
    /**
     * Получение пункта меню по ID
     */
    public function getItem($id)
    {
        return $this->db->fetchOne(
            "SELECT * FROM menu_structure WHERE id = ?",
            [$id]
        );
    }
    
    /**
     * Создание пункта меню
     */
    public function create($data)
    {
        return $this->db->insert('menu_structure', $data);
    }
    
    /**
     * Обновление пункта меню
     */
    public function update($id, $data)
    {
        return $this->db->update('menu_structure', $data, 'id = ?', [$id]);
    }
    
    /**
     * Удаление пункта меню
     */
    public function delete($id)
    {
        return $this->db->delete('menu_structure', 'id = ?', [$id]);
    }
    
    /**
     * Проверка, есть ли дочерние пункты
     */
    public function hasChildren($id)
    {
        $count = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM menu_structure WHERE parent_id = ?",
            [$id]
        );
        return $count['count'] > 0;
    }
    
    /**
     * Получение выпадающего списка для выбора родителя
     */
    public function getParentOptions($excludeId = null)
    {
        $sql = "SELECT id, parent_id, menu_title FROM menu_structure WHERE 1=1";
        $params = [];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $sql .= " ORDER BY parent_id, id";
        
        $items = $this->db->fetchAll($sql, $params);
        $tree = $this->buildTree($items);
        
        return $this->flattenOptions($tree);
    }
    
    /**
     * Преобразование дерева в плоский список для <select>
     */
	private function flattenOptions($tree, $prefix = '')
	{
		$options = [];
		
		foreach ($tree as $item) {
			// Сохраняем ID пункта как ключ массива
			$options[$item['id']] = $prefix . $item['menu_title'];
			
			if (isset($item['children']) && !empty($item['children'])) {
				// Рекурсивно получаем дочерние пункты
				$children = $this->flattenOptions($item['children'], $prefix . '— ');
				
				// ВАЖНО: сохраняем ключи (ID), НЕ используем array_merge!
				foreach ($children as $childId => $childTitle) {
					$options[$childId] = $childTitle;
				}
			}
		}
		
		return $options;
	}
    
    /**
     * Проверка доступа к странице по её URL
     */
    public function hasPageAccess($url, $user = null)
    {
        $item = $this->db->fetchOne(
            "SELECT * FROM menu_structure WHERE module_link = ?",
            [$url]
        );
        
        if (!$item) {
            return true;
        }
        
        return $this->hasAccess($item, $user);
    }
}