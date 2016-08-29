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
     * {@inheritdoc}
     */
    public function execute(string $sql)
    {
        // Perform database query
        return $this->driver->prepare($sql)->execute();
    }

    /**
     * @deprecated Use self::fetchArray()
     */
    public function fetch(string $sql)
    {
        return $this->fetchArray($sql);
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
     * @deprecated Use self::fetchColumns
     */
    public function fetchColumn($entity, QueryInterface $query, $field)
    {
        return $this->fetchColumns($this->prepareSQL($entity, $query), array_search($field, array_values($entity::$_table_attributes)));
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
    public function fetchArray(string $sql, string $className = null) : array
    {
        // Perform database query
        if ($className === null) { // Return array
            return $this->driver->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        } else { // Create object of passed class name
            return $this->driver->query($sql)->fetchAll(\PDO::FETCH_CLASS, $className);
        }
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
        return $this->driver->query($sql)->fetchAll(\PDO::FETCH_COLUMN, $columnIndex);
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
