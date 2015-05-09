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
    /**
     * Connect to a database using driver with parameters
     * @param mixed $driver Database driver for interaction
     * @param string $username Database username
     * @param string $password Database password
     * @param string $host Database host(localhost by default)
     * @param int $port Database port(3306 by default)
     * @return bool True if connection to database was successful
     */
    public function connect($driver, $username, $password, $host = 'localhost', $port = 3306);
}