<?php
/**
 * Класс GalleryManager - управление галереями
 * 
 * Использование:
 *   $gallery = GalleryManager::getById(5);
 *   GalleryManager::addImage(5, $_FILES['image']);
 *   GalleryManager::deleteImage(12);
 */

class GalleryManager
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    // ============================================
    // ГАЛЕРЕИ
    // ============================================
    
    /**
     * Получить все галереи
     * 
     * @param bool $activeOnly - только активные
     * @return array
     */
    public function getAll($activeOnly = false)
    {
        $sql = "SELECT g.*, 
                       (SELECT COUNT(*) FROM gallery_images WHERE gallery_id = g.id) as images_count
                FROM galleries g
                WHERE 1=1";
        
        if ($activeOnly) {
            $sql .= " AND g.is_active = 1";
        }
        
        $sql .= " ORDER BY g.created_at DESC";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Получить галерею по ID (с изображениями)
     * 
     * @param int $id
     * @return array|null
     */
    public function getById($id)
    {
        $gallery = $this->db->fetchOne(
            "SELECT * FROM galleries WHERE id = ?",
            [$id]
        );
        
        if (!$gallery) {
            return null;
        }
        
        $gallery['images'] = $this->getImages($id);
        
        return $gallery;
    }
    
    /**
     * Получить галерею по ID (без изображений)
     * 
     * @param int $id
     * @return array|null
     */
    public function getBasicById($id)
    {
        return $this->db->fetchOne(
            "SELECT * FROM galleries WHERE id = ?",
            [$id]
        );
    }
    
    /**
     * Создать галерею
     * 
     * @param string $title
     * @param string $description
     * @return int - ID новой галереи
     */
    public function create($title, $description = '')
    {
        $id = $this->db->insert('galleries', [
            'title' => $title,
            'description' => $description,
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        // Создаём папку для файлов
        $folder = ROOT_DIR . '/uploads/gallery/' . $id;
        if (!is_dir($folder)) {
            mkdir($folder, 0755, true);
        }
        
        return $id;
    }
    
    /**
     * Обновить метаданные галереи
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data)
    {
        return $this->db->update(
            'galleries',
            $data,
            'id = ?',
            [$id]
        );
    }
    
    /**
     * Удалить галерею + все файлы
     * 
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        $gallery = $this->getBasicById($id);
        
        if (!$gallery) {
            return false;
        }
        
        // Удаляем все изображения из БД и с диска
        $images = $this->getImages($id);
        foreach ($images as $image) {
            $this->deleteImageFile($image['image_path']);
        }
        
        // Удаляем папку галереи
        $folder = ROOT_DIR . '/uploads/gallery/' . $id;
        if (is_dir($folder)) {
            $this->removeDirectory($folder);
        }
        
        // Удаляем запись (CASCADE удалит gallery_images)
        return $this->db->delete('galleries', 'id = ?', [$id]) > 0;
    }
    
    // ============================================
    // ИЗОБРАЖЕНИЯ
    // ============================================
    
    /**
     * Получить изображения галереи
     * 
     * @param int $galleryId
     * @return array
     */
    public function getImages($galleryId)
    {
        return $this->db->fetchAll(
            "SELECT * FROM gallery_images 
             WHERE gallery_id = ? 
             ORDER BY sort_order ASC, id ASC",
            [$galleryId]
        );
    }
    
    /**
     * Добавить изображение в галерею
     * 
     * @param int $galleryId
     * @param array $file - $_FILES['image']
     * @param array $options - ['alt' => '...', 'caption' => '...']
     * @return array
     */
    public function addImage($galleryId, $file, $options = [])
    {
        $gallery = $this->getBasicById($galleryId);
        
        if (!$gallery) {
            return ['success' => false, 'message' => 'Галерея не найдена'];
        }
        
        // Папка для файлов галереи
        $folder = 'gallery/' . $galleryId;
        
        // Загружаем через ImageUploader
        $result = ImageUploader::upload($file, $folder);
        
        if (!$result['success']) {
            return $result;
        }
        
        // Определяем sort_order (в конец)
        $maxOrder = $this->db->fetchOne(
            "SELECT MAX(sort_order) as max_order FROM gallery_images WHERE gallery_id = ?",
            [$galleryId]
        )['max_order'] ?? -1;
        
        // Сохраняем в БД
        $imageId = $this->db->insert('gallery_images', [
            'gallery_id' => $galleryId,
            'image_path' => $result['path'],
            'image_alt' => $options['alt'] ?? '',
            'image_caption' => $options['caption'] ?? '',
            'sort_order' => $maxOrder + 1,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        return [
            'success' => true,
            'image_id' => $imageId,
            'path' => $result['path'],
            'sizes' => $result['sizes'] ?? [],
            'message' => 'Изображение добавлено'
        ];
    }
    
    /**
     * Обновить метаданные изображения
     * 
     * @param int $imageId
     * @param array $data - ['image_alt' => '...', 'image_caption' => '...']
     * @return bool
     */
    public function updateImage($imageId, $data)
    {
        return $this->db->update(
            'gallery_images',
            $data,
            'id = ?',
            [$imageId]
        );
    }
    
    /**
     * Удалить изображение (файл + запись)
     * 
     * @param int $imageId
     * @return bool
     */
    public function deleteImage($imageId)
    {
        $image = $this->db->fetchOne(
            "SELECT * FROM gallery_images WHERE id = ?",
            [$imageId]
        );
        
        if (!$image) {
            return false;
        }
        
        // Удаляем файл
        $this->deleteImageFile($image['image_path']);
        
        // Удаляем запись
        return $this->db->delete('gallery_images', 'id = ?', [$imageId]) > 0;
    }
    
    /**
     * Сортировка изображений
     * 
     * @param array $order - [imageId1, imageId2, ...]
     * @return bool
     */
    public function sortImages($order)
    {
        if (empty($order) || !is_array($order)) {
            return false;
        }
        
        foreach ($order as $index => $imageId) {
            $this->db->update(
                'gallery_images',
                ['sort_order' => $index],
                'id = ?',
                [(int)$imageId]
            );
        }
        
        return true;
    }
    
    /**
     * Количество изображений в галерее
     * 
     * @param int $galleryId
     * @return int
     */
    public function countImages($galleryId)
    {
        return $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM gallery_images WHERE gallery_id = ?",
            [$galleryId]
        )['count'] ?? 0;
    }
    
    // ============================================
    // ВСПОМОГАТЕЛЬНЫЕ
    // ============================================
    
    /**
     * Удалить файл изображения (с миниатюрами)
     */
    private function deleteImageFile($path)
    {
        if (empty($path)) return;
        
        // Путь без начального слеша
        $path = ltrim($path, '/');
        
        // Защита
        if (strpos($path, '..') !== false || strpos($path, 'uploads/') !== 0) {
            return;
        }
        
        $fullPath = ROOT_DIR . '/' . $path;
        
        if (!file_exists($fullPath)) return;
        
        $info = pathinfo($fullPath);
        $baseName = $info['filename'];
        $dir = $info['dirname'];
        $ext = $info['extension'] ?? '';
        
        // Удаляем оригинал
        unlink($fullPath);
        
        // Удаляем миниатюры
        $pattern = $dir . '/' . $baseName . '_*.' . $ext;
        foreach (glob($pattern) as $thumb) {
            unlink($thumb);
        }
    }
    
    /**
     * Рекурсивное удаление папки
     */
    private function removeDirectory($dir)
    {
        if (!is_dir($dir)) return;
        
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $path = $dir . '/' . $item;
            
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        
        rmdir($dir);
    }
    
    /**
     * Получить список галерей для select
     * 
     * @return array - ['id' => 'title']
     */
    public function getOptions()
    {
        $galleries = $this->getAll(true);
        
        $options = ['' => '— Не выбрана —'];
        foreach ($galleries as $gallery) {
            $options[$gallery['id']] = $gallery['title'] . ' (' . $gallery['images_count'] . ' фото)';
        }
        
        return $options;
    }
}