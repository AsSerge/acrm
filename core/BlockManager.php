<?php
/**
 * Класс BlockManager - управление блоками
 * Сканирование, регистрация, создание, удаление
 */

class BlockManager
{
    private $db;
    private $blocks = [];
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->loadBlocks();
    }
    
    /**
     * Загрузка всех блоков из реестра
     */
    private function loadBlocks()
    {
        $blocks = $this->db->fetchAll(
            "SELECT * FROM blocks_registry ORDER BY block_name"
        );
        
        foreach ($blocks as $block) {
            $this->blocks[$block['block_name']] = $block;
        }
    }
    
    /**
     * Получение всех блоков
     */
    public function getAllBlocks()
    {
        return $this->blocks;
    }
    
    /**
     * Получение блока по имени
     */
    public function getBlock($name)
    {
        return $this->blocks[$name] ?? null;
    }
    
    /**
     * Получение блока по ID
     */
    public function getBlockById($id)
    {
        return $this->db->fetchOne(
            "SELECT * FROM blocks_registry WHERE id = ?",
            [$id]
        );
    }
    
    /**
     * Сканирование папки /blocks/
     * Регистрирует новые блоки, обновляет существующие,
     * удаляет мёртвые записи
     */
    public function scanBlocks()
    {
        $blocksDir = ROOT_DIR . '/blocks';
        
        if (!is_dir($blocksDir)) {
            return ['added' => 0, 'updated' => 0, 'deleted' => 0, 'total' => 0];
        }
        
        $items = scandir($blocksDir);
        $added = 0;
        $updated = 0;
        $total = 0;
        $foundBlocks = [];
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $fullPath = $blocksDir . '/' . $item;
            if (!is_dir($fullPath)) continue;
            
            // Проверяем наличие block.php
            if (!file_exists($fullPath . '/block.php')) continue;
            
            $total++;
            $foundBlocks[] = $item;
            
            // Проверяем, зарегистрирован ли блок
            $existing = $this->db->fetchOne(
                "SELECT id FROM blocks_registry WHERE block_name = ?",
                [$item]
            );
            
            if (!$existing) {
                // Регистрируем новый блок
                $this->db->insert('blocks_registry', [
                    'block_name' => $item,
                    'title' => $this->humanizeName($item),
                    'description' => '',
                    'version' => '1.0.0',
                    'author' => 'admin',
                    'is_active' => 1,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                $added++;
            } else {
                // Обновляем дату
                $this->db->update(
                    'blocks_registry',
                    ['updated_at' => date('Y-m-d H:i:s')],
                    'block_name = ?',
                    [$item]
                );
                $updated++;
            }
        }
        
        // Автоочистка мёртвых записей
        $deleted = $this->cleanOrphanedBlocks($foundBlocks);
        
        // Перезагружаем список
        $this->loadBlocks();
        
        return [
            'added' => $added,
            'updated' => $updated,
            'deleted' => $deleted,
            'total' => $total
        ];
    }
    
    /**
     * Удаление записей блоков, которых нет на диске
     */
    private function cleanOrphanedBlocks($foundBlocks)
    {
        $dbBlocks = $this->db->fetchAll(
            "SELECT id, block_name FROM blocks_registry"
        );
        
        $deleted = 0;
        
        foreach ($dbBlocks as $dbBlock) {
            if (!in_array($dbBlock['block_name'], $foundBlocks)) {
                $this->db->delete('blocks_registry', 'id = ?', [$dbBlock['id']]);
                $deleted++;
            }
        }
        
        return $deleted;
    }
    
    /**
     * Создание блока
     * Создаёт папку, файлы и запись в БД
     */
    public function createBlock($name, $title, $description = '', $author = 'admin')
    {
        // Валидация имени
        if (!$this->validateName($name)) {
            return ['success' => false, 'message' => 'Имя блока должно содержать только латиницу, цифры и дефис'];
        }
        
        // Проверяем, не существует ли уже
        if ($this->getBlock($name)) {
            return ['success' => false, 'message' => 'Блок с таким именем уже существует'];
        }
        
        $blocksDir = ROOT_DIR . '/blocks';
        $blockPath = $blocksDir . '/' . $name;
        
        // Создаём папку /blocks/
        if (!is_dir($blocksDir)) {
            mkdir($blocksDir, 0755, true);
        }
        
        // Создаём папку блока
        if (!is_dir($blockPath)) {
            mkdir($blockPath, 0755, true);
        }
        
        // Создаём block.php
        $blockPhpContent = $this->getBlockPhpTemplate($name, $title);
        file_put_contents($blockPath . '/block.php', $blockPhpContent);
        
        // Создаём style.css
        $styleContent = "/* Стили блока \"{$title}\" */\n\n.{$name}-block {\n    \n}\n";
        file_put_contents($blockPath . '/style.css', $styleContent);
        
        // Создаём script.js
        $scriptContent = "/**\n * Скрипты блока \"{$title}\"\n */\n\n(function() {\n    'use strict';\n    \n    // Логика блока\n    \n})();\n";
        file_put_contents($blockPath . '/script.js', $scriptContent);
        
        // Создаём запись в БД
        $this->db->insert('blocks_registry', [
            'block_name' => $name,
            'title' => $title,
            'description' => $description,
            'version' => '1.0.0',
            'author' => $author,
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        // Перезагружаем список
        $this->loadBlocks();
        
        return ['success' => true, 'message' => "Блок «{$title}» успешно создан"];
    }
    
    /**
     * Обновление метаданных блока
     */
    public function updateBlock($name, $data)
    {
        return $this->db->update(
            'blocks_registry',
            $data,
            'block_name = ?',
            [$name]
        );
    }
    
    /**
     * Удаление блока (папка + запись в БД)
     */
    public function deleteBlock($name)
    {
        $block = $this->getBlock($name);
        if (!$block) {
            return ['success' => false, 'message' => 'Блок не найден'];
        }
        
        $blockPath = ROOT_DIR . '/blocks/' . $name;
        
        // Удаляем папку рекурсивно
        if (is_dir($blockPath)) {
            $this->removeDirectory($blockPath);
        }
        
        // Удаляем запись
        $this->db->delete('blocks_registry', 'block_name = ?', [$name]);
        
        // Перезагружаем
        $this->loadBlocks();
        
        return ['success' => true, 'message' => 'Блок удалён'];
    }
    
    /**
     * Загрузка блока (пути к файлам)
     */
    public function loadBlock($name)
    {
        $block = $this->getBlock($name);
        if (!$block) {
            return false;
        }
        
        $blockPath = ROOT_DIR . '/blocks/' . $name;
        
        if (!is_dir($blockPath)) {
            return false;
        }
        
        $blockFile = $blockPath . '/block.php';
        if (!file_exists($blockFile)) {
            return false;
        }
        
        $cssFile = $blockPath . '/style.css';
        $jsFile = $blockPath . '/script.js';
        
        return [
            'block' => $block,
            'path' => $blockPath,
            'block_file' => $blockFile,
            'css' => file_exists($cssFile) ? $cssFile : null,
            'js' => file_exists($jsFile) ? $jsFile : null,
            'has_css' => file_exists($cssFile),
            'has_js' => file_exists($jsFile)
        ];
    }
    
    /**
     * Проверка, существует ли папка блока на диске
     */
    public function blockExistsOnDisk($name)
    {
        $path = ROOT_DIR . '/blocks/' . $name . '/block.php';
        return file_exists($path);
    }
    
    /**
     * Проверка имени блока
     */
    public function validateName($name)
    {
        return preg_match('/^[a-z][a-z0-9-]*$/', $name) === 1;
    }
    
    /**
     * Шаблон block.php
     */
    private function getBlockPhpTemplate($name, $title)
    {
        return <<<PHP
<?php
/**
 * Блок "{$title}"
 * 
 * Доступные переменные:
 * \$params — массив параметров из data-* атрибутов
 * \$blockName — имя блока ({$name})
 * \$uniqid — уникальный ID экземпляра блока
 */

// Получаем параметры
\$count = \$params['count'] ?? 3;

// Логика блока
// ...

// Вывод HTML
?>
<div class="{$name}-block">
    <p>Блок "{$title}" работает!</p>
</div>
PHP;
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
     * Человекочитаемое имя из имени папки
     */
    private function humanizeName($name)
    {
        $name = str_replace(['-', '_'], ' ', $name);
        $name = ucwords($name);
        return $name;
    }
}