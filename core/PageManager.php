<?php
/**
 * Класс PageManager - управление страницами
 */

class PageManager
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Получение всех страниц
     */
    public function getAll($activeOnly = false)
    {
        $sql = "SELECT * FROM pages WHERE 1=1";
        if ($activeOnly) {
            $sql .= " AND is_active = 1";
        }
        $sql .= " ORDER BY sort_order, id";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Получение страницы по ID
     */
    public function getById($id)
    {
        return $this->db->fetchOne(
            "SELECT * FROM pages WHERE id = ?",
            [$id]
        );
    }
    
	/**
	 * Получение страницы по slug
	 */
	public function getBySlug($slug)
	{
		// Убираем слеши в начале и конце
		$slug = trim($slug, '/');
		
		// Если slug пустой — возвращаем null
		if (empty($slug)) {
			return null;
		}
		
		return $this->db->fetchOne(
			"SELECT * FROM pages WHERE slug = ? AND is_active = 1",
			[$slug]
		);
	}
    
    /**
     * Проверка, есть ли страница с таким slug (для уникальности)
     */
public function slugExists($slug, $excludeId = null)
{
    $sql = "SELECT id FROM pages WHERE slug = ?";
    $params = [$slug];
    
    if ($excludeId) {
        $sql .= " AND id != ?";
        $params[] = $excludeId;
    }
    
    $result = $this->db->fetchOne($sql, $params);
    return $result !== null && !empty($result);
}
    
    /**
     * Создание страницы
     */
    public function create($data)
    {
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['title']);
        }
        
        return $this->db->insert('pages', $data);
    }
    
    /**
     * Обновление страницы
     */
    public function update($id, $data)
    {
        return $this->db->update('pages', $data, 'id = ?', [$id]);
    }
    
    /**
     * Удаление страницы
     */
    public function delete($id)
    {
        return $this->db->delete('pages', 'id = ?', [$id]);
    }
    
    /**
     * Генерация slug из заголовка
     * Теперь public, чтобы можно было вызывать извне
     */
/**
 * Генерация slug из заголовка
 */
public function generateSlug($title)
{
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    
    // Если slug пустой — используем 'page'
    if (empty($slug)) {
        $slug = 'page';
    }
    
    // Проверяем уникальность с защитой от бесконечного цикла
    $originalSlug = $slug;
    $counter = 1;
    $maxAttempts = 100;
    
    while ($this->slugExists($slug) && $counter <= $maxAttempts) {
        $slug = $originalSlug . '-' . $counter;
        $counter++;
    }
    
    // Если достигнут лимит — добавляем timestamp
    if ($counter > $maxAttempts) {
        $slug = $originalSlug . '-' . time();
    }
    
    return $slug;
}
    
    /**
     * Получение списка для выпадающего списка (для меню)
     */
    public function getPageOptions()
    {
        $pages = $this->getAll(false);
        $options = [];
        
        foreach ($pages as $page) {
            $options[$page['id']] = $page['title'] . ' (' . $page['slug'] . ')';
        }
        
        return $options;
    }
}