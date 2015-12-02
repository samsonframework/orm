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
    /** @var Database Database manager for queries */
    protected $database;

    protected function getFields()
    {

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
}
