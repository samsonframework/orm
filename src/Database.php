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

    /** @var int Amount of miliseconds spent on queries */
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

            // Create connection string
            $dsn = $driver . ':host=' . $host . ';port='.$port.';dbname=' . $database . ';charset=' . $charset;

            $this->database = $database;

            // Set options
            $opt = array(
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
            );

            try { // Connect to a database
                $this->driver = new \PDO($dsn, $username, $password, $opt);
            } catch (\PDOException $e) {
                // Handle exception
            }
        }
    }

    /**
     * High-level database query executor
     * @param string $sql SQL statement
     * @return mixed Database query result
     */
    public function & query($sql)
    {
        $result = array();

        if (isset($this->driver)) {
            // Store timestamp
            $tsLast = microtime(true);

            try {
                // Perform database query
                $result = $this->driver->query($sql)->execute();
            } catch (\PDOException $e) {
                echo("\n" . $sql . '-' . $e->getMessage());
            }

            // Store queries count
            $this->count++;

            // Отметим затраченное время на выполнение запроса
            $this->elapsed += microtime(true) - $tsLast;
        }

        return $result;
    }

    /** {@inheritdoc} */
    public function & fetch($sql)
    {
        $result = array();

        if (isset($this->driver)) {
            // Store timestamp
            $tsLast = microtime(true);

            try {
                // Perform database query
                $result = $this->driver->query($sql)->fetchAll();
            } catch (\PDOException $e) {
                echo("\n" . $sql . '-' . $e->getMessage());
            }

            // Store queries count
            $this->count++;

            // Отметим затраченное время на выполнение запроса
            $this->elapsed += microtime(true) - $tsLast;
        }

        return $result;
    }

    /**
     * Special accelerated function to retrieve db record fields instead of objects
     *
     * @param string $className
     * @param dbQuery $query
     * @param string $field
     *
     * @return array
     */
    public function & fetchColumn($className, $query, $field)
    {
        // Get SQL
        $sql = $this->prepareSQL($className, $query);

        // Get table column index by its name
        $columnIndex = array_search($field, array_values($className::$_table_attributes));

        $result = $this->driver->query($sql)->fetchAll(\PDO::FETCH_COLUMN, $columnIndex);

        // Вернем коллекцию полученных объектов
        return $result;
    }

    public function create($className, & $object = null)
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

    public function update($className, & $object)
    {
        // ??
        $fields = $this->getQueryFields($className, $object, true);

        // Build SQL query
        $sql = 'UPDATE `' . $className::$_table_name . '` SET ' . implode(',',
                $fields) . ' WHERE ' . $className::$_table_name . '.' . $className::$_primary . '="' . $object->id . '"';

        $this->query($sql);
    }

    public function delete($className, & $object)
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

    /** @deprecated Use query() */
    public function & simple_query($sql)
    {
        return $this->query($sql);
    }
}
