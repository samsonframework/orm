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
     * @param string $database Database name
     * @param string $username Database username
     * @param string $password Database password
     * @param string $host Database host(localhost by default)
     * @param int $port Database port(3306 by default)
     * @param string $driver Database driver for interaction(MySQL by default)
     * @param string $charset Database character set
     * @return bool True if connection to database was successful
     */
    public function connect(
        $database,
        $username,
        $password,
        $host = 'localhost',
        $port = 3306,
        $driver = 'mysql',
        $charset = 'utf-8'
    );

    /**
     * High-level database query rows fetcher
     * @param string $sql SQL statement
     * @return array Key-value record set
     */
    public function & fetch($sql);

    /**
     * High-level database query
     * @param string $sql SQL statement
     * @return mixed Query execution result, true if ok
     */
    public function & query($sql);
}
