<?php
/**
 * Created by PhpStorm.
 * User: onysko
 * Date: 22.05.2015
 * Time: 17:10
 */

namespace samsonframework\orm;


class MySQL
{
    protected $link;

    protected $result;

    public function __construct($host, $database, $username, $password, $charset, $port = 3306, $driver = 'MySQL')
    {
        // Connect to database
        $this->link = mysqli_connect($host, $username, $password, $database) or die('MySQLi error: ' . mysqli_error($this->link));
    }

    public function query($sql)
    {
        // Выполним запрос к БД
        $this->result = mysqli_query($this->link, $sql) or die('MySQLi query error: ' . mysqli_error($this->link));

        return $this;
    }

    public function prepare($sql)
    {
        // Выполним запрос к БД
        $this->result = mysqli_query($this->link, $sql) or die('MySQLi prepare error: ' . mysqli_error($this->link));

        return $this;
    }

    public function quote($value)
    {
        // If magic quotes are on - remove slashes
        if( get_magic_quotes_gpc() ) $value = stripslashes( $value );

        // Normally escape string
        $value = mysqli_real_escape_string($this->link, $value );

        // Return value in quotes for queryCMSMainTest.php
        return '"'.$value.'"';
    }

    public function fetchAll($config, $columnIndex = null)
    {
        $rows = array();

        // Если нам вернулся ресурс
        if (!is_bool($this->result)) {

            // Заполним все результаты
            while ($row = mysqli_fetch_array($this->result, MYSQL_BOTH)) {
                if (isset($columnIndex)) {
                    $rows[] = $row[$columnIndex];
                } else {
                    $rows[] = $row;
                }
            }

            // Очистим память
            mysqli_free_result($this->result);
        }

        return $rows;
    }

    public function fetch()
    {
        if (!is_bool($this->result)) {
            return mysqli_fetch_array($this->result, MYSQL_ASSOC);
        }
    }

    public function execute()
    {
        if (!is_bool($this->result)) {
            return mysqli_fetch_array($this->result, MYSQL_ASSOC);
        }
    }
}