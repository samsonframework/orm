<?php declare(strict_types = 1);
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>.
 * on 29.08.16 at 10:01
 */
namespace samsonframework\orm\tests;

use samsonframework\orm\Database;
use samsonframework\orm\DatabaseInterface;
use samsonframework\orm\SQLBuilder;

/**
 * Class DatabaseTest.
 *
 * @author Vitaly Egorov <egorov@samsonos.com>
 */
class DatabaseTest extends TestCase
{
    /** @var DatabaseInterface */
    protected $database;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $driver;

    public function setUp()
    {
        parent::setUp();

        $this->driver = $this->createMock(\PDO::class);

        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('execute')->willReturn(true);

        $this->driver->method('prepare')->willReturn($stmt);

        $this->database = new Database($this->driver, new SQLBuilder());
    }

    public function testExecute()
    {
        $this->database->execute('SELECT * FROM `table`');
    }

    public function testFetchColumns()
    {
        $data = ['test1', 'test2'];

        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('fetchAll')->willReturn($data);

        $this->driver->method('query')->willReturn($stmt);

        static::assertEquals($data, $this->database->fetchColumn('SELECT column1, column2 FROM `table`', 1));
    }

    public function testFetchObjects()
    {
        $testEntity = new TestEntity($this->database, $this->metadata);
        $testEntity->primary = 1;
        $testEntity->testField = 1;

        $testEntity2 = new TestEntity($this->database, $this->metadata);
        $testEntity2->primary = 2;
        $testEntity2->testField = 2;

        $data = [$testEntity, $testEntity2];

        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('fetchAll')->willReturn($data);

        $this->driver->method('query')->willReturn($stmt);

        $objects = $this->database->fetchObjects(
            'SELECT column1, column2 FROM `table`',
            TestEntity::class,
            $this->metadata->primaryField
        );

        // Always return array
        static::assertTrue(is_array($objects));
        // Check that resulting array is grouped by primary keys
        static::assertArrayHasKey(1, $objects);
        static::assertArrayHasKey(2, $objects);
        static::assertArrayNotHasKey(99, $objects);
        // Check returned type
        static::assertInstanceOf(TestEntity::class, $objects[1]);
        static::assertInstanceOf(TestEntity::class, $objects[2]);
    }

    public function testFetchArray()
    {
        $this->prepareFetchArray([
            ['primary'=>1, 'testField' => 'test1'],
            ['primary'=>2, 'testField' => 'test2']
        ]);

        $objects = $this->database->fetchArray('SELECT column1, column2 FROM `table`');

        static::assertArrayHasKey('primary', $objects[0]);
        static::assertArrayHasKey('primary', $objects[1]);
        static::assertEquals(1, $objects[0]['primary']);
        static::assertEquals(2, $objects[1]['primary']);
    }

    protected function prepareFetchArray(array $data)
    {
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('fetchAll')->willReturn($data);

        $this->driver->method('query')->willReturn($stmt);
    }

    public function testCount()
    {
        $this->prepareFetchArray([['__Count'=>1]]);

        static::assertEquals(1, $this->database->count('SELECT column1, column2 FROM `table`'));
    }

    public function testCountEmpty()
    {
        $this->prepareFetchArray([]);

        static::assertEquals(0, $this->database->count('SELECT column1, column2 FROM `table`'));
    }

    public function testFetchObjectsWithJoinException()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->prepareFetchObjects([
            ['primary' => 1, 'testField' => 'test1'],
            ['primary' => 2, 'testField' => 'test2']
        ]);
    }

    protected function prepareFetchObjects(array $data)
    {
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('fetchAll')->willReturn($data);

        $this->driver->method('query')->willReturn($stmt);

        return $this->database->fetchObjectsWithJoin(
            'SELECT column1, column2 FROM `table`',
            $this->metadata,
            $this->joinedMetadata
        );
    }

    public function testFetchObjectsWithJoin()
    {
        $objects = $this->prepareFetchObjects([
            ['primary' => 1, 'testColumn' => 'test1', 'primary2' => 22, 'testColumn3' => 'test2'],
            ['primary' => 2, 'testColumn' => 'test2', 'primary2' => 23, 'testColumn3' => 'test3']
        ]);

        $testEntity = $objects[1];
        static::assertInstanceOf(TestEntity::class, $testEntity);
        static::assertArrayHasKey(JoinTestEntity::class, $testEntity->joined);

        $joinedEntities = $testEntity->joined[JoinTestEntity::class];

        // Checked joined entities grouped by their id
        static::assertArrayHasKey(22, $joinedEntities);
        // Check joined instance
        static::assertInstanceOf(JoinTestEntity::class, $joinedEntities[22]);
    }
}
