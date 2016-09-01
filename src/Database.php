<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: egorov
 * Date: 09.05.2015
 * Time: 13:05
 */
namespace samsonframework\orm;

/**
 * Database management class.
 *
 * @package samsonframework\orm
 * @\samsonframework\containerannotation\Service("database")
 */
class Database implements DatabaseInterface
{
    /** Table name prefix */
    public static $prefix = '';

    /** @var \PDO Database driver */
    protected $driver;

    /** @var  SQLBuilder */
    protected $sqlBuilder;

    /**
     * Database constructor.
     *
     * @param \PDO $driver
     *
     * @\samsonframework\containerannotation\InjectArgument(driver="\PDO")
     * @\samsonframework\containerannotation\InjectArgument(sqlBuilder="\samsonframework\orm\SQLBuilder")
     */
    public function __construct(\PDO $driver, SQLBuilder $sqlBuilder)
    {
        $this->driver = $driver;
        $this->sqlBuilder = $sqlBuilder;

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
     * {@inheritdoc}
     */
    public function insert(TableMetadata $tableMetadata, array $columnValues)
    {
        $this->execute($this->sqlBuilder->buildInsertStatement($tableMetadata, $columnValues));

        return $this->driver->lastInsertId();
    }

    /**
     * {@inheritdoc}
     */
    public function update(TableMetadata $tableMetadata, array $columnValues, Condition $condition)
    {
        return $this->execute($this->sqlBuilder->buildUpdateStatement($tableMetadata, $columnValues, $condition));
    }

    /**
     * Execute SQL query.
     *
     * @param string $sql SQL statement
     *
     * @deprecated Use self::execute()
     * @return mixed Driver result
     */
    public function fetch(string $sql)
    {
        return $this->fetchArray($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchArray(string $sql) : array
    {
        return $this->driver->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * {@inheritdoc}
     */
    public function database() : string
    {
        return $this->driver->query('select database()')->fetchColumn();
    }

    /**
     * {@inheritdoc}
     */
    public function count(string $sql) : int
    {
        // Modify query SQL and add counting
        $result = $this->fetchArray('SELECT Count(*) as __Count FROM (' . $sql . ') as __table');

        return array_key_exists(0, $result) ? (int)$result[0]['__Count'] : 0;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchObjects(string $sql, string $className, string $primaryField) : array
    {
        $grouped = [];
        foreach ($this->driver->query($sql)->fetchAll(\PDO::FETCH_CLASS, $className) as $instance) {
            $grouped[$instance->$primaryField] = $instance;
        }

        return $grouped;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchColumn(string $sql, int $columnIndex) : array
    {
        return $this->driver->query($sql)->fetchAll(\PDO::FETCH_COLUMN, $columnIndex);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchObjectsWithJoin(string $sql, TableMetadata $metadata, array $joinedMetadata) : array
    {
        return $this->createEntities(
            $this->fetchArray($sql),
            $metadata,
            $joinedMetadata
        );
    }

    /**
     * Create entity instances and its joined entities.
     *
     * @param array           $rows
     * @param TableMetadata   $metadata
     * @param TableMetadata[] $joinedMetadata
     *
     * @return array
     * @throws \InvalidArgumentException
     */
    protected function createEntities(array $rows, TableMetadata $metadata, array $joinedMetadata = [])
    {
        $objects = [];

        /** @var array $entityRows Iterate entity rows */
        foreach ($this->groupResults($rows, $metadata->primaryField) as $primaryValue => $entityRows) {
            // Create entity instance
            $instance = $objects[$primaryValue] = new $metadata->className($this, $metadata);

            // TODO: $attributes argument should be filled with selected fields?
            $this->fillEntityFieldValues($instance, $metadata->columns, $entityRows[0]);

            // Iterate inner rows for nested entities creation
            foreach ($entityRows as $row) {
                // Iterate all joined entities
                foreach ($joinedMetadata as $joinMetadata) {
                    if (array_key_exists($joinMetadata->primaryField, $row)) {
                        // Create joined instance and add to parent instance
                        $joinedInstance = new $joinMetadata->className($this, $joinMetadata);

                        // TODO: We need to change value retrieval
                        $this->fillEntityFieldValues($joinedInstance, $joinMetadata->columns, $row);

                        // Store joined instance by primary field value
                        $instance->joined[$joinMetadata->className][$row[$joinMetadata->primaryField]] = $joinedInstance;
                    } else {
                        throw new \InvalidArgumentException(
                            'Cannot join ' . $joinMetadata->className . ' - primary field ' . $joinMetadata->primaryField . ' not found'
                        );
                    }
                }
            }
        }

        return $objects;
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
     * Fill entity instance fields from row column values according to entity value attributes.
     *
     * @param mixed $instance   Entity instance
     * @param array $attributes Metadata entity attributes
     * @param array $row        Database results row
     */
    protected function fillEntityFieldValues($instance, array $attributes, array $row)
    {
        foreach ($row as $columnName => $columnValue) {
            // If database row has aliased field column
            if (array_key_exists($columnName, $attributes)) {
                $columnName = $attributes[$columnName];
                // Store attribute value
                $instance->$columnName = $columnValue;
            }
        }

        // Call handler for object filling
        $instance->filled();
    }

    /**
     * Quote variable for security reasons.
     *
     * @param string $value
     *
     * @return string Quoted value
     */
    protected function quote($value)
    {
        return $this->driver->quote($value);
    }
}
