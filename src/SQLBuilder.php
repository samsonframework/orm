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
     * @param ConditionInterface $condition
     *
     * @return string Limitation statement
     *
     */
    public function buildWhereStatement(TableMetadata $metadata, ConditionInterface $condition) : string
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

    protected function buildArgumentCondition(string $columnName, string $columnType, string $relation, $value)
    {
        if (is_array($value)) {
            // Generate list of values, integer type optimization
            $arrayValue = $columnType === 'int'
                ? 'IN (' . implode(',', $value) . ')'
                : 'IN ("' . implode('","', $value) . '")';

            if ($relation === ArgumentInterface::NOT_EQUAL) {
                $arrayValue = 'NOT '.$arrayValue;
            }

            return $this->buildCondition($columnName, $arrayValue);
        } else { // Regular condition
            return $this->buildCondition(
                $columnName,
                $relation,
                ($columnType === 'int' ? (string)$value : '"'.$value .'"')
            );
        }
    }


    /**
     * "Правильно" разпознать переданный аргумент условия запроса к БД
     *
     * @param string   $class_name Схема сущности БД для которой данные условия
     * @param Argument $argument   Аругемнт условия для преобразования
     *
     * @return string Возвращает разпознанную строку с условием для MySQL
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
