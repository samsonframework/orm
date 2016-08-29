<?php declare(strict_types = 1);
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>.
 * on 29.08.16 at 10:01
 */
namespace samsonframework\orm\tests;

use PHPUnit\Framework\TestCase;
use samsonframework\orm\Database;
use samsonframework\orm\DatabaseInterface;

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
        $this->driver = $this->createMock(\PDO::class);

        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('execute')->willReturn(true);

        $this->driver->method('prepare')->willReturn($stmt);

        $this->database = new Database($this->driver);
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

        static::assertEquals($data, $this->database->fetchColumns('SELECT column1, column2 FROM `table`', 1));
    }

    public function testFetchObjects()
    {
        $data = [
            ['primary'=>1, 'testField' => 'test1'],
            ['primary'=>2, 'testField' => 'test2']
        ];

        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('fetchAll')->willReturn($data);

        $this->driver->method('query')->willReturn($stmt);

        $objects = $this->database->fetchObjects('SELECT column1, column2 FROM `table`', TestEntity::class);

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
}
