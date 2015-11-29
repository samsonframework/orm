<?php
/**
 * Created by PhpStorm.
 * User: onysko
 * Date: 22.05.2015
 * Time: 17:13
 */
namespace samsonframework\orm;


class PDO
{
    protected $driver;

    /** Proxy all calls to a driver */
    public function __call($method, $arguments)
    {
        return call_user_func_array(array($this->driver, $method), $arguments);
    }

    public function __construct($host, $database, $username, $password, $charset, $port = 3306, $driver = 'MySQL')
    {
        // Create connection string
        $dsn = $driver . ':host=' . $host . ';port=' . $port . ';dbname=' . $database . ';charset=' . $charset;

        // Set options
        $opt = array(
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
        );

        try { // Connect to a database
            // Check if configured database exists
            $this->driver = new \PDO($dsn, $username, $password, $opt);
        } catch (\PDOException $e) {
            // TODO: Use logger interface
            // Handle exception
            die(__NAMESPACE__.' error:'.$e->getMessage());
        }
    }
}