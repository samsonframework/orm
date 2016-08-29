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
class QueryToSQL
{
    /**
     * Get selected fields select statement.
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

    protected function buildGroupStatement(array $columnNames) : string
    {
        return 'GROUP BY ' . implode(', ', $columnNames);
    }

    protected function buildOrderStatement(array $columnName, string $order = 'ASC') : string
    {
        return 'ORDER BY ' . $columnName . ' ' . $order;
    }

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
    protected function prepareSQL(QueryInterface $query)
    {
        $metadata = new TableMetadata();

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

    protected function buildWhereStatement(ConditionInterface $condition, $className) : string
    {
        $conditions = [];

        foreach ($condition as $argument) {
            if ($argument instanceof ConditionInterface) {
                $conditions[] = $this->buildWhereStatement($argument, $className);
            } else {
                $conditions[] = $this->parseCondition($className, $argument);
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
*@return string Возвращает разпознанную строку с условием для MySQL
     */
    protected function parseCondition(TableMetadata $metadata, Argument $argument)
    {

        // Получим "правильное" имя аттрибута сущности и выделим постоянную часть условия
        $sql = $metadata->getTableColumnName($argument->field);

        // Если аргумент условия - это НЕ массив - оптимизации по более частому условию
        if (!is_array($argument->value)) {
            // NULL condition
            if ($argument->relation === ArgumentInterface::NOTNULL || $argument->relation === ArgumentInterface::ISNULL) {
                return $sql . $argument->relation;
            } // Own condition
            else {
                if ($argument->relation === ArgumentInterface::OWN) {
                    return $argument->field;
                } // Regular condition
                else {
                    return $sql . $argument->relation . $this->protectQueryValue($argument->value);
                }
            }
        } // Если аргумент условия - это массив и в нем есть значения
        else {
            if (count($argument->value)) {
                // TODO: Add other numeric types support
                // TODO: Get types of joined tables fields

                // Generate list of values, integer type optimization
                $sql_values = isset($class_name::$_types[$argument->field]) && $class_name::$_types[$argument->field] == 'int'
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
