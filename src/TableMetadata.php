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

    /** @var array Collection of database table columns aliases to real column names */
    public $columnAliases = [];

    /** @var array Collection of lower case table column aliases to real column names */
    public $lowerColumnAliases = [];

    /** @var string Database table primary field */
    public $primaryField;

    /** @var array Collection of database UNIQUE table columns */
    public $uniqueColumns = [];

    /** @var array Collection of database INDEXED table columns */
    public $indexColumns = [];

    /** @var array Collection of database table columns default values */
    public $columnDefaults = [];

    /** @var array Collection of database table columns is nullable status */
    public $columnNullable = [];

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
            $metadata->columnTypes = $queryClassName::$fieldDataTypes;
            $metadata->columnDefaults = $queryClassName::$fieldDefaults;

            // Fill in nullables
            foreach ($queryClassName::$fieldNullable as $columnName => $nullable) {
                $metadata->columnNullable[$columnName] = $nullable === 'YES';
            }

            // Store lower case aliases
            foreach ($metadata->columnAliases as $alias => $name) {
                $metadata->lowerColumnAliases[strtolower($alias)] = $name;
            }

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
     * Get table column default value by column name or alias.
     *
     * @param string $columnNameOrAlias Table column name or alias
     *
     * @return mixed Table column default value
     * @throws \InvalidArgumentException
     */
    public function getColumnDefaultValue(string $columnNameOrAlias)
    {
        $columnName = $this->getTableColumnName($columnNameOrAlias);

        if (array_key_exists($columnName, $this->columnDefaults)) {
            return $this->columnDefaults[$columnName];
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
        // Case insensitive search
        if ($this->isColumnAliasExists($columnNameOrAlias)) {
            return $this->lowerColumnAliases[strtolower($columnNameOrAlias)];
        }

        // Search real column names
        if ($this->isColumnNameExists($columnNameOrAlias)) {
            return $columnNameOrAlias;
        }

        throw new \InvalidArgumentException(
            'Column ' . $columnNameOrAlias . ' not found in table ' . $this->tableName
        );
    }

    /**
     * Get table column alias by column name or alias.
     *
     * @param string $columnNameOrAlias Table column name or alias
     *
     * @return string Table column alias
     * @throws \InvalidArgumentException
     */
    public function getTableColumnAlias(string $columnNameOrAlias) : string
    {
        // Case insensitive search
        if ($this->isColumnAliasExists($columnNameOrAlias)) {
            $columnName = $this->lowerColumnAliases[strtolower($columnNameOrAlias)];
            return array_flip($this->columnAliases)[$columnName];
        }

        // Search real column names
        if ($this->isColumnNameExists($columnNameOrAlias)) {
            return array_flip($this->columnAliases)[$columnNameOrAlias];
        }

        throw new \InvalidArgumentException(
            'Column ' . $columnNameOrAlias . ' not found in table ' . $this->tableName
        );
    }

    /**
     * Get table primary field name.
     *
     * @return string Table primary field name
     * @throws \InvalidArgumentException
     */
    public function getTablePrimaryField(): string
    {
        return $this->getTableColumnName($this->primaryField);
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

    /**
     * Define if passed column name or alias exists.
     *
     * @param string $columnNameOrAlias Table column name or alias
     * @return bool True if passed column name or alias exists
     */
    public function isColumnExists(string $columnNameOrAlias): bool
    {
        return $this->isColumnAliasExists($columnNameOrAlias) || $this->isColumnNameExists($columnNameOrAlias);
    }

    /**
     * Is column alias exists using case insensitive search.
     *
     * @param string $columnAlias Column name alias
     * @return bool True if column alias exists otherwise false
     */
    protected function isColumnAliasExists(string $columnAlias): bool
    {
//        return array_key_exists($columnAlias, $this->columnAliases);
        return array_key_exists(strtolower($columnAlias), $this->lowerColumnAliases);
    }

    /**
     * Is column name exists.
     *
     * @param string $columnName Column name
     * @return bool True if column name exists otherwise false
     */
    protected function isColumnNameExists(string $columnName): bool
    {
        return in_array($columnName, $this->columns, true);
    }

    /**
     * Is column nullable.
     *
     * @param string $columnNameOrAlias Column name or alias
     * @return bool True if column is nullable otherwise false
     * @throws \InvalidArgumentException
     */
    public function isColumnNullable(string $columnNameOrAlias): bool
    {
        return $this->columnNullable[$this->getTableColumnName($columnNameOrAlias)];
    }
}
