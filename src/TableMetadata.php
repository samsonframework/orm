<?php declare(strict_types=1);
namespace samsonframework\orm;

/**
 * Base table metadata class.
 *
 * @author Vitaly Iegorov <egorov@samsonos.com>
 */
class TableMetadata
{
    /** @var string Database table name */
    public $tableName;

    /** @var array Collection of database table columns */
    public $columns;

    /** @var array Collection of database table columns types */
    public $columnTypes;

    /** @var array Collection of database table columns aliases */
    public $columnAliases;

    /** @var string Database table primary field */
    public $primaryField;

    /** @var array Collection of database UNIQUE table columns */
    public $uniqueColumns;

    /** @var array Collection of database INDEXED table columns */
    public $indexColumns;
}
