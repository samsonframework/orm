<?php declare(strict_types=1);
namespace samsonframework\orm;

use samson\activerecord\dbQuery;
use samsonframework\container\definition\analyzer\annotation\annotation\Service;

/**
 * Database query builder.
 *
 * @author Vitaly Iegorov <egorov@samsonos.com>
 * @Service("query")
 */
class Query extends dbQuery implements QueryInterface
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
    public function find() : array
    {
        return $this->database->fetchObjects($this->buildSQL(), $this->metadata);
    }

    /**
     * {@inheritdoc}
     */
    public function flush() : QueryInterface
    {
        $this->select = [];
        $this->sorting = [];
        $this->grouping = [];
        $this->limitation = [];
        $this->joins = [];
        $this->condition = new Condition();

        return $this;
    }

    /**
     * Build SQL statement from this query.
     *
     * @return string SQL statement
     * @throws \InvalidArgumentException
     */
    protected function buildSQL() : string
    {
        // If none fields are selected - select all fields from parent table
        $this->select = count($this->select) ? $this->select : [$this->metadata->tableName => '*'];

        $sql = $this->sqlBuilder->buildSelectStatement($this->select);
        $sql .= "\n" . $this->sqlBuilder->buildFromStatement(
                array_merge(array_keys($this->select), array_keys($this->joins))
            );

        $whereCondition = $this->sqlBuilder->buildWhereStatement($this->metadata, $this->condition);

        if (isset($whereCondition{0})) {
            $sql .= "\n" . 'WHERE ' . $whereCondition;
        }

        if (count($this->grouping)) {
            $sql .= "\n" . $this->sqlBuilder->buildGroupStatement($this->grouping);
        }

        if (count($this->sorting)) {
            $sql .= "\n" . $this->sqlBuilder->buildOrderStatement($this->sorting[0], $this->sorting[1]);
        }

        if (count($this->limitation)) {
            $sql .= "\n" . $this->sqlBuilder->buildLimitStatement($this->limitation[0], $this->limitation[1]);
        }

        return $sql;
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
    public function first()
    {
        $return = $this->limit(1)->exec();

        return count($return) ? array_shift($return) : null;
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
    public function fields(string $fieldName) : array
    {
        // Return bool or collection
        return $this->database->fetchColumn($this->buildSQL(), $this->metadata->getTableColumnIndex($fieldName));
    }

    /**
     * {@inheritdoc}
     */
    public function entity($metadata) : QueryInterface
    {
        if (is_string($metadata)) {
            // Remove old namespace
            $metadata = strpos($metadata, '\samson\activerecord\\') !== false ? str_replace('\samson\activerecord\\', '', $metadata) : $metadata;
            $metadata = strpos($metadata, 'samson\activerecord\\') !== false ? str_replace('samson\activerecord\\', '', $metadata) : $metadata;
            // Capitalize and add cms namespace
            $metadata = strpos($metadata, '\\') === false ? 'samsoncms\api\generated\\' . ucfirst($metadata) : $metadata;

            $this->metadata = TableMetadata::fromClassName($metadata);
        } else {
            $this->metadata = $metadata;
        }

        $this->flush();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function orderBy(string $fieldName, string $order = 'ASC', string $tableName = null) : QueryInterface
    {
        $this->sorting[0][$tableName ?? $this->metadata->tableName][] = $fieldName;
        $this->sorting[1][] = $order;

        // Chaining
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
    public function isNull(string $fieldName) : QueryInterface
    {
        return $this->where($fieldName, '', ArgumentInterface::ISNULL);
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
        // Add condition argument
        $this->condition->add($fieldName, $fieldValue, $relation);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function notNull(string $fieldName) : QueryInterface
    {
        return $this->where($fieldName, '', ArgumentInterface::NOTNULL);
    }

    /**
     * {@inheritdoc}
     */
    public function notEmpty(string $fieldName) : QueryInterface
    {
        return $this->where($fieldName, '', ArgumentInterface::NOT_EQUAL);
    }

    /**
     * {@inheritdoc}
     */
    public function like(string $fieldName, string $value = '') : QueryInterface
    {
        return $this->where($fieldName, $value, ArgumentInterface::LIKE);
    }

    /**
     * {@inheritdoc}
     */
    public function primary($value) : QueryInterface
    {
        return $this->where($this->metadata->primaryField, $value);
    }
}
