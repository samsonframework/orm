<?php
/**
 * Created by PhpStorm.
 * User: egorov
 * Date: 09.05.2015
 * Time: 13:05
 */
namespace samsonframework\orm;
use samsonframework\orm\exception\EntityNotFound;

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
     * Get entity query manager
     * @param string $entity Entity identifier
     * @return Query Query manager instance
     */
    public function manager($entity)
    {
        return new Query($entity, $this);
    }

    /**
     * Intreal error beautifier
     * @param \Exception $e
     * @param $sql
     */
    private function outputError(\Exception $e, $sql, $text = 'Error executing database query:')
    {
        elapsed('erorr');
        echo("\n" . '<div style="font-size:12px; position:relative; background:red; z-index:9999999;">'
        .'<div style="padding:4px 10px;">'.$text.'</div>'
            .'<div style="padding:0px 10px;">['.htmlspecialchars($e->getMessage()).']</div>'
            .'<textarea style="display:block; width:100%; min-height:100px;">'.$sql . '</textarea></div>');
    }

    /**
     * Proxy function for executing database fetching logic with exception,
     * error, profile handling
     * @param callback $fetcher Callback for fetching
     * @return mixed Fetching function result
     */
    private function execute($fetcher)
    {
        $result = array();

        if (isset($this->driver)) {
            // Store timestamp
            $tsLast = microtime(true);

            try { // Call fetcher
                // Get argument and remove first one
                $args = func_get_args();
                array_shift($args);

                // Proxy calling of fetcher function with passing parameters
                $result = call_user_func_array($fetcher, $args);
            } catch (\PDOException $e) {
                $this->outputError($e, $sql, 'Error executing ['.$fetcher.']');
            }

            // Store queries count
            $this->count++;

            // Count elapsed time
            $this->elapsed += microtime(true) - $tsLast;
        }

        return $result;
    }

    /**
     * High-level database query executor
     * @param string $sql SQL statement
     * @return mixed Database query result
     */
    private function innerQuery($sql)
    {
        try {
            // Perform database query
            $result = $this->driver->prepare($sql)->execute();
        } catch (\PDOException $e) {
            $this->outputError($e, $sql);
        }
    }

    /**
     * Retrieve array of records from a database, if $className is passed method
     * will try to create an object of that type. If request has failed than
     * method will return empty array of stdClass all arrays regarding to $className is
     * passed or not.
     *
     * @param string $sql Query text
     * @return array Collection of arrays or objects
     */
    private function innerFetch($sql, $className = null)
    {
        try {
            // Perform database query
            if (!isset($className)) { // Return array
                return $this->driver->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
            } else { // Create object of passed class name
                return $this->driver->query($sql)->fetchAll(\PDO::FETCH_CLASS, $className, array(&$this));
            }
        } catch (\PDOException $e) {
            $this->outputError($e, $sql, 'Fetching database records:');
        }
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
    private function innerFetchColumn($className, $query, $field)
    {
        // Get SQL
        $sql = $this->prepareSQL($className, $query);

        // TODO: Remove old attributes retrieval
        // Get table column index by its name
        $columnIndex = array_search($field, array_values($className::$_table_attributes));

        try {
            // Perform database query
            return $this->driver->query($sql)->fetchAll(\PDO::FETCH_COLUMN, $columnIndex);
        } catch (\PDOException $e) {
            $this->outputError($e, $sql, 'Error fetching records column values:');
        }
    }

    /**
     * High-level database query executor
     * @param string $sql SQL statement
     * @return mixed Database query result
     */
    public function query($sql)
    {
        return $this->execute(array($this, 'innerQuery'), $sql);
    }

    /**
     * Retrieve array of records from a database, if $className is passed method
     * will try to create an object of that type. If request has failed than
     * method will return empty array of stdClass all arrays regarding to $className is
     * passed or not.
     *
     * @param string $sql Query text
     * @return array Collection of arrays or objects
     */
    public function fetch($sql)
    {
        return $this->execute(array($this, 'innerFetch'), $sql);
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
    public function fetchColumn($className, $query, $field)
    {
        return $this->execute(array($this, 'innerFetchColumn'), $className, $query, $field);
    }

    public function create($className, &$object = null)
    {
        // ??
        $fields = $this->getQueryFields($className, $object);

        $this->query('INSERT INTO `' . $className::$_table_name . '` (`'
            . implode('`,`', array_keys($fields)) . '`)
            VALUES (' . implode(',', $fields) . ')'
        );

        // Return last inserted row identifier
        return $this->driver->lastInsertId();
    }

    public function update($className, &$object)
    {
        $this->query('UPDATE `' . $className::$_table_name . '` SET '
            . implode(',', $this->getQueryFields($className, $object, true))
            . ' WHERE ' . $className::$_table_name . '.' . $className::$_primary . '="'
            . $object->id . '"');
    }

    public function delete($className, &$object)
    {
        $this->query('DELETE FROM `' . $className::$_table_name . '` WHERE '
            . $className::$_primary . ' = "' . $object->id . '"'
        );
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
