<?php
/**
 * Класс Database - обертка над PDO с паттерном Singleton
 * Обеспечивает единое подключение к БД
 */

class Database
{
    private static $instance = null;
    private $pdo;
    
    private function __construct()
    {
        $this->pdo = require_once __DIR__ . '/../base/base-connect.php';
    }
    
    /**
     * Получение экземпляра (Singleton)
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Выполнение запроса с prepared statements
     */
    public function query($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    /**
     * Получение одной строки
     */
    public function fetchOne($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * Получение всех строк
     */
    public function fetchAll($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Вставка записи (возвращает ID)
     */
    public function insert($table, $data)
    {
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', $fields),
            implode(', ', $placeholders)
        );
        
        $this->query($sql, array_values($data));
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Обновление записей
     */
    public function update($table, $data, $where, $whereParams = [])
    {
        $set = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            $set[] = "{$key} = ?";
            $params[] = $value;
        }
        
        // Добавляем параметры WHERE
        foreach ($whereParams as $param) {
            $params[] = $param;
        }
        
        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s',
            $table,
            implode(', ', $set),
            $where
        );
        
        return $this->query($sql, $params)->rowCount();
    }
    
    /**
     * Удаление записей
     */
    public function delete($table, $where, $params = [])
    {
        $sql = sprintf('DELETE FROM %s WHERE %s', $table, $where);
        return $this->query($sql, $params)->rowCount();
    }
    
    /**
     * Получение последнего вставленного ID
     */
    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Экранирование строки (для LIKE и т.д.)
     */
    public function escape($string)
    {
        return $this->pdo->quote($string);
    }
}