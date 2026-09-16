<?php
/**
 * Класс Setting - управление настройками сайта
 * 
 * Использование:
 *   Setting::get('site_name')              — получить значение
 *   Setting::set('site_name', 'Новое')     — сохранить
 *   Setting::all()                         — все настройки
 *   Setting::has('site_name')              — проверить наличие
 *   Setting::load(true)                    — принудительно перезагрузить кэш
 */

class Setting
{
    /**
     * Кэш настроек (статический, чтобы не читать БД многократно)
     */
    private static $cache = null;
    
    /**
     * Флаг, что кэш уже загружен
     */
    private static $loaded = false;
    
    /**
     * Получение экземпляра БД
     */
    private static function db()
    {
        return Database::getInstance();
    }
    
    /**
     * Загрузка всех настроек в кэш
     * 
     * @param bool $force - принудительно перезагрузить из БД
     */
    public static function load($force = false)
    {
        if (self::$loaded && !$force) {
            return;
        }
        
        self::$cache = [];
        
        try {
            $settings = self::db()->fetchAll(
                "SELECT setting_key, setting_value FROM system_settings"
            );
            
            foreach ($settings as $row) {
                self::$cache[$row['setting_key']] = $row['setting_value'];
            }
            
            self::$loaded = true;
            
        } catch (Exception $e) {
            self::$cache = [];
            self::$loaded = true;
        }
    }
    
    /**
     * Получение значения настройки
     * 
     * @param string $key - ключ настройки
     * @param mixed $default - значение по умолчанию
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        self::load();
        
        if (!isset(self::$cache[$key])) {
            return $default;
        }
        
        return self::$cache[$key];
    }
    
    /**
     * Проверка, существует ли настройка
     * 
     * @param string $key
     * @return bool
     */
    public static function has($key)
    {
        self::load();
        return isset(self::$cache[$key]);
    }
    
    /**
     * Сохранение настройки
     * 
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public static function set($key, $value)
    {
        self::load();
        
        try {
            // Проверяем, существует ли настройка
            $exists = self::db()->fetchOne(
                "SELECT id FROM system_settings WHERE setting_key = ?",
                [$key]
            );
            
            if ($exists) {
                // Обновляем
                self::db()->update(
                    'system_settings',
                    [
                        'setting_value' => $value,
                        'updated_at' => date('Y-m-d H:i:s')
                    ],
                    'setting_key = ?',
                    [$key]
                );
            } else {
                // Создаём
                self::db()->insert('system_settings', [
                    'setting_key' => $key,
                    'setting_value' => $value,
                    'is_cached' => 1,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }
            
            // Обновляем кэш
            self::$cache[$key] = $value;
            
            return true;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Сохранение нескольких настроек сразу
     * 
     * @param array $data - ['key1' => 'value1', 'key2' => 'value2']
     * @return int - количество сохранённых
     */
    public static function setMany($data)
    {
        $count = 0;
        
        foreach ($data as $key => $value) {
            if (self::set($key, $value)) {
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Получение всех настроек
     * 
     * @return array
     */
    public static function all()
    {
        self::load();
        return self::$cache;
    }
    
    /**
     * Удаление настройки
     * 
     * @param string $key
     * @return bool
     */
    public static function delete($key)
    {
        self::load();
        
        try {
            self::db()->delete(
                'system_settings',
                'setting_key = ?',
                [$key]
            );
            
            unset(self::$cache[$key]);
            
            return true;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Сброс кэша (для тестов)
     */
    public static function reset()
    {
        self::$cache = null;
        self::$loaded = false;
    }
    
    /**
     * Получение настроек по префиксу ключа
     * 
     * @param string $prefix - например 'contact_'
     * @return array
     */
    public static function getByPrefix($prefix)
    {
        self::load();
        
        $result = [];
        foreach (self::$cache as $key => $value) {
            if (strpos($key, $prefix) === 0) {
                $result[$key] = $value;
            }
        }
        
        return $result;
    }
    
    /**
     * Получение булевой настройки
     * 
     * @param string $key
     * @param bool $default
     * @return bool
     */
    public static function getBool($key, $default = false)
    {
        $value = self::get($key);
        
        if ($value === null) {
            return $default;
        }
        
        return (bool)(int)$value;
    }
}