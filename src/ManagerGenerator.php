<?php
/**
 * Created by PhpStorm.
 * User: VITALYIEGOROV
 * Date: 30.11.15
 * Time: 16:38
 */
namespace samsonframework\orm;

/**
 * Database entity manager.
 * @package samsonframework\orm
 */
class ManagerGenerator
{
    /** @var Database Database manager */
    protected $database;

    /** @var string SQL statement for retrieving database tables value */
    protected $metadataSQL = '
      SELECT
      `TABLES`.`TABLE_NAME` as `TABLE_NAME`,
      `COLUMNS`.`COLUMN_NAME` as `Field`,
      `COLUMNS`.`DATA_TYPE` as `Type`,
      `COLUMNS`.`IS_NULLABLE` as `Null`,
      `COLUMNS`.`COLUMN_KEY` as `Key`,
      `COLUMNS`.`COLUMN_DEFAULT` as `Default`,
      `COLUMNS`.`EXTRA` as `Extra`
      FROM `information_schema`.`TABLES` as `TABLES`
      LEFT JOIN `information_schema`.`COLUMNS` as `COLUMNS`
      ON `TABLES`.`TABLE_NAME`=`COLUMNS`.`TABLE_NAME`
      WHERE `TABLES`.`TABLE_SCHEMA`="@database" AND `COLUMNS`.`TABLE_SCHEMA`="@database"
      ';

    /**
     * ManagerGenerator constructor.
     *
     * @param Database $database Database query manager
     */
    public function __construct(Database $database)
    {
        $this->database = $database;
        foreach ($this->metadata() as $entity => $entityMetadata) {
            trace($this->createManager($entity, $entityMetadata), 1);
        }
    }

    /**
     * Create database structure value. This method should return array:
     * $entity => [ $field => [ $field_params[] ].
     *
     * @return array Database structure value
     */
    public function metadata()
    {
        // Insert parameter
        $this->metadataSQL = str_replace('@database', $this->database->database(), $this->metadataSQL);

        /** @var array Collection of database tables and their fields description  */
        $metadata = array();
        // Iterate database value
        foreach ($this->database->fetch($this->metadataSQL) as $tableMetadata) {
            // Gather database in format entity => field => [field_params]
            $metadata[$tableMetadata['TABLE_NAME']][$tableMetadata['Field']] = array_slice($tableMetadata, 2);
        }

        return $metadata;
    }

    public function createManager($entity, array $entityMetadata)
    {
        /** @var string $code Manager class code */
        $code = '';
        if (sizeof($entityMetadata)) {
            $code .= 'namespace '.__NAMESPACE__.';'."\n";
            $code .= "\n".'class '.ucfirst($entity).'Manager extends Manager';
            $code .= "\n"."{"; // Open class
            $code .= "\n"."\t".'public $entityName = "'.$entity.'";';
            $code .= "\n"."\t".$this->createAttributesCollection($entityMetadata);
            $code .= "\n"."}"."\n"; // Close class
        }

        return $code;
    }

    protected function createAttributesCollection(array $entityMetadata, $variableName = 'queryFields')
    {
        $code = 'public $'.$variableName.' = array(';
        foreach ($entityMetadata as $field => $fieldMetadata) {
            // Ignore entity fields that are filled automatically by database
            if ($fieldMetadata['TYPE'] !== 'timestamp' && $fieldMetadata['Key'] != 'PRI') {
                $code .= "\n" . "\t" . "\t" . '"' . $field . '" => "' . $fieldMetadata['Type'] . '",';
            }
        }
        return $code . "\n"."\t".');';
    }
}
