<?php declare(strict_types = 1);
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>.
 * on 29.08.16 at 23:48
 */
namespace samsonframework\orm\tests;

use samsonframework\orm\Database;
use samsonframework\orm\Query;
use samsonframework\orm\SQLBuilder;
use samsonframework\orm\TableMetadata;

/**
 * Class QueryTest
 *
 * @author Vitaly Egorov <egorov@samsonos.com>
 */
class QueryTest extends TestCase
{
    /** @var Query */
    protected $query;

    public function setUp()
    {
        $this->query = new Query($this->createMock(Database::class), new SQLBuilder());
    }

    public function testEntity()
    {
        $tableMetadata = new TableMetadata();
        $this->query->entity($tableMetadata);

        static::assertEquals($tableMetadata, $this->getProperty('metadata', $this->query));
    }

    public function testSelect()
    {
        $this->query->select('testTable', 'testColumn');

        static::assertEquals(['testTable' => ['testColumn']], $this->getProperty('select', $this->query));
    }

    public function testOrderBy()
    {
        $this->query->orderBy('testTable', 'testColumn', 'DESC');

        static::assertEquals(['testTable' => [['testColumn', 'DESC']]], $this->getProperty('sorting', $this->query));
    }

    public function testGroupBy()
    {
        $this->query->groupBy('testTable', 'testColumn');

        static::assertEquals(['testTable' => ['testColumn']], $this->getProperty('grouping', $this->query));
    }

    public function testLimit()
    {
        $this->query->limit(5, 2);

        static::assertEquals([5, 2], $this->getProperty('limitation', $this->query));
    }

    public function testJoin()
    {
        $this->query->join('testJoin');

        static::assertEquals(['testJoin' => []], $this->getProperty('joins', $this->query));
    }
}
