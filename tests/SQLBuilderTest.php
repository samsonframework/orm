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

    public function setUp()
    {
        $this->sqlBuilder = new SQLBuilder();
    }

    public function testBuildSelectStatement()
    {
        $metadata = new TableMetadata();
        $metadata->tableName = 'testTable';
        $metadata->className = TestEntity::class;
        $metadata->columns[] = 'testColumn';
        $metadata->columns[] = 'testColumn2';

        static::assertEquals(
            'SELECT `testTable`.`testColumn`, `testTable`.`testColumn2`',
            $this->sqlBuilder->buildSelectStatement($metadata)
        );
    }

    public function testBuildSelectStatementWithJoins()
    {
        $metadata = new TableMetadata();
        $metadata->tableName = 'testTable';
        $metadata->className = TestEntity::class;
        $metadata->columns[] = 'testColumn';
        $metadata->columns[] = 'testColumn2';

        $joinedMetadata = [];
        $joinedMetadata[0] = new TableMetadata();
        $joinedMetadata[0]->tableName = 'testTable2';
        $joinedMetadata[0]->className = JoinTestEntity::class;
        $joinedMetadata[0]->columns[] = 'testColumn3';
        $joinedMetadata[0]->columns[] = 'testColumn4';

        static::assertEquals(
            'SELECT `testTable`.`testColumn`, `testTable`.`testColumn2`'.
            "\n".'`testTable2`.`testColumn3`, `testTable2`.`testColumn4`',
            $this->sqlBuilder->buildSelectStatement($metadata, $joinedMetadata)
        );
    }
}
