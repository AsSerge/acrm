<?php
/**
 * Класс ContentManager - управление контентом (статьи и новости)
 * 
 * Единый класс для работы с таблицей block_content.
 * Тип контента определяется полем `type`:
 *   - article — статья (галерея в тексте)
 *   - news    — новость (галерея привязана)
 * 
 * Использование:
 *   $article = $contentManager->getById(5);
 *   $news = $contentManager->getBySlug('moya-novost', 'news');
 *   $contentManager->create(['type' => 'article', 'title' => '...']);
 */

class ContentManager
{
    private $db;
    
    // Разрешённые типы
    private $allowedTypes = ['article', 'news'];
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    // ============================================
    // ПОЛУЧЕНИЕ
    // ============================================
    
    /**
     * Получить весь контент с фильтрацией
     * 
     * @param array $options:
     *   - type: string ('article' / 'news') — обязательно для фильтра
     *   - active_only: bool
     *   - published_only: bool
     *   - search: string
     *   - limit: int
     *   - offset: int
     * @return array
     */
    public function getAll($options = [])
    {
        $sql = "SELECT c.*, 
                       g.title as gallery_title,
                       g.id as gallery_real_id
                FROM block_content c
                LEFT JOIN galleries g ON c.gallery_id = g.id
                WHERE 1=1";
        
        $params = [];
        
        // Фильтр по типу
        if (!empty($options['type']) && in_array($options['type'], $this->allowedTypes)) {
            $sql .= " AND c.type = ?";
            $params[] = $options['type'];
        }
        
        // Только активные
        if (!empty($options['active_only'])) {
            $sql .= " AND c.is_active = 1";
        }
        
        // Только опубликованные
        if (!empty($options['published_only'])) {
            $sql .= " AND c.published_at IS NOT NULL AND c.published_at <= NOW()";
        }
        
        // Поиск
        if (!empty($options['search'])) {
            $sql .= " AND (c.title LIKE ? OR c.slug LIKE ?)";
            $params[] = '%' . $options['search'] . '%';
            $params[] = '%' . $options['search'] . '%';
        }
        
        // Сортировка
        $sql .= " ORDER BY c.published_at DESC, c.created_at DESC";
        
        // Пагинация
        if (!empty($options['limit'])) {
            $sql .= " LIMIT ?";
            $params[] = (int)$options['limit'];
            
            if (isset($options['offset'])) {
                $sql .= " OFFSET ?";
                $params[] = (int)$options['offset'];
            }
        }
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Количество записей с фильтрацией
     * 
     * @param array $options
     * @return int
     */
    public function count($options = [])
    {
        $sql = "SELECT COUNT(*) as count FROM block_content WHERE 1=1";
        $params = [];
        
        if (!empty($options['type']) && in_array($options['type'], $this->allowedTypes)) {
            $sql .= " AND type = ?";
            $params[] = $options['type'];
        }
        
        if (!empty($options['active_only'])) {
            $sql .= " AND is_active = 1";
        }
        
        if (!empty($options['published_only'])) {
            $sql .= " AND published_at IS NOT NULL AND published_at <= NOW()";
        }
        
        if (!empty($options['search'])) {
            $sql .= " AND (title LIKE ? OR slug LIKE ?)";
            $params[] = '%' . $options['search'] . '%';
            $params[] = '%' . $options['search'] . '%';
        }
        
        return $this->db->fetchOne($sql, $params)['count'] ?? 0;
    }
    
    /**
     * Получить запись по ID
     * 
     * @param int $id
     * @return array|null
     */
    public function getById($id)
    {
        return $this->db->fetchOne(
            "SELECT c.*, 
                    g.title as gallery_title
             FROM block_content c
             LEFT JOIN galleries g ON c.gallery_id = g.id
             WHERE c.id = ?",
            [$id]
        );
    }
    
    /**
     * Получить запись по slug и типу
     * 
     * @param string $slug
     * @param string $type
     * @param bool $publishedOnly
     * @return array|null
     */
    public function getBySlug($slug, $type, $publishedOnly = true)
    {
        if (!in_array($type, $this->allowedTypes)) {
            return null;
        }
        
        $sql = "SELECT c.*, 
                       g.title as gallery_title
                FROM block_content c
                LEFT JOIN galleries g ON c.gallery_id = g.id
                WHERE c.slug = ? AND c.type = ? AND c.is_active = 1";
        
        $params = [$slug, $type];
        
        if ($publishedOnly) {
            $sql .= " AND c.published_at IS NOT NULL AND c.published_at <= NOW()";
        }
        
        return $this->db->fetchOne($sql, $params);
    }
    
    // ============================================
    // СОЗДАНИЕ
    // ============================================
    
    /**
     * Создать запись
     * 
     * @param array $data
     * @return array ['success' => bool, 'id' => int, 'message' => string]
     */
    public function create($data)
    {
        // ============================================
        // 1. ВАЛИДАЦИЯ ТИПА
        // ============================================
        
        $type = $data['type'] ?? 'article';
        
        if (!in_array($type, $this->allowedTypes)) {
            return ['success' => false, 'message' => 'Недопустимый тип контента'];
        }
        
        // ============================================
        // 2. ВАЛИДАЦИЯ ЗАГОЛОВКА
        // ============================================
        
        if (empty($data['title'])) {
            return ['success' => false, 'message' => 'Заголовок обязателен'];
        }
        
        if (strlen($data['title']) < 3) {
            return ['success' => false, 'message' => 'Заголовок должен содержать минимум 3 символа'];
        }
        
        // ============================================
        // 3. ВАЛИДАЦИЯ ПО ТИПУ
        // ============================================
        
        if ($type === 'news') {
            // Для новости — обложка обязательна
            if (empty($data['cover'])) {
                return ['success' => false, 'message' => 'Для новости обязательна обложка'];
            }
        } elseif ($type === 'article') {
            // Для статьи — контент обязателен
            if (empty($data['content'])) {
                return ['success' => false, 'message' => 'Для статьи обязателен контент'];
            }
        }
        
        // ============================================
        // 4. ГЕНЕРАЦИЯ SLUG
        // ============================================
        
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['title'], $type);
        } else {
            if ($this->slugExists($data['slug'], $type)) {
                return ['success' => false, 'message' => 'Запись с таким slug уже существует'];
            }
        }
        
        // ============================================
        // 5. ДАТА ПУБЛИКАЦИИ
        // ============================================
        
        if (empty($data['published_at'])) {
            $data['published_at'] = date('Y-m-d H:i:s');
        }
        
        // ============================================
        // 6. СОЗДАНИЕ
        // ============================================
        
        $data['created_at'] = date('Y-m-d H:i:s');
        
        try {
            $id = $this->db->insert('block_content', $data);
            
            return [
                'success' => true,
                'id' => $id,
                'message' => 'Запись создана'
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Ошибка создания: ' . $e->getMessage()];
        }
    }
    
    /**
     * Обновить запись
     * 
     * @param int $id
     * @param array $data
     * @return array
     */
    public function update($id, $data)
    {
        $content = $this->getById($id);
        
        if (!$content) {
            return ['success' => false, 'message' => 'Запись не найдена'];
        }
        
        $type = $data['type'] ?? $content['type'];
        
        // Валидация по типу
        if ($type === 'news') {
            $cover = $data['cover'] ?? $content['cover'];
            if (empty($cover)) {
                return ['success' => false, 'message' => 'Для новости обязательна обложка'];
            }
        }
        
        // Проверка slug
        if (!empty($data['slug'])) {
            if ($this->slugExists($data['slug'], $type, $id)) {
                return ['success' => false, 'message' => 'Запись с таким slug уже существует'];
            }
        }
        
        try {
            $this->db->update('block_content', $data, 'id = ?', [$id]);
            return ['success' => true, 'message' => 'Запись обновлена'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Ошибка обновления: ' . $e->getMessage()];
        }
    }
    
    /**
     * Удалить запись
     * 
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        $content = $this->getById($id);
        
        if (!$content) {
            return false;
        }
        
        // Удаляем обложку
        if (!empty($content['cover'])) {
            ImageUploader::delete($content['cover']);
        }
        
        return $this->db->delete('block_content', 'id = ?', [$id]) > 0;
    }
    
    // ============================================
    // SLUG
    // ============================================
    
    /**
     * Проверить, существует ли запись с таким slug и типом
     * 
     * @param string $slug
     * @param string $type
     * @param int $excludeId
     * @return bool
     */
    public function slugExists($slug, $type, $excludeId = null)
    {
        $sql = "SELECT id FROM block_content WHERE slug = ? AND type = ?";
        $params = [$slug, $type];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->db->fetchOne($sql, $params);
        return !empty($result);
    }
    
    /**
     * Генерация slug из заголовка (с транслитерацией)
     * 
     * @param string $title
     * @param string $type
     * @return string
     */
    public function generateSlug($title, $type = 'article')
    {
        $slug = $this->transliterate($title);
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        if (empty($slug)) {
            $slug = 'content';
        }
        
        // Проверяем уникальность в рамках типа
        $originalSlug = $slug;
        $counter = 1;
        $maxAttempts = 100;
        
        while ($this->slugExists($slug, $type) && $counter <= $maxAttempts) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        if ($counter > $maxAttempts) {
            $slug = $originalSlug . '-' . time();
        }
        
        return $slug;
    }
    
    /**
     * Транслитерация кириллицы
     */
    private function transliterate($text)
    {
        $map = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
            'е' => 'e', 'ё' => 'yo', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
            'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
            'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch',
            'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '',
            'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
            'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D',
            'Е' => 'E', 'Ё' => 'Yo', 'Ж' => 'Zh', 'З' => 'Z', 'И' => 'I',
            'Й' => 'Y', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N',
            'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T',
            'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'Ts', 'Ч' => 'Ch',
            'Ш' => 'Sh', 'Щ' => 'Sch', 'Ъ' => '', 'Ы' => 'Y', 'Ь' => '',
            'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya',
            ' ' => '-', '_' => '-', '/' => '-', '.' => '-',
        ];
        
        return strtr($text, $map);
    }
    
    // ============================================
    // ОБЛОЖКА
    // ============================================
    
    /**
     * Загрузить обложку
     */
    public function uploadCover($id, $file)
    {
        $content = $this->getById($id);
        
        if (!$content) {
            return ['success' => false, 'message' => 'Запись не найдена'];
        }
        
        // Загружаем через ImageUploader
        $folder = ($content['type'] === 'news') ? 'news' : 'articles';
        $result = ImageUploader::upload($file, $folder);
        
        if (!$result['success']) {
            return $result;
        }
        
        // Удаляем старую обложку
        if (!empty($content['cover'])) {
            ImageUploader::delete($content['cover']);
        }
        
        // Обновляем запись
        $this->db->update('block_content', ['cover' => $result['path']], 'id = ?', [$id]);
        
        return [
            'success' => true,
            'path' => $result['path'],
            'sizes' => $result['sizes'] ?? [],
            'message' => 'Обложка загружена'
        ];
    }
    
    /**
     * Удалить обложку
     */
    public function deleteCover($id)
    {
        $content = $this->getById($id);
        
        if (!$content || empty($content['cover'])) {
            return false;
        }
        
        ImageUploader::delete($content['cover']);
        $this->db->update('block_content', ['cover' => null], 'id = ?', [$id]);
        
        return true;
    }
    
    // ============================================
    // ГАЛЕРЕЯ
    // ============================================
    
    /**
     * Привязать галерею
     */
    public function setGallery($id, $galleryId)
    {
        if ($galleryId === 0 || $galleryId === '') {
            $galleryId = null;
        }
        
        $this->db->update('block_content', ['gallery_id' => $galleryId], 'id = ?', [$id]);
    }
    
    // ============================================
    // ХЕЛПЕРЫ
    // ============================================
    
    /**
     * Получить название типа для отображения
     */
    public function getTypeLabel($type)
    {
        $labels = [
            'article' => 'Статья',
            'news' => 'Новость',
        ];
        
        return $labels[$type] ?? $type;
    }
    
    /**
     * Получить URL записи
     */
    public function getUrl($content)
    {
        $prefix = ($content['type'] === 'news') ? 'news' : 'articles';
        return '/' . $prefix . '/' . $content['slug'];
    }
}