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
        // Если аргумент условия - это НЕ массив - оптимизации по более частому условию
        if ($argument->relation === ArgumentInterface::OWN) {
            return $argument->field;
        } elseif (!is_array($argument->value)) {
            $columnName = $metadata->getTableColumnName($argument->field);
            $columnType = $metadata->getTableColumnType($columnName);
            $sql = $columnName;

            if (in_array($argument->relation, [ArgumentInterface::NOTNULL, ArgumentInterface::ISNULL], true)) {
                return $sql . $argument->relation;
            } else {
                return $sql . $argument->relation . ($columnType === 'int' ? $argument->value : '"'.$argument->value .'"');
            }
        } else {
            $columnName = $metadata->getTableColumnName($argument->field);
            $columnType = $metadata->getTableColumnType($columnName);
            $sql = $columnName;
            
            if (count($argument->value)) {
                // TODO: Add other numeric types support
                // TODO: Get types of joined tables fields

                // Generate list of values, integer type optimization
                $sql_values = $columnType === 'int'
                    ? ' IN (' . implode(',', $argument->value) . ')'
                    : ' IN ("' . implode('","', $argument->value) . '")';

                switch ($argument->relation) {
                    case ArgumentInterface::EQUAL:
                        return $sql . $sql_values;
                    case ArgumentInterface::NOT_EQUAL:
                        return $sql . ' NOT ' . $sql_values;
                }
            } else { // If we received a condition with empty array - consider this as failing condition
                return '1 = 0';
            }
        }
    }
}
