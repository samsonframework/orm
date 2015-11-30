<?php
/**
 * Created by PhpStorm.
 * User: egorov
 * Date: 09.05.2015
 * Time: 13:05
 */
namespace samsonframework\orm;

/**
 * Class Database
 * @package samsonframework\orm
 */
class Database
{
    /** @var \PDO Database driver */
    protected $driver;

    /** @var string Database name */
    protected $database;

    /** @var int Amount of milliseconds spent on queries */
    protected $elapsed;

    /** @var int Amount queries executed */
    protected $count;

    /**
     * Connect to a database using driver with parameters
     * @param string $database Database name
     * @param string $username Database username
     * @param string $password Database password
     * @param string $host Database host(localhost by default)
     * @param int $port Database port(3306 by default)
     * @param string $driver Database driver for interaction(MySQL by default)
     * @param string $charset Database character set
     * @return bool True if connection to database was successful
     */
    public function connect(
        $database,
        $username,
        $password,
        $host = 'localhost',
        $port = 3306,
        $driver = 'mysql',
        $charset = 'utf8'
    ) {
        // If we have not connected yet
        if (!isset($this->driver)) {
            $this->database = $database;

            // Check if configured database exists
            $this->driver = new PDO($host, $database, $username, $password, $charset, $port, $driver);

            // Set correct encodings
            $this->query("set character_set_client='utf8'");
            $this->query("set character_set_results='utf8'");
            $this->query("set collation_connection='utf8_general_ci'");
        }
    }

    /**
     * Get database name
     * @return string
     */
    public function database()
    {
        return $this->database;
    }

    /**
     * Create new database record
     * @param string $entity Entity class name
     * @return null|RecordInterface Entity instance
     * @throws EntityNotFound
     */
    public function entity($entity)
    {
        if (class_exists($entity)) {
            return new $entity($this);
        } else {
            throw new EntityNotFound('['.$entity.'] not found');
        }

        return null;
    }

    /**
     * Get entity query manager
     * @param string $entity Entity identifier
     * @return Query Query manager instance
     */
    public function manager($entity)
    {
        return new Query($entity, $this);
    }

    /**
     * High-level database query executor
     * @param string $sql SQL statement
     * @return mixed Database query result
     */
    public function &query($sql)
    {
        $result = array();

        if (isset($this->driver)) {
            // Store timestamp
            $tsLast = microtime(true);

            try {
                // Perform database query
                $result = $this->driver->prepare($sql)->execute();
            } catch (\PDOException $e) {
                echo("\n" . $sql . '-' . $e->getMessage());
            }

            // Store queries count
            $this->count++;

            // Count elapsed time
            $this->elapsed += microtime(true) - $tsLast;
        }

        return $result;
    }

    /**
     * Retrieve array of records from a database, if $className is passed method
     * will try to create an object of that type. If request has failed than
     * method will return empty array of stdClass all arrays regarding to $className is
     * passed or not.
     *
     * @param string $sql Query text
     * @param string $className Class name if we want to create object
     * @return array Collection of arrays or objects
     */
    public function &fetch($sql, $className = null)
    {
        // Return value
        $result = array();

        if (isset($this->driver)) {
            // Store timestamp
            $tsLast = microtime(true);

            try {
                // Perform database query
                if (!isset($className)) { // Return array
                    $result = $this->driver->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                } else { // Create object of passed class name
                    $result = $this->driver->query($sql)->fetchAll(\PDO::FETCH_CLASS, $className, array(&$this));
                }
            } catch (\PDOException $e) {
                echo("\n" . $sql . '-' . $e->getMessage());
            }

            // Store queries count
            $this->count++;

            // Count elapsed time
            $this->elapsed += microtime(true) - $tsLast;
        }

        return $result;
    }

    /**
     * Special accelerated function to retrieve db record fields instead of objects
     *
     * @param string $className
     * @param mixed $query
     * @param string $field
     *
     * @return array
     */
    public function &fetchColumn($className, $query, $field)
    {
        $result = array();

        if (isset($this->driver)) {
            // Store timestamp
            $tsLast = microtime(true);

            // Get SQL
            $sql = $this->prepareSQL($className, $query);

            // Get table column index by its name
            $columnIndex = array_search($field, array_values($className::$_table_attributes));

            try {
                // Perform database query
                $result = $this->driver->query($sql)->fetchAll(\PDO::FETCH_COLUMN, $columnIndex);
            } catch (\PDOException $e) {
                echo("\n" . $sql . '-' . $e->getMessage());
            }

            // Store queries count
            $this->count++;

            // Count elapsed time
            $this->elapsed += microtime(true) - $tsLast;
        }

        return $result;
    }

    /**
     * Retrieve one record from a database, if $className is passed method
     * will try to create an object of that type. If request has failed than
     * method will return empty array or stdClass regarding to $className is
     * passed or not.
     *
     * @param string $sql Query text
     * @param string $className Class name if we want to create object
     * @return array|object Record as array or object
     */
    public function &fetchOne($sql, $className = null)
    {
        // Return value, configure to return correct type
        $result = isset($className) ? new \stdClass() : array();

        if (isset($this->driver)) {
            // Store timestamp
            $tsLast = microtime(true);

            try {
                // Perform database query
                if (!isset($className)) { // Return array
                    $result = $this->driver->query($sql)->fetch(\PDO::FETCH_ASSOC);
                } else { // Create object of passed class name
                    $result = $this->driver->query($sql)->fetchObject($className, array(&$this));
                }

            } catch (\PDOException $e) {
                echo("\n" . $sql . '-' . $e->getMessage());
            }

            // Store queries count
            $this->count++;

            // Count elapsed time
            $this->elapsed += microtime(true) - $tsLast;
        }

        return $result;
    }

    /**
     * Get one record from database by its field value
     * @param string $className Enitity
     * @param string $fieldName Field name
     * @param string $fieldValue Field value
     * @return object Found object instance or an empty stdClass instance
     */
    public function fetchField($className, $fieldName, $fieldValue)
    {
        // Build SQL statement
        $sql = 'SELECT *
        FROM `' . $className::$_table_name . '`
        WHERE `' . $fieldName . '` = ' . $this->driver->quote($fieldValue);

        return $this->fetchOne($sql);
    }

    public function create($className, &$object = null)
    {
        // ??
        $fields = $this->getQueryFields($className, $object);

        // Build SQL query
        $sql = 'INSERT INTO `' . $className::$_table_name . '` (`'
            . implode('`,`', array_keys($fields)) . '`)
            VALUES (' . implode(',', $fields) . ')';

        $this->query($sql);

        // Return last inserted row identifier
        return $this->driver->lastInsertId();
    }

    public function update($className, &$object)
    {
        // ??
        $fields = $this->getQueryFields($className, $object, true);

        // Build SQL query
        $sql = 'UPDATE `' . $className::$_table_name . '` SET ' . implode(',',
                $fields) . ' WHERE ' . $className::$_table_name . '.' . $className::$_primary . '="' . $object->id . '"';

        $this->query($sql);
    }

    public function delete($className, &$object)
    {
        // Build SQL query
        $sql = 'DELETE FROM `' . $className::$_table_name . '` WHERE ' . $className::$_primary . ' = "' . $object->id . '"';

        $this->query($sql);
    }

    /** Count query result */
    public function count($className, $query)
    {
        // Get SQL
        $sql = 'SELECT Count(*) as __Count FROM (' . $this->prepareSQL($className, $query) . ') as __table';

        // Выполним запрос к БД
        $result = $this->fetch($sql);

        return $result[0]['__Count'];
    }

    /**
     * Выполнить защиту значения поля для его безопасного использования в запросах
     *
     * @param string $value Значения поля для запроса
     * @return string $value Безопасное представление значения поля для запроса
     */
    protected function protectQueryValue($value)
    {
        // If magic quotes are on - remove slashes
        if (get_magic_quotes_gpc()) {
            $value = stripslashes($value);
        }

        // Normally escape string
        $value = $this->driver->quote($value);

        // Return value in quotes
        return $value;
    }

    /**
     * Prepare create & update SQL statements fields
     * @param string $className Entity name
     * @param Record $object Database object to get values(if needed)
     * @param bool $straight Way of forming SQL field statements
     * @return array Collection of key => value with SQL fields statements
     */
    protected function &getQueryFields($className, & $object = null, $straight = false)
    {
        // Результирующая коллекция
        $collection = array();

        // Установим флаг получения значений атрибутов из переданного объекта
        $use_values = isset($object);

        // Переберем "настоящее" имена атрибутов схемы данных для объекта
        foreach ($className::$_table_attributes as $attribute => $map_attribute) {
            // Отметки времени не заполняем
            if ($className::$_types[$attribute] == 'timestamp') {
                continue;
            }

            // Основной ключ не заполняем
            if ($className::$_primary == $attribute) {
                continue;
            }

            // Получим значение атрибута объекта защитив от инъекций, если объект передан
            $value = $use_values ? $this->driver->quote($object->$map_attribute) : '';

            // Добавим значение поля, в зависимости от вида вывывода метода
            $collection[$map_attribute] = ($straight ? $className::$_table_name . '.' . $map_attribute . '=' : '') . $value;
        }

        // Вернем полученную коллекцию
        return $collection;
    }

    /** @deprecated Use query() */
    public function &simple_query($sql)
    {
        return $this->query($sql);
    }
}
