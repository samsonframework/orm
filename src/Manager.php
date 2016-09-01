<?php declare(strict_types = 1);
/**
 * Created by PhpStorm.
 * User: VITALYIEGOROV
 * Date: 30.11.15
 * Time: 16:38
 */
namespace samsonframework\orm;

/**
 * Database entity manager.
 *
 * @author Vitaly Egorov <egorov@samsonos.com>
 */
class Manager
{
    /** @var string Entity identifier */
    protected $entityName;

    /** @var string Entity primary field name */
    protected $primaryFieldName;

    /** @var array Collection of entity fields that could be used in queries */
    protected $queryFields = array();

    /** @var array Collection of entity field names and their types */
    protected $fieldsAndTypes = array();

    /** @var DatabaseInterface Database manager */
    protected $database;

    /**
     * Manager constructor.
     *
     * @param DatabaseInterface $database   database low-level driver
     * @param string            $entityName Entity name
     * @param array             $attributes Key-value collection with field name => type
     */
    public function __construct(DatabaseInterface $database, string $entityName, $attributes)
    {
        $this->database = $database;
        $this->entityName = $entityName;
        $this->fieldsAndTypes = $attributes;
    }

    /**
     * Store entity into database.
     *
     * @param mixed $entity Database entity
     */
    public function save($entity)
    {
        $fields = $this->getFields($entity);
//
//        $this->execute('INSERT INTO `' . $this->entityName . '` (`'
//            . implode('`,`', array_keys($fields)) . '`) VALUES (' . implode(',', $fields) . ')'
//        );

// Generate entity fields update command
//        $fields = array();
//        foreach ($this->getFields($entity) as $fieldName => $fieldValue) {
//            $fields[] = '`'.$this->entityName.'`.`'.$fieldName.'` = "'.$fieldValue.'"';
//        }
//
//        $this->execute('UPDATE `' . $this->entityName . '` SET '
//            . implode(',', $fields)
//            . ' WHERE `' . $this->entityName . '`.`' . $this->primaryFieldName . '`="'
//            . $this->quote($entity->id) . '"');

        //$this->database->
    }

    /**
     * Get new entity instance.
     *
     * @return mixed Entity instance
     */
    public function create()
    {
        return new $this->entityName($this);
    }

    /**
     * Convert RecordInterface instance to collection of its field name => value,
     * returning only fields that needs to participate in SQL statements.
     * TODO: We need to generate this collection in entity class generation.
     *
     * @param RecordInterface $object Database record instance to convert
     * @return array Collection of key => value with SQL fields statements
     */
    protected function &getQueryFields(RecordInterface &$object = null)
    {
        $collection = array();
        foreach ($this->fieldsAndTypes as $attribute => $type) {
            if ($type == 'timestamp') {
                continue;
            } elseif ($this->primaryFieldName == $attribute) {
                continue;
            }

            $collection[$attribute] = $object->$attribute;
        }

        return $collection;
    }

//    /**
//     * Create new database entity record.
//     * @param RecordInterface $entity Entity record for creation
//     * @return RecordInterface Created database entity record with new primary identifier
//     */
//    public function create(RecordInterface $entity)
//    {
//        $fields = $this->getFields($entity);
//
//        $this->execute('INSERT INTO `' . $this->entityName . '` (`'
//            . implode('`,`', array_keys($fields)) . '`) VALUES (' . implode(',', $fields) . ')'
//        );
//    }
//
//    /**
//     * Read database entity records from QueryInterface.
//     *
//     * @param QueryInterface $query For retrieving records
//     * @return RecordInterface[] Collection of read database entity records
//     */
//    public function read(QueryInterface $query)
//    {
//        // TODO: Implement read() method.
//    }
//
//    /**
//     * Update database entity record.
//     *
//     * @param RecordInterface $entity Entity record for updating
//     */
//    public function update(RecordInterface $entity)
//    {
//        // Generate entity fields update command
//        $fields = array();
//        foreach ($this->getFields($entity) as $fieldName => $fieldValue) {
//            $fields[] = '`'.$this->entityName.'`.`'.$fieldName.'` = "'.$fieldValue.'"';
//        }
//
//        $this->execute('UPDATE `' . $this->entityName . '` SET '
//            . implode(',', $fields)
//            . ' WHERE `' . $this->entityName . '`.`' . $this->primaryFieldName . '`="'
//            . $this->quote($entity->id) . '"');
//    }
//
//    /**
//     * Delete database record from database.
//     *
//     * @param RecordInterface $entity Entity record for removing
//     */
//    public function delete(RecordInterface $entity)
//    {
//        $this->execute('DELETE FROM `' . $this->entityName . '` WHERE '
//            . $this->primaryFieldName . ' = "' . $this->quote($entity->id) . '"'
//        );
//    }
}
