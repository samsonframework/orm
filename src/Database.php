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
class Database implements DatabaseInterface
{
    /** @var \PDO Database driver */
    protected $driver;

    /** @var string Database name */
    protected $database;

    /** @var int Amount of milliseconds spent on queries */
    protected $elapsed;

    /** @var int Amount queries executed */
    protected $count;

    /** Do not serialize anything */
    public function __sleep()
    {
        return array();
    }

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

            //new ManagerGenerator($this);
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
     * High-level database query executor
     * @param string $sql SQL statement
     * @return mixed Database query result
     * @deprecated Use execute()
     */
    public function query($sql)
    {
        return $this->execute($sql);
    }

    /**
     * Internal error beautifier.
     *
     * @param \Exception $exception
     * @param            $sql
     * @param string     $text
     *
     * @throws \Exception
     */
    private function outputError(\Exception $exception, $sql, $text = 'Error executing database query:')
    {
        throw $exception;

        echo("\n" . '<div style="font-size:12px; position:relative; background:red; z-index:9999999;">'
            .'<div style="padding:4px 10px;">'.$text.'</div>'
            .'<div style="padding:0px 10px;">['.htmlspecialchars($exception->getMessage()).']</div>'
            .'<textarea style="display:block; width:100%; min-height:100px;">'.$sql . '</textarea></div>');
    }

    /**
     * Proxy function for executing database fetching logic with exception,
     * error, profile handling
     * @param callback $fetcher Callback for fetching
     * @return mixed Fetching function result
     */
    private function executeFetcher($fetcher, $sql)
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
            } catch (\PDOException $exception) {
                $this->outputError($exception, $sql, 'Error executing ['.$fetcher[1].']');
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
            return $this->driver->prepare($sql)->execute();
        } catch (\PDOException $e) {
            $this->outputError($e, $sql);
        }

        return null;
    }

    /**
     * Retrieve array of records from a database, if $className is passed method
     * will try to create an object of that type. If request has failed than
     * method will return empty array of stdClass all arrays regarding to $className is
     * passed or not.
     *
     * @param string $sql SQL statement
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

        return array();
    }

    /**
     * Special accelerated function to retrieve db record fields instead of objects
     *
     * @param string $sql SQL statement
     * @param int $columnIndex Needed column index
     *
     * @return array Database records column value collection
     */
    private function innerFetchColumn($sql, $columnIndex)
    {
        try {
            // Perform database query
            return $this->driver->query($sql)->fetchAll(\PDO::FETCH_COLUMN, $columnIndex);
        } catch (\PDOException $e) {
            $this->outputError($e, $sql, 'Error fetching records column values:');
        }

        return array();
    }

    /**
     * High-level database query executor
     * @param string $sql SQL statement
     * @return mixed Database query result
     */
    public function execute($sql)
    {
        return $this->executeFetcher(array($this, 'innerQuery'), $sql);
    }

    /**
     * Retrieve array of records from a database, if $className is passed method
     * will try to create an object of that type. If request has failed than
     * method will return empty array of stdClass all arrays regarding to $className is
     * passed or not.
     *
     * @param string $sql SQL statement
     * @return array Collection of arrays or objects
     */
    public function fetch($sql)
    {
        return $this->executeFetcher(array($this, 'innerFetch'), $sql);
    }

    /**
     * Special accelerated function to retrieve db record fields instead of objects
     * TODO: Change to be independent of query and class name, just SQL, this SQL
     * should only have one column in SELECT part and then we do not need parameter
     * for this as we can always take 0.
     *
     * @param string $entity Entity identifier
     * @param QueryInterface Query object
     * @param string $field Entity field identifier
     *
     * @return array Collection of rows with field value
     */
    public function fetchColumn($entity, QueryInterface $query, $field)
    {
        // TODO: Remove old attributes retrieval

        return $this->executeFetcher(
            array($this, 'innerFetchColumn'),
            $this->prepareSQL($entity, $query),
            array_search($field, array_values($entity::$_table_attributes))
        );
    }

    /**
     * Count resulting rows.
     *
     * @param string Entity identifier
     * @param QueryInterface Query object
     *
     * @return int Amount of rows
     */
    public function count($entity, QueryInterface $query)
    {
        // Modify query SQL and add counting
        $result = $this->fetch('SELECT Count(*) as __Count FROM (' . $this->prepareSQL($entity, $query) . ') as __table');

        return isset($result[0]) ? (int)$result[0]['__Count'] : 0;
    }

    /**
     * Quote variable for security reasons.
     *
     * @param string $value
     * @return string Quoted value
     */
    protected function quote($value)
    {
        return $this->driver->quote($value);
    }

    /**
     * Convert QueryInterface into SQL statement.
     *
     * @param string Entity identifier
     * @param QueryInterface Query object
     *
     * @return string SQL statement
     */
    protected function prepareSQL($entity, QueryInterface $query)
    {

    }
}
