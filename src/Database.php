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
     * @param \PDO $driver
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
     * {@inheritdoc}
     */
    public function count(string $sql) : int
    {
        // Modify query SQL and add counting
        $result = $this->fetchArray('SELECT Count(*) as __Count FROM (' . $sql . ') as __table');

        return array_key_exists(0, $result) ? (int)$result[0]['__Count'] : 0;
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
     * {@inheritdoc}
     */
    public function fetchArray(string $sql) : array
    {
        return $this->driver->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchObjects(string $sql, string $className) : array
    {
        $grouped = [];
        $primaryField = $className::$_primary;
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
     * Fill entity instance fields from row column values according to entity className attributes.
     *
     * @param mixed $instance   Entity instance
     * @param array $attributes Metadata entity attributes
     * @param array $row        Database results row
     *
     * @throws \InvalidArgumentException
     */
    protected function fillEntityFieldValues($instance, array $attributes, array $row)
    {
        // Iterate attribute className
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
     * @param array           $rows
     * @param TableMetadata   $metadata
     * @param TableMetadata[] $joinedMetadata
     *
     * @return array
     * @throws \InvalidArgumentException
     */
    protected function createEntities(array $rows, TableMetadata $metadata, array $joinedMetadata)
    {
        $objects = [];

        /** @var array $entityRows Iterate entity rows */
        foreach ($this->groupResults($rows, $metadata->primaryField) as $primaryValue => $entityRows) {
            // Create entity instance
            $instance = $objects[$primaryValue] = new $metadata->className($this);

            // TODO: $attributes argument should be filled with selected fields?
            $this->fillEntityFieldValues($instance, $metadata->columns, $entityRows[0]);

            // Iterate inner rows for nested entities creation
            foreach ($entityRows as $row) {
                // Iterate all joined entities
                foreach ($joinedMetadata as $joinMetadata) {
                    if (array_key_exists($joinMetadata->primaryField, $row)) {
                        // Create joined instance and add to parent instance
                        $joinedInstance = new $joinMetadata->className($this);

                        // TODO: We need to change className retrieval
                        $this->fillEntityFieldValues($joinedInstance, $joinMetadata->columns, $row);

                        // Store joined instance by primary field value
                        $instance->joined[$joinMetadata->className][$row[$joinMetadata->primaryField]] = $joinedInstance;
                    } else {
                        throw new \InvalidArgumentException(
                            'Cannot join '.$joinMetadata->className.' - primary field '.$joinMetadata->primaryField.' not found'
                        );
                    }
                }
            }
        }

        return $objects;
    }
}
