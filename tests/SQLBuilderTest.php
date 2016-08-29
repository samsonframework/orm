<?php declare(strict_types = 1);
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>.
 * on 29.08.16 at 17:35
 */
namespace samsonframework\orm\tests;

use PHPUnit\Framework\TestCase;
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
        $this->sqlBuilder = new SQLBuilder();
    }

    public function testBuildSelectStatement()
    {

    }
}
