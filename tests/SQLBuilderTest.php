<?php declare(strict_types = 1);
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>.
 * on 29.08.16 at 17:35
 */
namespace samsonframework\orm\tests;

use PHPUnit\Framework\TestCase;
use samsonframework\orm\SQLBuilder;
use samsonframework\orm\TableMetadata;

/**
 * Class SQLBuilderTest
 *
 * @author Vitaly Egorov <egorov@samsonos.com>
 */
class SQLBuilderTest extends TestCase
{
    /** @var SQLBuilder */
    protected $sqlBuilder;

    /** @var TableMetadata */
    protected $metadata;

    /** @var TableMetadata[] */
    protected $joinedMetadata;

    public function setUp()
    {
        $this->sqlBuilder = new SQLBuilder();

        $this->metadata = new TableMetadata();
        $this->metadata->tableName = 'testTable';
        $this->metadata->className = TestEntity::class;
        $this->metadata->columns[] = 'testColumn';
        $this->metadata->columns[] = 'testColumn2';

        $this->joinedMetadata = [];
        $this->joinedMetadata[0] = new TableMetadata();
        $this->joinedMetadata[0]->tableName = 'testTable2';
        $this->joinedMetadata[0]->className = JoinTestEntity::class;
        $this->joinedMetadata[0]->columns[] = 'testColumn3';
        $this->joinedMetadata[0]->columns[] = 'testColumn4';
    }

    public function testBuildSelectStatement()
    {
        static::assertEquals(
            'SELECT `testTable`.`testColumn`, `testTable`.`testColumn2`',
            $this->sqlBuilder->buildSelectStatement($this->metadata)
        );
    }

    public function testBuildSelectStatementWithJoins()
    {
        static::assertEquals(
            'SELECT `testTable`.`testColumn`, `testTable`.`testColumn2`'.
            "\n".',`testTable2`.`testColumn3`, `testTable2`.`testColumn4`',
            $this->sqlBuilder->buildSelectStatement($this->metadata, $this->joinedMetadata)
        );
    }

    public function testBuildFromStatement()
    {
        static::assertEquals(
            'FROM `testTable`',
            $this->sqlBuilder->buildFromStatement($this->metadata)
        );
    }

    public function testBuildFromStatementWithJoins()
    {
        static::assertEquals(
            'FROM `testTable`'.
            "\n".',`testTable2`',
            $this->sqlBuilder->buildFromStatement($this->metadata, $this->joinedMetadata)
        );
    }

    public function testBuildGroupStatement()
    {
        static::assertEquals(
            'GROUP BY `testTable`.testColumn, `testTable2`.testColumn3',
            $this->sqlBuilder->buildGroupStatement(
                array_merge([$this->metadata], $this->joinedMetadata),
                ['testColumn', 'testColumn3']
            )
        );
    }

    public function testBuildGroupStatementWithException()
    {
        $this->expectException(\InvalidArgumentException::class);

        static::assertEquals(
            'GROUP BY `testTable`.testColumn, `testTable2`.testColumn3',
            $this->sqlBuilder->buildGroupStatement(
                array_merge([$this->metadata], $this->joinedMetadata),
                ['testColumn99', 'testColumn98']
            )
        );
    }

    public function testBuildOrderStatement()
    {
        static::assertEquals(
            'ORDER BY `testTable`.testColumn DESC, `testTable2`.testColumn3 ASC',
            $this->sqlBuilder->buildOrderStatement(
                array_merge([$this->metadata], $this->joinedMetadata),
                ['testColumn', 'testColumn3'],
                ['DESC']
            )
        );
    }
}
