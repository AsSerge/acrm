<?php
/**
 * Класс ImageUploader - загрузка и обработка изображений
 * 
 * Использование:
 *   $result = ImageUploader::upload($_FILES['image'], 'news');
 *   $result = ImageUploader::upload($_FILES['logo'], 'site', ['sizes' => []]);
 *   
 * Результат:
 *   [
 *       'success' => true,
 *       'path' => '/uploads/news/2026-09/2026-09-15-abc123.jpg',
 *       'filename' => '2026-09-15-abc123.jpg',
 *       'sizes' => [
 *           'thumb' => '/uploads/news/2026-09/2026-09-15-abc123_thumb.jpg',
 *           'medium' => '/uploads/news/2026-09/2026-09-15-abc123_medium.jpg',
 *       ],
 *       'width' => 1600,
 *       'height' => 1200,
 *       'size' => 123456,
 *   ]
 */

class ImageUploader
{
    /**
     * Разрешённые расширения
     */
    private static $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
    
    /**
     * Разрешённые MIME-типы
     */
    private static $allowedMimes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
    ];
    
    /**
     * Максимальный размер файла (15 МБ)
     */
    private static $maxFileSize = 15728640; // 15 * 1024 * 1024
    
    /**
     * Качество JPEG
     */
    private static $jpegQuality = 85;
    
    /**
     * Размеры по умолчанию
     */
    private static $defaultSizes = [
        'thumb' => ['width' => 200, 'height' => 150, 'crop' => true],
        'medium' => ['width' => 800, 'height' => 600, 'crop' => true],
    ];
    
    /**
     * Разрешённые папки
     */
    private static $allowedFolders = [
        'site', 'news', 'articles', 'gallery', 'users', 'modules', 'blocks'
    ];
    
    /**
     * Загрузка изображения
     * 
     * @param array $file - $_FILES['...']
     * @param string $folder - папка назначения (site, news, articles, ...)
     * @param array $options - опции:
     *   - sizes: массив размеров (по умолчанию thumb + medium)
     *   - crop: обрезать или вписать (по умолчанию true)
     *   - quality: качество JPEG (по умолчанию 85)
     *   - filename: своё имя (без расширения)
     * @return array
     */
    public static function upload($file, $folder, $options = [])
    {
        // ============================================
        // 1. ПРОВЕРКА ЗАГРУЗКИ
        // ============================================
        
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return self::error('Файл не загружен');
        }
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return self::error('Ошибка загрузки: код ' . $file['error']);
        }
        
        // ============================================
        // 2. ПРОВЕРКА РАЗМЕРА
        // ============================================
        
        if ($file['size'] > self::$maxFileSize) {
            $maxMb = round(self::$maxFileSize / 1024 / 1024, 1);
            return self::error("Файл слишком большой (максимум {$maxMb} МБ)");
        }
        
        // ============================================
        // 3. ПРОВЕРКА РАСШИРЕНИЯ
        // ============================================
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($extension, self::$allowedExtensions)) {
            return self::error('Недопустимое расширение файла');
        }
        
        // ============================================
        // 4. ПРОВЕРКА MIME-ТИПА
        // ============================================
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime, self::$allowedMimes)) {
            return self::error('Недопустимый тип файла: ' . $mime);
        }
        
        // ============================================
        // 5. ПРОВЕРКА ПАПКИ
        // ============================================
        
        $folder = trim($folder, '/');
        $folderParts = explode('/', $folder);
        $mainFolder = $folderParts[0];
        
        if (!in_array($mainFolder, self::$allowedFolders)) {
            return self::error('Недопустимая папка назначения: ' . $mainFolder);
        }
        
        // ============================================
        // 6. СОЗДАНИЕ ПАПКИ (с разбивкой по месяцам)
        // ============================================
        
        $month = date('Y-m');
        $uploadDir = ROOT_DIR . '/uploads/' . $folder . '/' . $month;
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // ============================================
        // 7. ГЕНЕРАЦИЯ ИМЕНИ
        // ============================================
        
        if (!empty($options['filename'])) {
            // Своё имя
            $baseName = self::sanitizeName($options['filename']);
        } else {
            // Дата + хеш
            $baseName = date('Y-m-d') . '-' . self::generateHash();
        }
        
        // Если SVG — сохраняем как есть
        if ($extension === 'svg') {
            return self::saveSvg($file, $uploadDir, $baseName, $folder, $month);
        }
        
        // ============================================
        // 8. СОЗДАНИЕ ИЗОБРАЖЕНИЯ
        // ============================================
        
        $image = self::createImage($file['tmp_name'], $mime);
        if (!$image) {
            return self::error('Не удалось обработать изображение');
        }
        
        $originalWidth = imagesx($image);
        $originalHeight = imagesy($image);
        
        // ============================================
        // 9. СОХРАНЕНИЕ ОРИГИНАЛА (или основного)
        // ============================================
        
        // Сохраняем оригинал с максимальным размером (medium)
        $sizes = $options['sizes'] ?? self::$defaultSizes;
        $quality = $options['quality'] ?? self::$jpegQuality;
        
        // Определяем максимальный размер для сохранения оригинала
        $maxWidth = 1600; // Максимум для "оригинала"
        $maxHeight = 1200;
        
        $mainImage = $image;
        if ($originalWidth > $maxWidth || $originalHeight > $maxHeight) {
            $mainImage = self::resizeImage($image, $maxWidth, $maxHeight, false);
        }
        
        $mainFileName = $baseName . '.' . $extension;
        $mainFilePath = $uploadDir . '/' . $mainFileName;
        
        if (!self::saveImage($mainImage, $mainFilePath, $extension, $quality)) {
            imagedestroy($image);
            return self::error('Не удалось сохранить файл');
        }
        
        $savedSizes = [];
        
        // ============================================
        // 10. СОЗДАНИЕ МИНИАТЮР
        // ============================================
        
        foreach ($sizes as $sizeName => $sizeOptions) {
            $width = $sizeOptions['width'] ?? 200;
            $height = $sizeOptions['height'] ?? 150;
            $crop = $sizeOptions['crop'] ?? true;
            
            $resized = self::resizeImage($image, $width, $height, $crop);
            
            $sizeFileName = $baseName . '_' . $sizeName . '.' . $extension;
            $sizeFilePath = $uploadDir . '/' . $sizeFileName;
            
            if (self::saveImage($resized, $sizeFilePath, $extension, $quality)) {
                $savedSizes[$sizeName] = '/uploads/' . $folder . '/' . $month . '/' . $sizeFileName;
            }
            
            imagedestroy($resized);
        }
        
        imagedestroy($image);
        if ($mainImage !== $image) {
            imagedestroy($mainImage);
        }
        
        // ============================================
        // 11. ВОЗВРАТ РЕЗУЛЬТАТА
        // ============================================
        
        return [
            'success' => true,
            'path' => '/uploads/' . $folder . '/' . $month . '/' . $mainFileName,
            'filename' => $mainFileName,
            'folder' => $folder,
            'month' => $month,
            'sizes' => $savedSizes,
            'width' => $originalWidth,
            'height' => $originalHeight,
            'size' => filesize($mainFilePath),
        ];
    }
    
    /**
     * Удаление изображения (с миниатюрами)
     */
    public static function delete($path)
    {
        // Убираем начальный слеш
        $path = ltrim($path, '/');
        
        // Защита от выхода за пределы /uploads/
        if (strpos($path, '..') !== false || strpos($path, 'uploads/') !== 0) {
            return false;
        }
        
        $fullPath = ROOT_DIR . '/' . $path;
        
        if (!file_exists($fullPath)) {
            return false;
        }
        
        // Получаем имя без расширения
        $info = pathinfo($fullPath);
        $baseName = $info['filename'];
        $dir = $info['dirname'];
        $extension = $info['extension'] ?? '';
        
        // Удаляем оригинал
        unlink($fullPath);
        
        // Удаляем миниатюры
        $pattern = $dir . '/' . $baseName . '_*.' . $extension;
        foreach (glob($pattern) as $thumb) {
            unlink($thumb);
        }
        
        return true;
    }

	    /**
     * Рекурсивное удаление папки
     * 
     * @param string $path - путь к папке (относительно /uploads/)
     * @return array
     */
    public static function deleteFolder($path)
    {
        // Убираем начальный слеш
        $path = ltrim($path, '/');
        
        // Защита от выхода за пределы /uploads/
        if (strpos($path, '..') !== false || strpos($path, 'uploads/') !== 0) {
            return ['success' => false, 'message' => 'Недопустимый путь'];
        }
        
        $fullPath = ROOT_DIR . '/' . rtrim($path, '/');
        
        if (!is_dir($fullPath)) {
            return ['success' => false, 'message' => 'Папка не найдена'];
        }
        
        // Считаем содержимое
        $count = self::countFolderContents($fullPath);
        
        // Рекурсивное удаление
        $deleted = self::removeDirectory($fullPath);
        
        if (!$deleted) {
            return ['success' => false, 'message' => 'Не удалось удалить папку'];
        }
        
        return [
            'success' => true,
            'message' => 'Папка удалена',
            'deleted' => $count,
        ];
    }
    
    /**
     * Подсчёт содержимого папки
     */
    public static function countFolderContents($path)
    {
        if (!is_dir($path)) return 0;
        
        $count = 0;
        $items = scandir($path);
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $itemPath = $path . '/' . $item;
            
            if (is_dir($itemPath)) {
                $count += self::countFolderContents($itemPath);
            } else {
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Рекурсивное удаление директории
     */
    private static function removeDirectory($dir)
    {
        if (!is_dir($dir)) return false;
        
        $items = scandir($dir);
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $path = $dir . '/' . $item;
            
            if (is_dir($path)) {
                self::removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        
        return rmdir($dir);
    }
    
    /**
     * Переименование папки
     * 
     * @param string $oldPath - старый путь
     * @param string $newName - новое имя (без пути)
     * @return array
     */
    public static function renameFolder($oldPath, $newName)
    {
        // Убираем начальный слеш
        $oldPath = ltrim($oldPath, '/');
        
        // Защита
        if (strpos($oldPath, '..') !== false || strpos($oldPath, 'uploads/') !== 0) {
            return ['success' => false, 'message' => 'Недопустимый путь'];
        }
        
        if (strpos($newName, '..') !== false || strpos($newName, '/') !== false || strpos($newName, '\\') !== false) {
            return ['success' => false, 'message' => 'Недопустимое имя папки'];
        }
        
        $fullPath = ROOT_DIR . '/' . rtrim($oldPath, '/');
        
        if (!is_dir($fullPath)) {
            return ['success' => false, 'message' => 'Папка не найдена'];
        }
        
        // Очищаем новое имя
        $newName = self::sanitizeName($newName);
        
        if (empty($newName)) {
            return ['success' => false, 'message' => 'Недопустимое имя'];
        }
        
        $parentDir = dirname($fullPath);
        $newFullPath = $parentDir . '/' . $newName;
        
        if (file_exists($newFullPath)) {
            return ['success' => false, 'message' => 'Папка с таким именем уже существует'];
        }
        
        if (!rename($fullPath, $newFullPath)) {
            return ['success' => false, 'message' => 'Не удалось переименовать папку'];
        }
        
        return [
            'success' => true,
            'message' => 'Папка переименована',
            'new_name' => $newName,
        ];
    }
    
    /**
     * Создание изображения из файла
     */
    private static function createImage($path, $mime)
    {
        switch ($mime) {
            case 'image/jpeg':
                return imagecreatefromjpeg($path);
            case 'image/png':
                return imagecreatefrompng($path);
            case 'image/gif':
                return imagecreatefromgif($path);
            case 'image/webp':
                return imagecreatefromwebp($path);
            default:
                return false;
        }
    }
    
    /**
     * Изменение размера изображения
     * 
     * @param resource $image - исходное изображение
     * @param int $width - целевая ширина
     * @param int $height - целевая высота
     * @param bool $crop - обрезать (true) или вписать (false)
     * @return resource
     */
    private static function resizeImage($image, $width, $height, $crop = true)
    {
        $origWidth = imagesx($image);
        $origHeight = imagesy($image);
        
        if ($crop) {
            // Обрезка по центру
            $ratio = max($width / $origWidth, $height / $origHeight);
            $newWidth = (int)($origWidth * $ratio);
            $newHeight = (int)($origHeight * $ratio);
            
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            
            // Сохраняем прозрачность для PNG/GIF
            self::preserveTransparency($resized, $image);
            
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
            
            // Обрезаем
            $finalImage = imagecreatetruecolor($width, $height);
            self::preserveTransparency($finalImage, $resized);
            
            $srcX = (int)(($newWidth - $width) / 2);
            $srcY = (int)(($newHeight - $height) / 2);
            
            imagecopy($finalImage, $resized, 0, 0, $srcX, $srcY, $width, $height);
            
            imagedestroy($resized);
            
            return $finalImage;
        } else {
            // Вписать в рамку (fit)
            $ratio = min($width / $origWidth, $height / $origHeight);
            $newWidth = (int)($origWidth * $ratio);
            $newHeight = (int)($origHeight * $ratio);
            
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            self::preserveTransparency($resized, $image);
            
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
            
            return $resized;
        }
    }
    
    /**
     * Сохранение прозрачности для PNG/GIF
     */
    private static function preserveTransparency($destination, $source)
    {
        $transparent = imagecolorallocatealpha($destination, 0, 0, 0, 127);
        imagefill($destination, 0, 0, $transparent);
        imagealphablending($destination, false);
        imagesavealpha($destination, true);
    }
    
    /**
     * Сохранение изображения в файл
     */
    private static function saveImage($image, $path, $extension, $quality)
    {
        switch (strtolower($extension)) {
            case 'jpg':
            case 'jpeg':
                return imagejpeg($image, $path, $quality);
            case 'png':
                return imagepng($image, $path, 9);
            case 'gif':
                return imagegif($image, $path);
            case 'webp':
                return imagewebp($image, $path, $quality);
            default:
                return false;
        }
    }
    
    /**
     * Сохранение SVG
     */
    private static function saveSvg($file, $uploadDir, $baseName, $folder, $month)
    {
        $content = file_get_contents($file['tmp_name']);
        
        // Санитизация SVG
        $content = self::sanitizeSvg($content);
        
        $fileName = $baseName . '.svg';
        $filePath = $uploadDir . '/' . $fileName;
        
        if (file_put_contents($filePath, $content) === false) {
            return self::error('Не удалось сохранить SVG');
        }
        
        return [
            'success' => true,
            'path' => '/uploads/' . $folder . '/' . $month . '/' . $fileName,
            'filename' => $fileName,
            'folder' => $folder,
            'month' => $month,
            'sizes' => [],
            'width' => 0,
            'height' => 0,
            'size' => filesize($filePath),
        ];
    }
    
    /**
     * Санитизация SVG
     */
    private static function sanitizeSvg($content)
    {
        // Удаляем <script> и обработчики событий
        $content = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $content);
        $content = preg_replace('/\s+on\w+\s*=\s*["\'][^"\']*["\']/i', '', $content);
        $content = preg_replace('/javascript:/i', '', $content);
        
        return $content;
    }
    
    /**
     * Генерация хеша
     */
    private static function generateHash($length = 12)
    {
        return substr(bin2hex(random_bytes(8)), 0, $length);
    }
    
    /**
     * Очистка имени файла
     */
    private static function sanitizeName($name)
    {
        $name = strtolower(trim($name));
        $name = preg_replace('/[^a-z0-9-]/', '-', $name);
        $name = preg_replace('/-+/', '-', $name);
        $name = trim($name, '-');
        
        if (empty($name)) {
            $name = 'image';
        }
        
        return $name;
    }
    
    /**
     * Возврат ошибки
     */
    private static function error($message)
    {
        return [
            'success' => false,
            'message' => $message,
        ];
    }
    
    /**
     * Получение URL изображения
     */
    public static function getUrl($path)
    {
        if (empty($path)) return '';
        
        if (strpos($path, 'http') === 0) {
            return $path;
        }
        
        return $path;
    }
}