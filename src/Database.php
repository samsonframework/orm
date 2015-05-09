<?php
/**
 * Created by PhpStorm.
 * User: egorov
 * Date: 09.05.2015
 * Time: 13:05
 */
namespace samsonframework\orm;

/**
 * Class Database
 * @package samsonframework\orm
 */
class Database implements DatabaseInterface
{
    /** @var resource Database driver */
    protected $driver;

    /** @var int Amount of miliseconds spent on queries */
    protected $elapsed;

    /** @var int Amount queries executed */
    protected $count;


    /** {@inheritdoc} */
    public function connect(
        $database,
        $username,
        $password,
        $host = 'localhost',
        $port = 3306,
        $driver = 'mysql',
        $charset = 'utf-8'
    ) {
        // If we have not connected yet
        if (!isset($this->driver)) {

            // Create connection string
            $dsn = $driver . ':host=' . $host . ';dbname=' . $database . ';charset=' . $charset;

            // Set options
            $opt = array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            );

            try { // Connect to a database
                $this->driver = new PDO($dsn, $username, $password, $opt);
            } catch (PDOException $e) {
                // Handle exception
            }
        }
    }

    /** {@inheritdoc} */
    public function & query($sql)
    {
        // Store timestamp
        $tsLast = microtime(true);

        // Perform database query
        $result = $pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);

        // Store queries count
        $this->count++;

        // Отметим затраченное время на выполнение запроса
        $this->elapsed += microtime(true) - $tsLast;

        return $result;
    }

    /** Destructor */
    public function __destruct()
    {
        try {
            unset($this->driver);
        } catch (Exception $e) {
            // Handle disconnection error
        }
    }

}
