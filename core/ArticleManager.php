<?php
/**
 * Класс ArticleManager - управление статьями
 * 
 * Использование:
 *   $article = ArticleManager::getById(5);
 *   $article = ArticleManager::getBySlug('moya-statya');
 *   ArticleManager::create([...]);
 */

class ArticleManager
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    // ============================================
    // ПОЛУЧЕНИЕ
    // ============================================
    
    /**
     * Получить все статьи
     * 
     * @param array $options - опции фильтрации:
     *   - active_only: bool (только активные)
     *   - published_only: bool (только опубликованные)
     *   - limit: int
     *   - offset: int
     * @return array
     */
    public function getAll($options = [])
    {
        $sql = "SELECT a.*, 
                       g.title as gallery_title
                FROM block_articles a
                LEFT JOIN galleries g ON a.gallery_id = g.id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($options['active_only'])) {
            $sql .= " AND a.is_active = 1";
        }
        
        if (!empty($options['published_only'])) {
            $sql .= " AND a.published_at IS NOT NULL AND a.published_at <= NOW()";
        }
        
        $sql .= " ORDER BY a.published_at DESC, a.created_at DESC";
        
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
     * Получить статью по ID
     * 
     * @param int $id
     * @return array|null
     */
    public function getById($id)
    {
        return $this->db->fetchOne(
            "SELECT a.*, 
                    g.title as gallery_title
             FROM block_articles a
             LEFT JOIN galleries g ON a.gallery_id = g.id
             WHERE a.id = ?",
            [$id]
        );
    }
    
    /**
     * Получить статью по slug
     * 
     * @param string $slug
     * @param bool $publishedOnly - только опубликованные
     * @return array|null
     */
    public function getBySlug($slug, $publishedOnly = true)
    {
        $sql = "SELECT a.*, 
                       g.title as gallery_title
                FROM block_articles a
                LEFT JOIN galleries g ON a.gallery_id = g.id
                WHERE a.slug = ? AND a.is_active = 1";
        
        if ($publishedOnly) {
            $sql .= " AND a.published_at IS NOT NULL AND a.published_at <= NOW()";
        }
        
        return $this->db->fetchOne($sql, [$slug]);
    }
    
    /**
     * Количество статей
     * 
     * @param array $options
     * @return int
     */
    public function count($options = [])
    {
        $sql = "SELECT COUNT(*) as count FROM block_articles WHERE 1=1";
        $params = [];
        
        if (!empty($options['active_only'])) {
            $sql .= " AND is_active = 1";
        }
        
        if (!empty($options['published_only'])) {
            $sql .= " AND published_at IS NOT NULL AND published_at <= NOW()";
        }
        
        return $this->db->fetchOne($sql, $params)['count'] ?? 0;
    }
    
    // ============================================
    // СОЗДАНИЕ / ОБНОВЛЕНИЕ
    // ============================================
    
    /**
     * Создать статью
     * 
     * @param array $data
     * @return int - ID новой статьи
     */
    public function create($data)
    {
        // Генерируем slug, если не указан
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['title']);
        }
        
        // Дата создания
        $data['created_at'] = date('Y-m-d H:i:s');
        
        // Если published_at не указан — ставим текущее время
        if (!isset($data['published_at'])) {
            $data['published_at'] = date('Y-m-d H:i:s');
        }
        
        return $this->db->insert('block_articles', $data);
    }
    
    /**
     * Обновить статью
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data)
    {
        return $this->db->update(
            'block_articles',
            $data,
            'id = ?',
            [$id]
        );
    }
    
    /**
     * Удалить статью
     * 
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        return $this->db->delete('block_articles', 'id = ?', [$id]) > 0;
    }
    
    // ============================================
    // SLUG
    // ============================================
    
    /**
     * Проверить, существует ли статья с таким slug
     * 
     * @param string $slug
     * @param int $excludeId - исключить ID (для редактирования)
     * @return bool
     */
    public function slugExists($slug, $excludeId = null)
    {
        $sql = "SELECT id FROM block_articles WHERE slug = ?";
        $params = [$slug];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->db->fetchOne($sql, $params);
        return !empty($result);
    }
    
    /**
     * Генерация slug из заголовка
     * 
     * @param string $title
     * @return string
     */
    public function generateSlug($title)
    {
        $slug = $this->transliterate($title);
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        if (empty($slug)) {
            $slug = 'article';
        }
        
        // Проверяем уникальность
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
     * Транслитерация кириллицы в латиницу
     * 
     * @param string $text
     * @return string
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
     * Загрузить обложку статьи
     * 
     * @param int $articleId
     * @param array $file - $_FILES['cover']
     * @return array
     */
    public function uploadCover($articleId, $file)
    {
        $article = $this->getById($articleId);
        
        if (!$article) {
            return ['success' => false, 'message' => 'Статья не найдена'];
        }
        
        // Загружаем через ImageUploader
        $result = ImageUploader::upload($file, 'articles');
        
        if (!$result['success']) {
            return $result;
        }
        
        // Удаляем старую обложку
        if (!empty($article['cover'])) {
            ImageUploader::delete($article['cover']);
        }
        
        // Обновляем запись
        $this->update($articleId, ['cover' => $result['path']]);
        
        return [
            'success' => true,
            'path' => $result['path'],
            'sizes' => $result['sizes'] ?? [],
            'message' => 'Обложка загружена'
        ];
    }
    
    /**
     * Удалить обложку статьи
     * 
     * @param int $articleId
     * @return bool
     */
    public function deleteCover($articleId)
    {
        $article = $this->getById($articleId);
        
        if (!$article || empty($article['cover'])) {
            return false;
        }
        
        // Удаляем файл
        ImageUploader::delete($article['cover']);
        
        // Очищаем поле
        $this->update($articleId, ['cover' => null]);
        
        return true;
    }
    
    // ============================================
    // ГАЛЕРЕЯ
    // ============================================
    
    /**
     * Привязать галерею к статье
     * 
     * @param int $articleId
     * @param int|null $galleryId
     * @return bool
     */
    public function setGallery($articleId, $galleryId)
    {
        if ($galleryId === 0 || $galleryId === '') {
            $galleryId = null;
        }
        
        return $this->update($articleId, ['gallery_id' => $galleryId]);
    }
}