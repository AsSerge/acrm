<?php
/**
 * Класс Router - маршрутизация запросов
 * Поддерживает пути типа /module/action/param
 */

class Router
{
    private $routes = [];
    private $basePath = '';
    private $moduleName = '';
    private $action = 'index';
    private $params = [];
    
    public function __construct($basePath = '')
    {
        $this->basePath = trim($basePath, '/');
        $this->parseRequest();
    }
    
    /**
     * Разбор текущего запроса
     */
    private function parseRequest()
    {
        $uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        
        // Удаляем базовый путь
        if (!empty($this->basePath) && strpos($uri, $this->basePath) === 0) {
            $uri = substr($uri, strlen($this->basePath));
            $uri = trim($uri, '/');
        }
        
        if (empty($uri)) {
            $this->moduleName = 'home';
            $this->action = 'index';
            return;
        }
        
        $segments = explode('/', $uri);
        
        // Первый сегмент - имя модуля
        $this->moduleName = $segments[0];
        
        // Второй сегмент - действие (опционально)
        if (isset($segments[1]) && !empty($segments[1])) {
            $this->action = $segments[1];
        }
        
        // Остальные сегменты - параметры
        if (isset($segments[2])) {
            $this->params = array_slice($segments, 2);
        }
    }
    
    /**
     * Получение имени модуля
     */
    public function getModule()
    {
        return $this->moduleName;
    }
    
    /**
     * Получение действия
     */
    public function getAction()
    {
        return $this->action;
    }
    
    /**
     * Получение параметров
     */
    public function getParams()
    {
        return $this->params;
    }
    
    /**
     * Получение конкретного параметра по индексу
     */
    public function getParam($index, $default = null)
    {
        return isset($this->params[$index]) ? $this->params[$index] : $default;
    }
    
    /**
     * Проверка, является ли запрос AJAX
     */
    public function isAjax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Проверка метода запроса
     */
    public function isMethod($method)
    {
        return strtoupper($_SERVER['REQUEST_METHOD']) === strtoupper($method);
    }
    
    /**
     * Формирование URL для модуля
     */
    public function buildUrl($module, $action = 'index', $params = [])
    {
        $url = '/' . ltrim($module, '/');
        if ($action !== 'index') {
            $url .= '/' . ltrim($action, '/');
        }
        if (!empty($params)) {
            $url .= '/' . implode('/', $params);
        }
        return $url;
    }
    
    /**
     * Редирект
     */
    public function redirect($url, $permanent = false)
    {
        if ($permanent) {
            header('HTTP/1.1 301 Moved Permanently');
        }
        header('Location: ' . $url);
        exit;
    }
}