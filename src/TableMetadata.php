<?php declare(strict_types=1);
namespace samsonframework\orm;

/**
 * Base table value class.
 *
 * @author Vitaly Iegorov <egorov@samsonos.com>
 */
class TableMetadata
{
    /** @var string Database table name */
    public $tableName;

    /** @var string Entity class name */
    public $className;

    /** @var array Collection of database table columns */
    public $columns = [];

    /** @var array Collection of database table columns types */
    public $columnTypes = [];

    /** @var array Collection of database table columns aliases */
    public $columnAliases = [];

    /** @var string Database table primary field */
    public $primaryField;

    /** @var array Collection of database UNIQUE table columns */
    public $uniqueColumns = [];

    /** @var array Collection of database INDEXED table columns */
    public $indexColumns = [];

    /**
     * Create metadata instance from entity class name
     * @param string $className Entity class name
     * @deprecated  This is temporary old approach
     * @return TableMetadata Metadata instance
     *
     * @throws \InvalidArgumentException If entity class not found
     */
    public static function fromClassName(string $className) : TableMetadata
    {
        $queryClassName = $className . 'Query';
        if (class_exists($queryClassName)) {
            $metadata = new TableMetadata();
            $metadata->primaryField = $queryClassName::$primaryFieldName;
            $metadata->className = $queryClassName::$identifier;
            $metadata->columnAliases = $queryClassName::$fieldNames;
            $metadata->columns = array_values($queryClassName::$fieldNames);
            $metadata->tableName = $queryClassName::$tableName;
            $metadata->columnTypes = $queryClassName::$fieldTypes;

            return $metadata;
        }

        throw new \InvalidArgumentException('Cannot create metadata for entity ' . $className);
    }

    /**
     * Get table column type by column name or alias.
     *
     * @param string $columnNameOrAlias Table column name or alias
     *
     * @return string Table column type
     * @throws \InvalidArgumentException
     */
    public function getTableColumnType(string $columnNameOrAlias) : string
    {
        $columnName = $this->getTableColumnName($columnNameOrAlias);

        if (array_key_exists($columnName, $this->columnTypes)) {
            return $this->columnTypes[$columnName];
        }

        throw new \InvalidArgumentException(
            'Column ' . $columnNameOrAlias . ' type is not defined table ' . $this->tableName
        );
    }

    /**
     * Get table column name by column name or alias.
     *
     * @param string $columnNameOrAlias Table column name or alias
     *
     * @return string Table column name
     * @throws \InvalidArgumentException
     */
    public function getTableColumnName(string $columnNameOrAlias) : string
    {
        if (in_array($columnNameOrAlias, $this->columns, true)) {
            return $columnNameOrAlias;
        }

        if (in_array($columnNameOrAlias, $this->columnAliases, true)) {
            return $this->columnAliases[$columnNameOrAlias];
        }

        throw new \InvalidArgumentException(
            'Column ' . $columnNameOrAlias . ' not found in table ' . $this->tableName
        );
    }

    /**
     * Get table column index by column name or alias.
     *
     * @param string $columnNameOrAlias Table column name or alias
     *
     * @return int Table column index
     * @throws \InvalidArgumentException
     */
    public function getTableColumnIndex(string $columnNameOrAlias) : int
    {
        return array_search($this->getTableColumnName($columnNameOrAlias), $this->columns, true);
    }
}
