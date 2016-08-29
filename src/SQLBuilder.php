<?php declare(strict_types = 1);
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>.
 * on 29.08.16 at 14:58
 */
namespace samsonframework\orm;

/**
 * Class QueryToSQL
 *
 * @author Vitaly Egorov <egorov@samsonos.com>
 */
class SQLBuilder
{
    const NUMERIC_COLUMNS_TYPES = ['int', 'float', 'longint', 'smallint', 'tinyint'];

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
        return '`'.$tableName.'`.`'.$columnName.'`';
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
     * Build selected fields SELECT statement part.
     *
     * @param array $tableColumns Tables and column names collection
     *
     * @return string SELECT statement
     */
    public function buildSelectStatement(array $tableColumns) : string
    {
        return 'SELECT '.implode(', ', $this->buildFullColumnNames($tableColumns));
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
        return 'FROM `'.implode('`, `', $tableNames).'`';
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
     * @param array $orders Collection of columns sorting order
     *
     * @return string Ordering statement
     * @throws \InvalidArgumentException
     */
    public function buildOrderStatement(array $tableColumns, array $orders) : string
    {
        $ordering = [];
        $i = 0;
        foreach ($this->buildFullColumnNames($tableColumns) as $columnName) {
            $ordering[] = $columnName.' '. ($orders[$i++] ?? 'ASC');
        }

        return 'ORDER BY ' . implode(', ', $ordering);
    }

    /**
     * Build limitation statement.
     *
     * @param int $rows Rows amount for limitation
     * @param int $offset Rows offset
     *
     * @return string Limitation statement
     */
    public function buildLimitStatement(int $rows, int $offset = 0) : string
    {
        return 'LIMIT ' . $offset . ', ' . $rows;
    }

    /**
     * Build where statement.
     *
     * @param TableMetadata      $metadata
     * @param Condition $condition
     *
     * @return string Limitation statement
     *
     */
    public function buildWhereStatement(TableMetadata $metadata, Condition $condition) : string
    {
        $conditions = [];

        foreach ($condition as $argument) {
            if ($argument instanceof ConditionInterface) {
                $conditions[] = $this->buildWhereStatement($metadata, $argument);
            } else {
                $conditions[] = $this->parseCondition($argument, $metadata);
            }
        }

        return '(' . implode(') ' . $condition->relation . ' (', $conditions) . ')';
    }

    protected function buildCondition(string $columnName, string $relation = '', string $value = '') : string
    {
        return trim($columnName . ' ' . $relation . ' ' . $value);
    }

    protected function buildOwnCondition(string $ownCondition) : string
    {
        return $this->buildCondition($ownCondition);
    }

    protected function buildNullCondition(string $columnName, string $nullRelation) : string
    {
        return $this->buildCondition($columnName, $nullRelation);
    }

    protected function isColumnNumeric(string $columnType) : bool
    {
        return in_array($columnType, self::NUMERIC_COLUMNS_TYPES, true);
    }

    protected function buildNumericArrayValue(array $array, string $relation) : string
    {
        return $relation.' (' . implode(',', $array) . ')';
    }

    protected function buildStringArrayValue(array $array, string $relation) : string
    {
        return $relation.' ("' . implode('","', $array) . '")';
    }

    protected function buildArrayValue(string $columnType, array $array, string $relation = 'IN') : string
    {
        $relation = $relation === ArgumentInterface::NOT_EQUAL ? 'NOT IN' : 'IN';

        return $this->isColumnNumeric($columnType)
            ? $this->buildNumericArrayValue($array, $relation)
            : $this->buildStringArrayValue($array, $relation);
    }

    protected function buildNumericValue($value) : string
    {
        return (string)$value;
    }

    protected function buildStringValue($value) : string
    {
        return '"'.$value.'"';
    }

    protected function buildValue(string $columnType, $value, string $relation) : string
    {
        return $this->isColumnNumeric($columnType)
            ? $relation . ' ' . $this->buildNumericValue($value)
            : $relation . ' ' . $this->buildStringValue($value);
    }

    protected function buildArgumentValue($value, string $columnType, string $relation)
    {
        return is_array($value)
            ? $this->buildArrayValue($columnType, $value, $relation)
            : $this->buildValue($columnType, $value, $relation);
    }

    protected function buildArgumentCondition(string $columnName, string $columnType, string $relation, $value)
    {
        return $this->buildCondition(
            $columnName,
            $this->buildArgumentValue($value, $columnType, $relation)
        );
    }

    /**
     * @param Argument      $argument
     * @param TableMetadata $metadata
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function parseCondition(Argument $argument, TableMetadata $metadata)
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
                $columnType = $metadata->getTableColumnType($columnName);
                return $this->buildArgumentCondition($columnName, $columnType, $argument->relation, $argument->value);
        }
    }
}
