<?php declare(strict_types = 1);
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>.
 * on 29.08.16 at 14:58
 */
namespace samsonframework\orm;

/**
 * SQL statement builder class.
 *
 * @author Vitaly Egorov <egorov@samsonos.com>
 */
class SQLBuilder
{
    const NUMERIC_COLUMNS_TYPES = ['int', 'float', 'longint', 'smallint', 'tinyint'];
    const DATE_COLUMNS_TYPES = ['date', 'datetime', 'timestamp'];

    /**
     * Build update statement.
     *
     * @param TableMetadata  $tableMetadata Table metadata
     * @param array          $columnValues  Collection of columnName => columnValue
     * @param Condition|null $condition     Update filtering condition
     *
     * @return string Update SQL statement
     * @throws \InvalidArgumentException
     */
    public function buildUpdateStatement(TableMetadata $tableMetadata, array $columnValues, Condition $condition = null) : string
    {
        $sql = [];
        foreach ($columnValues as $columnName => $columnValue) {
            $columnName = $tableMetadata->getTableColumnName($columnName);

            // Check for null on not nullable columns
            if (!$tableMetadata->isColumnNullable($columnName) && $columnValue === null) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Cannot update column "%s::%s" with null - column is not nullable',
                        $tableMetadata->tableName,
                        $columnName
                    )
                );
            }

            // Do not update primary field
            if ($tableMetadata->getTablePrimaryField() === $columnName) {
                continue;
            }
            $sql[] = $this->buildFullColumnName($tableMetadata->tableName, $columnName) .
                $this->buildArgumentValue(
                    $columnValue,
                    $tableMetadata->getTableColumnType($columnName),
                    ArgumentInterface::EQUAL,
                    $tableMetadata->isColumnNullable($columnName),
                    $tableMetadata->getColumnDefaultValue($columnName)
                );
        }

        return 'UPDATE `' . $tableMetadata->tableName . '` SET ' . implode(', ', $sql)
        . ($condition !== null ? $this->buildWhereStatement($tableMetadata, $condition) : '');
    }

    /**
     * Build full table column name.
     *
     * @param string $tableName  Table name
     * @param string $columnName Field name
     *
     * @return string Full table column name
     */
    protected function buildFullColumnName(string $tableName, string $columnName) : string
    {
        return '`' . $tableName . '`.' . ($columnName === '*' ? '*' : '`' . $columnName . '`');
    }

    /**
     * Build argument value statement.
     *
     * @param string|array $value      Argument column value
     * @param string       $columnType Argument column type
     * @param string       $relation   Argument relation
     * @param bool         $nullable
     * @param string       $defaultValue
     *
     * @return string Argument relation with value statement
     */
    protected function buildArgumentValue(
        $value,
        string $columnType,
        string $relation,
        bool $nullable = false,
        $defaultValue = ''
    )
    {
        return is_array($value)
            ? $this->buildArrayValue($columnType, $value, $relation)
            : $this->buildValue($columnType, $value, $relation, $nullable, $defaultValue);
    }

    /**
     * Build array argument value statement.
     *
     * @param string $columnType Table column type
     * @param array  $value      Table column array value
     * @param string $relation   Table column relation to value
     *
     * @return string Array argument relation with value statement
     */
    protected function buildArrayValue(string $columnType, array $value, string $relation = 'IN') : string
    {
        $relation = $relation === ArgumentInterface::NOT_EQUAL ? 'NOT IN' : 'IN';

        return $this->isColumnNumeric($columnType)
            ? $this->buildNumericArrayValue($value, $relation)
            : $this->buildStringArrayValue($value, $relation);
    }

    /**
     * Define if table column type is numeric.
     *
     * @param string $columnType Table column type
     *
     * @return bool True if column type is numeric
     */
    protected function isColumnNumeric(string $columnType) : bool
    {
        return in_array($columnType, self::NUMERIC_COLUMNS_TYPES, true);
    }

    /**
     * Build array with numeric values statement.
     *
     * @param array  $value    Array with numeric values
     * @param string $relation Table column relation to value
     *
     * @return string Array with numeric values statement
     */
    protected function buildNumericArrayValue(array $value, string $relation) : string
    {
        return $relation . ' (' . implode(',', $value) . ')';
    }

    /**
     * Build array string value statement.
     *
     * @param array  $value    Array with string values
     * @param string $relation Table column relation to value
     *
     * @return string Array with string values statement
     */
    protected function buildStringArrayValue(array $value, string $relation) : string
    {
        return $relation . ' ("' . implode('","', $value) . '")';
    }

    /**
     * Build not array argument value statement.
     *
     * @param string $columnType Table column type
     * @param mixed  $value      Table column value
     * @param string $relation   Table column relation to value
     *
     * @return string Not array argument relation with value statement
     */
    protected function buildValue(
        string $columnType,
        $value,
        string $relation,
        bool $nullable = false,
        $defaultValue = ''
    ) : string
    {
        // Append space to relation if present
        $relation = strlen($relation) ? $relation . ' ' : '';
        
        if ($this->isColumnNumeric($columnType)) {
            return $relation . $this->buildNumericValue($value);
        } elseif ($this->isColumnDate($columnType)) {
            return $relation . $this->buildDateValue($value);
        } elseif (is_string($value)) {
            return $relation . $this->buildStringValue($value);
        } elseif ($value === null) {
            if ($nullable) {
                return $relation . $this->buildNullValue();
            } else {
                return $relation . $defaultValue;
            }
        }

        throw new \InvalidArgumentException('Cannot build column value ' . $value);
    }

    /**
     * Build not array numeric value statement.
     *
     * @param mixed $value Numeric value
     *
     * @return string Not array numeric value statement
     */
    protected function buildNumericValue($value) : string
    {
        return $value !== null ? (string)$value : '0';
    }

    /**
     * Define if table column type is date.
     *
     * @param string $columnType Table column type
     *
     * @return bool True if column type is date
     */
    protected function isColumnDate(string $columnType) : bool
    {
        return in_array($columnType, self::DATE_COLUMNS_TYPES, true);
    }

    /**
     * Build not array date value statement.
     *
     * @param mixed $value Date value
     *
     * @return string Not array date value statement
     */
    protected function buildDateValue($value) : string
    {
        return '"' . $value . '"';
    }

    /**
     * Build not array string value statement.
     *
     * @param string $value String value
     *
     * @return string Not array string value statement
     */
    protected function buildStringValue(string $value) : string
    {
        return '"' . $value . '"';
    }

    /**
     * Build not array NULL value statement.
     *
     * @return string Not array string value statement
     */
    protected function buildNullValue() : string
    {
        return 'NULL';
    }

    /**
     * Build where statement.
     *
     * @param TableMetadata $metadata
     * @param Condition     $condition
     *
     * @return string Limitation statement
     * @throws \InvalidArgumentException
     *
     */
    public function buildWhereStatement(TableMetadata $metadata, Condition $condition) : string
    {
        $conditions = [];

        foreach ($condition as $argument) {
            if ($argument instanceof ConditionInterface) {
                $result = $this->buildWhereStatement($metadata, $argument);
            } else {
                $result = $this->buildArgumentCondition($argument, $metadata);
            }
            if (isset($result{0})) {
                $conditions[] = $result;
            }
        }

        if (count($conditions)) {
            return '(' . implode(') ' . $condition->relation . ' (', $conditions) . ')';
        } else { // If arguments is empty return empty where statement
            return '';
        }
    }

    /**
     * Build argument condition.
     *
     * @param Argument      $argument Condition argument
     * @param TableMetadata $metadata Table metadata
     *
     * @return string Argument condition statement
     * @throws \InvalidArgumentException If argument column does not exist
     */
    protected function buildArgumentCondition(Argument $argument, TableMetadata $metadata)
    {
        switch ($argument->relation) {
            case ArgumentInterface::OWN:
                return $this->buildOwnCondition($argument->field);
            case ArgumentInterface::ISNULL:
            case ArgumentInterface::NOTNULL:
                $columnName = $metadata->getTableColumnName($argument->field);
                return $this->buildNullCondition($columnName, $argument->relation);
            default:
                $columnName = $metadata->getTableColumnName($argument->field);
                return $this->buildCondition(
                    $columnName,
                    $this->buildArgumentValue(
                        $argument->value,
                        $metadata->getTableColumnType($columnName),
                        $argument->relation
                    )
                );
        }
    }

    /**
     * Build own  condition statement.
     *
     * @param string $ownCondition Condition statement
     *
     * @return string Own condition statement
     */
    protected function buildOwnCondition(string $ownCondition) : string
    {
        return $this->buildCondition($ownCondition);
    }

    /**
     * Build generic condition statement.
     *
     * @param string $columnName Table column name
     * @param string $relation   Table column value relation
     * @param string $value      Table column value
     *
     * @return string Generic condition statement
     */
    protected function buildCondition(string $columnName, string $relation = '', string $value = '') : string
    {
        return trim($columnName . ' ' . $relation . ' ' . $value);
    }

    /**
     * Build is null/not null condition statement.
     *
     * @param string $columnName Table column name
     * @param string $nullRelation Table column null relation
     *
     * @return string Is null/not null condition statement
     */
    protected function buildNullCondition(string $columnName, string $nullRelation) : string
    {
        return $this->buildCondition($columnName, $nullRelation);
    }

    /**
     * Build insert statement.
     *
     * @param TableMetadata $tableMetadata Table metadata
     * @param array         $columnValues  Collection of columnName => columnValue
     *
     * @return string Insert SQL statement
     * @throws \InvalidArgumentException
     */
    public function buildInsertStatement(TableMetadata $tableMetadata, array $columnValues) : string
    {
        $valuesSQL = [];
        $columnNames = [];
        foreach ($columnValues as $columnName => $columnValue) {
            $columnNames[] = $columnName = $tableMetadata->getTableColumnName($columnName);
            $valuesSQL[] = $this->buildArgumentValue(
                $columnValue,
                $tableMetadata->columnTypes[$columnName],
                '',
                $tableMetadata->columnNullable[$columnName],
                $tableMetadata->columnDefaults[$columnName]
            );
        }

        $sql = 'INSERT INTO `' . $tableMetadata->tableName . '` (`' . implode('`, `', $columnNames) . '`) ';
        $sql .= 'VALUES (' . implode(', ', $valuesSQL) . ')';

        return $sql;
    }

    /**
     * Build selected fields SELECT statement part.
     *
     * @param array $tableColumns Tables and column names collection
     *
     * @return string SELECT statement
     */
    public function buildSelectStatement(array $tableColumns) : string
    {
        return 'SELECT ' . implode(', ', $this->buildFullColumnNames($tableColumns));
    }

    /**
     * Build full table column names collection.
     *
     * @param array $tableColumns Tables and column names collection
     *
     * @return array Collection of full column names for query
     */
    protected function buildFullColumnNames(array $tableColumns) : array
    {
        $grouping = [];
        foreach ($tableColumns as $tableName => $columnNames) {
            /** @var array $columnNames */
            foreach ($columnNames = is_array($columnNames) ? $columnNames : [$columnNames] as $columnName) {
                $grouping[] = $this->buildFullColumnName($tableName, $columnName);
            }
        }

        return $grouping;
    }

    /**
     * Build FROM statement part.
     *
     * @param array $tableNames Tables and column names collection
     *
     * @return string FROM statement
     */
    public function buildFromStatement(array $tableNames = []) : string
    {
        return 'FROM `' . implode('`, `', $tableNames) . '`';
    }

    /**
     * Build grouping statement.
     *
     * @param array $tableColumns Tables and column names collection
     *
     * @return string Grouping statement
     */
    public function buildGroupStatement(array $tableColumns) : string
    {
        return 'GROUP BY ' . implode(', ', $this->buildFullColumnNames($tableColumns));
    }

    /**
     * Build ordering statement.
     *
     * @param array $tableColumns Tables and column names collection
     * @param array $orders       Collection of columns sorting order
     *
     * @return string Ordering statement
     * @throws \InvalidArgumentException
     */
    public function buildOrderStatement(array $tableColumns, array $orders) : string
    {
        $ordering = [];
        $i = 0;
        foreach ($this->buildFullColumnNames($tableColumns) as $columnName) {
            $ordering[] = $columnName . ' ' . ($orders[$i++] ?? 'ASC');
        }

        return 'ORDER BY ' . implode(', ', $ordering);
    }

    /**
     * Build limitation statement.
     *
     * @param int $rows   Rows amount for limitation
     * @param int $offset Rows offset
     *
     * @return string Limitation statement
     */
    public function buildLimitStatement(int $rows, int $offset = 0) : string
    {
        return 'LIMIT ' . $offset . ', ' . $rows;
    }
}
