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
     * Build selected fields SELECT statement part.
     *
     * @param string $tableName
     * @param array  $selectedFields
     *
     * @return string
     */
    protected function buildSelectStatement(string $tableName, array $selectedFields) : string
    {
        $select = [];
        foreach ($selectedFields as $field) {
            $select[] = '`' . $tableName . '`.`'.$field.'`';
        }

        return implode(', ', $select);
    }

    /**
     * Build grouping statement.
     *
     * @param array $columnNames Column names collection
     *
     * @return string Grouping statement
     */
    protected function buildGroupStatement(array $columnNames) : string
    {
        return 'GROUP BY ' . implode(', ', $columnNames);
    }

    /**
     * Build ordering statement.
     *
     * @param array  $columnName Ordering column name
     * @param string $order Sorting order
     *
     * @return string Ordering statement
     */
    protected function buildOrderStatement(array $columnName, string $order = 'ASC') : string
    {
        return 'ORDER BY ' . $columnName . ' ' . $order;
    }

    /**
     * Build limitation statement.
     *
     * @param int $rows Rows amount for limitation
     * @param int $offset Rows offset
     *
     * @return string Limitation statement
     */
    protected function buildLimitStatement(int $rows, int $offset = 0) : string
    {
        return 'LIMIT ' . $offset . ', ' . $rows;
    }

    /**
     * Create SQL request
     *
     * @param string $class_name Classname for request creating
     * @param QueryInterface $query Query with parameters
     * @return string SQL string
     */
    public function build(QueryInterface $query, TableMetadata $metadata)
    {
        $selectSQL = 'SELECT '."\n".$this->buildSelectStatement($metadata->tableName, $query->selectedFields);

        // Add join table selected fields
        foreach ($query->joins as $joinTableName => $joinFields) {
            $selectSQL .= ', '."\n".$this->buildSelectStatement($joinTableName, $joinFields);
        }

        // Add virtual fields for selection
        if (count($query->virtualFields)) {
            $selectSQL .= ', ' . "\n" . $this->buildSelectStatement($metadata->tableName, $query->virtualFields);
        }

        // From part
        $selectSQL .= "\n" . 'FROM ' . $metadata->tableName;

        // Add join table selected fields
        foreach ($query->joins as $joinTableName => $joinFields) {
            $selectSQL .= 'LEFT JOIN `'.$joinTableName.'` ON ';
        }

        if ($query->own_condition->size()) {
            $selectSQL .= "\n" . ' WHERE (' . $this->buildWhereStatement($query->own_condition, $class_name) . ')';
        }

        if (count($query->own_group)) {
            $selectSQL .= "\n" . $this->buildGroupStatement($query->own_group);
        }

        if (count($query->own_order)) {
            $selectSQL .= "\n" . $this->buildOrderStatement($query->own_order[0], $query->own_order[1]);
        }

        if (count($query->own_limit)) {
            $selectSQL .= "\n" . $this->buildLimitStatement($query->own_limit[0], $query->own_limit[1]);
        }

        return $selectSQL;
    }

    protected function buildWhereStatement(ConditionInterface $condition, TableMetadata $metadata) : string
    {
        $conditions = [];

        foreach ($condition as $argument) {
            if ($argument instanceof ConditionInterface) {
                $conditions[] = $this->buildWhereStatement($argument, $metadata);
            } else {
                $conditions[] = $this->parseCondition($argument, $metadata);
            }
        }

        // Соберем все условия условной группы в строку
        if (count($conditions)) {
            return '(' . implode(') ' . $condition->relation . ' (', $conditions) . ')';
        } else {
            return '';
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
        $columnName = $metadata->getTableColumnName($argument->field);
        $columnType = $metadata->getTableColumnType($columnName);
        $sql = $columnName;

        // Если аргумент условия - это НЕ массив - оптимизации по более частому условию
        if (!is_array($argument->value)) {
            if (in_array($argument->relation, [ArgumentInterface::NOTNULL, ArgumentInterface::ISNULL], true)) {
                return $sql . $argument->relation;
            } elseif ($argument->relation === ArgumentInterface::OWN) {
                return $argument->field;
            } else {
                return $sql . $argument->relation . ($columnType === 'int' ? $argument->value : '"'.$argument->value .'"');
            }
        } // Если аргумент условия - это массив и в нем есть значения
        else {
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
