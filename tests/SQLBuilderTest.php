<?php declare(strict_types = 1);
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>.
 * on 29.08.16 at 17:35
 */
namespace samsonframework\orm\tests;

use samsonframework\orm\ArgumentInterface;
use samsonframework\orm\Condition;
use samsonframework\orm\ConditionInterface;
use samsonframework\orm\SQLBuilder;

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
        parent::setUp();
        
        $this->sqlBuilder = new SQLBuilder();
    }

    public function testBuildSelectStatement()
    {
        static::assertEquals(
            'SELECT `testTable`.`testColumn`, `testTable`.`testColumn2`',
            $this->sqlBuilder->buildSelectStatement(
                ['testTable' => ['testColumn', 'testColumn2']])
        );
    }

    public function testBuildSelectStatementWithJoins()
    {
        static::assertEquals(
            'SELECT `testTable`.`testColumn`, `testTable`.`testColumn2`'.
            ', `testTable2`.`testColumn3`, `testTable2`.`testColumn4`',
            $this->sqlBuilder->buildSelectStatement([
                    'testTable' => ['testColumn', 'testColumn2'],
                    'testTable2' => ['testColumn3', 'testColumn4']
                ]
            )
        );
    }

    public function testBuildFromStatement()
    {
        static::assertEquals(
            'FROM `testTable`',
            $this->sqlBuilder->buildFromStatement(['testTable'])
        );
    }

    public function testBuildFromStatementWithJoins()
    {
        static::assertEquals(
            'FROM `testTable`, `testTable2`',
            $this->sqlBuilder->buildFromStatement(['testTable', 'testTable2'])
        );
    }

    public function testBuildGroupStatement()
    {
        static::assertEquals(
            'GROUP BY `testTable`.`testColumn`, `testTable2`.`testColumn3`',
            $this->sqlBuilder->buildGroupStatement(
                ['testTable' => ['testColumn'], 'testTable2' => ['testColumn3']]
            )
        );
    }

    public function testBuildOrderStatement()
    {
        static::assertEquals(
            'ORDER BY `testTable`.`testColumn` DESC, `testTable2`.`testColumn3` ASC',
            $this->sqlBuilder->buildOrderStatement(
                ['testTable' => 'testColumn', 'testTable2' => 'testColumn3'],
                ['DESC']
            )
        );
    }

    public function testBuildLimitStatement()
    {
        static::assertEquals(
            'LIMIT 0, 5',
            $this->sqlBuilder->buildLimitStatement(5)
        );
    }

    public function testBuildLimitStatementWithOffset()
    {
        static::assertEquals(
            'LIMIT 2, 5',
            $this->sqlBuilder->buildLimitStatement(5, 2)
        );
    }

    public function testBuildWhereStatement()
    {
        $condition = new Condition();
        $condition->add('testColumn', 11);
        $condition2 = new Condition(ConditionInterface::DISJUNCTION);
        $condition2->add('testColumn2', ['test', 'test2', 'test3']);
        $condition2->add('testColumn3', 'test', ArgumentInterface::NOT_EQUAL);
        $condition2->add('testColumn3', 'test', ArgumentInterface::NOTNULL);
        $condition2->add('testColumn3 = "test"', '', ArgumentInterface::OWN);
        $condition2->add('testColumn', [1,2], ArgumentInterface::NOT_EQUAL);
        $condition->addCondition($condition2);

        static::assertEquals(
            '(testColumn = 11) AND ((testColumn2 IN ("test","test2","test3")) OR (testColumn3 != "test") OR (testColumn3 IS NOT NULL) OR (testColumn3 = "test") OR (testColumn NOT IN (1,2)))',
            $this->sqlBuilder->buildWhereStatement($this->metadata, $condition)
        );
    }

    public function testBuildInsertStatement()
    {
        $columnValues = [
            'testColumn' => 1,
            'testColumn2' => 'testValue2',
            'testColumn3' => 'testValue3',
        ];

        static::assertEquals(
            'INSERT INTO `testTable` (`testColumn`, `testColumn2`, `testColumn3`) VALUES ( 1,  "testValue2",  "testValue3")',
            $this->sqlBuilder->buildInsertStatement($this->metadata, $columnValues)
        );
    }
}
