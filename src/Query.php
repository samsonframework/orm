<?php declare(strict_types=1);
namespace samsonframework\orm;

/**
 * Database query builder.
 *
 * @author Vitaly Iegorov <egorov@samsonos.com>
 */
class Query extends QueryHandler implements QueryInterface
{
    /** @var string Database table className */
    protected $className;

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
     */
    protected function buildSQL() : string
    {
        $sql = $this->sqlBuilder->buildSelectStatement($this->metadata, $this->joins);
        $sql .= $this->sqlBuilder->buildWhereStatement($this->condition, $this->metadata);
        $sql .= $this->sqlBuilder->buildGroupStatement($this->grouping);
        $sql .= $this->sqlBuilder->buildOrderStatement($this->sorting[0], $this->sorting[1]);
        $sql .= $this->sqlBuilder->buildLimitStatement($this->limitation[0], $this->limitation[1]);

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
        $this->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function exec() : array
    {
        return $this->database->fetchObjects($this->buildSQL(), $this->className);
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
        $this->className = $metadata->className;

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
    public function where(string $fieldName, $fieldValue = null, string $relation = ArgumentInterface::EQUAL) : QueryInterface
    {
        // If empty array is passed
        if (is_string($fieldName)) {
            // Handle empty field value passing to avoid unexpected behaviour
            if (!isset($fieldValue)) {
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
        } else {
            throw new \InvalidArgumentException('You can only pass string as first argument');
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function join(string $entityName)
    {
        $this->joins[$entityName] = [];

        // Chaining
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function groupBy(string $fieldName) : QueryInterface
    {
        $this->grouping[] = $fieldName;

        // Chaining
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function limit(int $quantity, int $offset = 0)
    {
        $this->limitation = [$quantity, $offset];

        // Chaining
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function orderBy(string $fieldName, string $order = 'ASC') : QueryInterface
    {
        $this->sorting[] = array($fieldName, $order);

        // Chaining
        return $this;
    }
}
