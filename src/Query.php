<?php declare(strict_types=1);
namespace samsonframework\orm;

/**
 * Database query builder.
 *
 * @author Vitaly Iegorov <egorov@samsonos.com>
 */
class Query extends QueryHandler implements QueryInterface
{
    /** @var TableMetadata */
    protected $metadata;

    /** @var array Collection of parent table selected fields */
    protected $select = [];

    /** @var array Collection of entity field names for sorting order */
    protected $sorting = [];

    /** @var array Collection of entity field names for grouping query results */
    protected $grouping = [];

    /** @var array Collection of query results limitations */
    protected $limitation = [];

    /** @var TableMetadata[] Collection of joined entities */
    protected $joins = [];

    /** @var Condition Query entity condition group */
    protected $condition;

    /** @var DatabaseInterface Database instance */
    protected $database;

    /** @var SQLBuilder SQL builder */
    protected $sqlBuilder;

    /**
     * Build SQL statement from this query.
     *
     * @return string SQL statement
     * @throws \InvalidArgumentException
     */
    protected function buildSQL() : string
    {
        $sql = $this->sqlBuilder->buildSelectStatement($this->select);
        $sql .= "\n".$this->sqlBuilder->buildFromStatement(array_merge(array_keys($this->select), $this->joins));
        $sql .= "\n".$this->sqlBuilder->buildWhereStatement($this->metadata, $this->condition);
        $sql .= "\n".$this->sqlBuilder->buildGroupStatement($this->grouping);
        $sql .= "\n".$this->sqlBuilder->buildOrderStatement($this->sorting[0], $this->sorting[1]);
        $sql .= "\n".$this->sqlBuilder->buildLimitStatement($this->limitation[0], $this->limitation[1]);

        return $sql;
    }

    /**
     * Query constructor.
     *
     * @param               Database Database instance
     * @param SQLBuilder    $sqlBuilder
     */
    public function __construct(Database $database, SQLBuilder $sqlBuilder)
    {
        $this->database = $database;
        $this->sqlBuilder = $sqlBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function exec() : array
    {
        return $this->database->fetchObjects($this->buildSQL(), $this->metadata->className);
    }

    /**
     * {@inheritdoc}
     */
    public function count() : int
    {
        return $this->database->count($this->buildSQL());
    }

    /**
     * {@inheritdoc}
     */
    public function first() : RecordInterface
    {
        $return = $this->limit(1)->exec();

        return count($return) ? array_shift($return) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function fields(string $fieldName) : array
    {
        // Return bool or collection
        return $this->database->fetchColumn($this->buildSQL(), $this->metadata->getTableColumnIndex($fieldName));
    }

    /**
     * {@inheritdoc}
     */
    public function entity(TableMetadata $metadata) : QueryInterface
    {
        $this->grouping = [];
        $this->limitation = [];
        $this->sorting = [];
        $this->condition = new Condition();
        $this->metadata = $metadata;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function whereCondition(ConditionInterface $condition) : QueryInterface
    {
        $this->condition->addCondition($condition);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function where(
        string $fieldName,
        $fieldValue = null,
        string $relation = ArgumentInterface::EQUAL
    ) : QueryInterface
    {
        // Handle empty field value passing to avoid unexpected behaviour
        if ($fieldValue !== null) {
            $relation = ArgumentInterface::ISNULL;
            $fieldValue = '';
        } elseif (is_array($fieldValue) && !count($fieldValue)) {
            // TODO: We consider empty array passed as condition value as NULL, illegal condition
            $relation = ArgumentInterface::EQUAL;
            $fieldName = '1';
            $fieldValue = '0';
        }

        // Add condition argument
        $this->condition->add($fieldName, $fieldValue, $relation);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function select(string $tableName, string $fieldName) : QueryInterface
    {
        $this->select[$tableName][] = $fieldName;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function join(string $entityName) : QueryInterface
    {
        $this->joins[$entityName] = [];

        // Chaining
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function groupBy(string $tableName, string $fieldName) : QueryInterface
    {
        $this->grouping[$tableName][] = $fieldName;

        // Chaining
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function limit(int $quantity, int $offset = 0) : QueryInterface
    {
        $this->limitation = [$quantity, $offset];

        // Chaining
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function orderBy(string $tableName, string $fieldName, string $order = 'ASC') : QueryInterface
    {
        $this->sorting[$tableName][] = [$fieldName, $order];

        // Chaining
        return $this;
    }
}
