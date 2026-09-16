<?php
/**
 * Класс BlockRenderer - рендер блоков в контенте
 * 
 * Находит <div data-block="...">...</div> в HTML и заменяет на HTML блока
 */

class BlockRenderer
{
    private $db;
    private $blockManager;
    private static $loadedCss = [];
    private static $loadedJs = [];
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->blockManager = new BlockManager();
    }
    
    /**
     * Обработка HTML: находит все блоки и заменяет их на HTML
     * 
     * @param string $html - HTML модуля
     * @return string - HTML с отрендеренными блоками
     */
    public function process($html)
    {
        if (empty($html)) {
            return $html;
        }
        
        // Ищем все <div data-block="...">
        $pattern = '/<div\s+([^>]*?)data-block=["\']([^"\']+)["\']([^>]*?)>\s*<\/div>/is';
        
        $html = preg_replace_callback($pattern, function($matches) {
            $name = $matches[2];
            $allAttrs = $matches[1] . ' ' . $matches[3];
            
            // Извлекаем параметры из data-*
            $params = $this->extractParams($allAttrs);
            
            // Рендерим блок
            return $this->render($name, $params);
        }, $html);
        
        return $html;
    }
    
    /**
     * Извлечение параметров из атрибутов
     */
    private function extractParams($attrs)
    {
        $params = [];
        
        // Ищем все data-* атрибуты (кроме data-block)
        if (preg_match_all('/data-([a-z0-9-]+)=["\']([^"\']*)["\']/i', $attrs, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $key = $match[1];
                $value = $match[2];
                
                // Пропускаем сам data-block
                if ($key === 'block') continue;
                
                // Преобразуем в camelCase или оставляем как есть
                $params[$key] = $value;
            }
        }
        
        return $params;
    }
    
    /**
     * Рендер блока по имени
     */
    public function render($name, $params = [])
    {
        // Получаем блок из реестра
        $block = $this->blockManager->getBlock($name);
        
        if (!$block) {
            return '<!-- Блок "' . htmlspecialchars($name) . '" не найден в реестре -->';
        }
        
        if (!$block['is_active']) {
            return '<!-- Блок "' . htmlspecialchars($name) . '" отключён -->';
        }
        
        // Загружаем файлы блока
        $blockData = $this->blockManager->loadBlock($name);
        
        if (!$blockData) {
            return '<!-- Блок "' . htmlspecialchars($name) . '" не найден на диске -->';
        }
        
        // Подключаем CSS (один раз)
        $this->loadCss($name, $blockData);
        
        // Подключаем JS (один раз)
        $this->loadJs($name, $blockData);
        
        // Готовим переменные для блока
        $blockName = $name;
        $uniqid = 'block-' . $name . '-' . substr(md5(uniqid('', true)), 0, 8);
        
        // Рендерим block.php в буфер
        ob_start();
        
        try {
            // Изолируем переменные через анонимную функцию
            (function($blockFile, $params, $blockName, $uniqid) {
                // Локальные переменные внутри анонимной функции
                extract(['params' => $params, 'blockName' => $blockName, 'uniqid' => $uniqid]);
                include $blockFile;
            })($blockData['block_file'], $params, $blockName, $uniqid);
            
            $html = ob_get_clean();
            
            // Оборачиваем в контейнер с уникальным ID
            return '<div class="block-wrapper" data-block-instance="' . $uniqid . '">' . $html . '</div>';
            
        } catch (Exception $e) {
            ob_end_clean();
            return '<!-- Ошибка рендера блока "' . htmlspecialchars($name) . '": ' . htmlspecialchars($e->getMessage()) . ' -->';
        }
    }
    
    /**
     * Подключение CSS блока
     */
    private function loadCss($name, $blockData)
    {
        if (!isset($blockData['has_css']) || !$blockData['has_css']) {
            return;
        }
        
        // Проверяем, не подключён ли уже
        if (isset(self::$loadedCss[$name])) {
            return;
        }
        
        $cssUrl = '/blocks/' . $name . '/style.css';
        
        echo '<link rel="stylesheet" href="' . $cssUrl . '">';
        
        self::$loadedCss[$name] = true;
    }
    
    /**
     * Подключение JS блока
     */
    private function loadJs($name, $blockData)
    {
        if (!isset($blockData['has_js']) || !$blockData['has_js']) {
            return;
        }
        
        // Проверяем, не подключён ли уже
        if (isset(self::$loadedJs[$name])) {
            return;
        }
        
        $jsUrl = '/blocks/' . $name . '/script.js';
        
        echo '<script src="' . $jsUrl . '"></script>';
        
        self::$loadedJs[$name] = true;
    }
    
    /**
     * Сброс кэша подключённых CSS/JS
     */
    public static function reset()
    {
        self::$loadedCss = [];
        self::$loadedJs = [];
    }
}