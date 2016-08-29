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
     * @return string SELECT statement part
     */
    protected function innerBuildSelectStatement(string $tableName, array $selectedFields) : string
    {
        $select = [];
        foreach ($selectedFields as $field) {
            $select[] = '`' . $tableName . '`.`'.$field.'`';
        }

        return implode(', ', $select);
    }

    /**
     * Build selected fields SELECT statement part.
     *
     * @param TableMetadata $metadata
     * @param TableMetadata[] $joinedMetadata
     * @return string SELECT statement
     */
    public function buildSelectStatement(TableMetadata $metadata, array $joinedMetadata = []) : string
    {
        $sql = 'SELECT '.$this->innerBuildSelectStatement($metadata->tableName, $metadata->columns);

        foreach ($joinedMetadata as $joinMetadata) {
            $sql .= "\n". ','.$this->innerBuildSelectStatement($joinMetadata->tableName, $joinMetadata->columns);
        }

        return $sql;
    }

    /**
     * Build FROM statement part.
     *
     * @param TableMetadata $metadata
     * @param TableMetadata[] $joinedMetadata
     * @return string FROM statement
     */
    public function buildFromStatement(TableMetadata $metadata, array $joinedMetadata = []) : string
    {
        $sql = 'FROM `'.$metadata->tableName.'`';

        foreach ($joinedMetadata as $joinMetadata) {
            $sql .= "\n". ',`'.$joinMetadata->tableName.'`';
        }

        return $sql;
    }

    /**
     * Try to build full column names with their tables
     * from passed collections.
     *
     * @param array $tablesMetadata Table metadata collection
     * @param array $columnNames Table column names
     *
     * @return array Collection of full column names for query
     *
     * @throws \InvalidArgumentException If at least one passed column not found in passed metadata
     */
    protected function buildFullColumnNames(array $tablesMetadata, array $columnNames) : array
    {
        $grouping = [];
        foreach ($tablesMetadata as $metadata) {
            foreach ($columnNames as $columnName) {
                try {
                    $grouping[] = '`'.$metadata->tableName.'`.'.$metadata->getTableColumnName($columnName);
                } catch (\InvalidArgumentException $e) {
                    // Do nothing
                }
            }
        }

        // Valid results
        if (count($grouping) !== count($columnNames)) {
            throw new \InvalidArgumentException('Cannot group by specified columns');
        }

        return $grouping;
    }

    /**
     * Build grouping statement.
     *
     * @param TableMetadata[] $tablesMetadata
     * @param array           $columnNames Column names collection
     *
     * @return string Grouping statement
     *
     * @throws \InvalidArgumentException If at least one passed column not found in passed metadata
     */
    public function buildGroupStatement(array $tablesMetadata, array $columnNames) : string
    {
        return 'GROUP BY ' . implode(', ', $this->buildFullColumnNames($tablesMetadata, $columnNames));
    }

    /**
     * Build ordering statement.
     *
     * @param TableMetadata[] $tablesMetadata
     * @param array           $columnNames Column names collection
     * @param array $orders Collection of columns sorting order
     *
     * @return string Ordering statement
     * @throws \InvalidArgumentException
     */
    public function buildOrderStatement(array $tablesMetadata, array $columnNames, array $orders) : string
    {
        $ordering = [];
        $i = 0;
        foreach ($this->buildFullColumnNames($tablesMetadata, $columnNames) as $columnName) {
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
     * @param ConditionInterface $condition
     * @param TableMetadata      $metadata
     *
     * @return string Limitation statement
     *
     */
    public function buildWhereStatement(ConditionInterface $condition, TableMetadata $metadata) : string
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
