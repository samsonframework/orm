<?php declare(strict_types = 1);
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>.
 * on 29.08.16 at 10:01
 */
namespace samsonframework\orm\tests;

use PHPUnit\Framework\TestCase;
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

    public function setUp()
    {
        $this->database = new \samsonframework\orm\Database();
    }

    public function testConnect()
    {

    }
}
