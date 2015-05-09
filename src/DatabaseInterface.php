<?php
/**
 * Created by PhpStorm.
 * User: egorov
 * Date: 09.05.2015
 * Time: 12:38
 */
namespace samsonframework\orm;

/**
 * Database interaction interface
 * @package samsonframework\orm
 * @author Vitaly Iegorov <egorov@samsonos.com>
 * @author Kotenko Nikita <kotenko@samsonos.com>
 */
interface DatabaseInterface
{
    public function connect($driver, $username, $password, $host = 'localhost', $port = 3306);
}