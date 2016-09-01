<?php declare(strict_types = 1);
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>.
 * on 06.08.16 at 13:56
 */
namespace samsonframework\orm\tests;

use samsonframework\orm\TableMetadata;

class TestCase extends \PHPUnit\Framework\TestCase
{
    /** @var TableMetadata */
    protected $metadata;

    /** @var TableMetadata[] */
    protected $joinedMetadata;

    public function setUp()
    {
        $this->metadata = new TableMetadata();
        $this->metadata->tableName = 'testTable';
        $this->metadata->primaryField = 'primary';
        $this->metadata->className = TestEntity::class;
        $this->metadata->columns['primary'] = 'primary';
        $this->metadata->columns['testColumn'] = 'testColumn';
        $this->metadata->columns['testColumn2'] = 'testColumn2';
        $this->metadata->columns['testColumn3'] = 'testColumn3';
        $this->metadata->columnTypes['testColumn'] = 'int';
        $this->metadata->columnTypes['testColumn2'] = 'varchar(25)';
        $this->metadata->columnTypes['testColumn3'] = 'varchar(25)';

        $this->joinedMetadata = [];
        $this->joinedMetadata[0] = new TableMetadata();
        $this->joinedMetadata[0]->tableName = 'testTable2';
        $this->joinedMetadata[0]->primaryField = 'primary2';
        $this->joinedMetadata[0]->className = JoinTestEntity::class;
        $this->joinedMetadata[0]->columns['primary2'] = 'primary2';
        $this->joinedMetadata[0]->columns['testColumn3'] = 'testColumn3';
        $this->joinedMetadata[0]->columns['testColumn4'] = 'testColumn4';
    }

    /**
     * Get $object private/protected property value.
     *
     * @param string $property Private/protected property name
     *
     * @param object $object   Object instance for getting private/protected property value
     *
     * @return mixed Private/protected property value
     */
    protected function getProperty($property, $object)
    {
        $property = (new \ReflectionClass($object))->getProperty($property);
        $property->setAccessible(true);
        try {
            return $property->getValue($object);
        } catch (\Exception $e) {
            return null;
        }
    }
}