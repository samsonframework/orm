<?php declare(strict_types=1);
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
    /** Table name prefix */
    public static $prefix = '';

    /** @var \PDO Database driver */
    protected $driver;

    /** @var string Database name */
    protected $database;

    /** @var int Amount of milliseconds spent on queries */
    protected $elapsed;

    /** @var int Amount queries executed */
    protected $count;

    /**
     * Database constructor.
     *
     * @param PDO $driver
     */
    public function __construct(\PDO $driver)
    {
        $this->driver = $driver;

        // Set correct encodings
        $this->execute("set character_set_client='utf8'");
        $this->execute("set character_set_results='utf8'");
        $this->execute("set collation_connection='utf8_general_ci'");
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
     * error, profile handling.
     *
     * @param callback $fetcher Callback for fetching
     *
     * @return mixed Fetching function result
     * @throws \Exception
     */
    private function executeFetcher($fetcher, $sql)
    {
        $result = [];

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
     * {@inheritdoc}
     */
    public function execute(string $sql)
    {
        return $this->executeFetcher(array($this, 'innerQuery'), $sql);
    }

    /**
     * {@inheritdoc}
     */
    public function fetch(string $sql)
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
     * @deprecated
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

    /**
     * {@inheritdoc}
     */
    public function fetchArray(string $sql) : array
    {
        return $this->fetch($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchObjects(string $sql, string $className) : array
    {
        return $this->createEntities(
            $this->fetchArray($sql),
            $className::$_primary,
            $className,
            []
            //$query->join,
            //array_merge($query->own_virtual_fields, $query->virtual_fields)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function fetchColumns(string $sql, int $columnIndex) : array
    {
        return $this->executeFetcher([$this, 'innerFetchColumn'], $sql, $columnIndex);
    }

    /**
     * Regroup database rows by primary field value.
     *
     * @param array  $rows Collection of records received from database
     * @param string $primaryField Primary field name for grouping
     *
     * @return array Grouped rows by primary field value
     */
    protected function groupResults(array $rows, string $primaryField) : array
    {
        /** @var array $grouped Collection of database rows grouped by primary field value */
        $grouped = [];

        // Iterate result set
        for ($i = 0, $rowsCount = count($rows); $i < $rowsCount; $i++) {
            $row = $rows[$i];

            // Group by primary field value
            $grouped[$row[$primaryField]][] = $row;
        }

        return $grouped;
    }

    /**
     * Fill entity instance fields from row column values according to entity metadata attributes.
     *
     * @param mixed $instance Entity instance
     * @param array $attributes Metadata entity attributes
     * @param array $row        Database results row
     */
    protected function fillEntityFieldValues($instance, array $attributes, array $row)
    {
        // Iterate attribute metadata
        foreach ($attributes as $alias) {
            // If database row has aliased field column
            if (array_key_exists($alias, $row)) {
                // Store attribute value
                $instance->$alias = $row[$alias];
            } else {
                throw new \InvalidArgumentException('Database row does not have requested column:'.$alias);
            }
        }

        // Call handler for object filling
        $instance->filled();
    }

    /**
     * Create entity instances and its joined entities.
     *
     * @param array  $rows
     * @param string $primaryField
     * @param string $className
     * @param array  $joinedClassNames
     *
     * @return array
     */
    protected function createEntities(array $rows, string $primaryField, string $className, array $joinedClassNames)
    {
        $objects = [];

        /** @var array $entityRows Iterate entity rows */
        foreach ($this->groupResults($rows, $primaryField) as $primaryValue => $entityRows) {
            // Create entity instance
            $instance = $objects[$primaryValue] = new $className($this);

            // TODO: $attributes argument should be filled with selected fields?
            $this->fillEntityFieldValues($instance, $className::$_attributes, $entityRows[0]);

            // Iterate inner rows for nested entities creation
            foreach ($entityRows as $row) {
                // Iterate all joined entities
                foreach ($joinedClassNames as $joinedClassName) {
                    // Create joined instance and add to parent instance
                    $joinedInstance = $instance->$joinedClassName[] = new $joinedClassName($this);

                    // TODO: We need to change metadata retrieval
                    $this->fillEntityFieldValues($joinedInstance, $joinedClassName::$_attributes, $row);
                }
            }
        }

        return $objects;
    }
}
