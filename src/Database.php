<?php declare(strict_types=1);
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
    /** Table name prefix */
    public static $prefix = '';

    /** @var \PDO Database driver */
    protected $driver;

    /** @var string Database name */
    protected $database;

    /** @var int Amount of milliseconds spent on queries */
    protected $elapsed;

    /** @var int Amount queries executed */
    protected $count;

    /** Do not serialize anything */
    public function __sleep()
    {
        return array();
    }

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
        $charset = 'utf8'
    ) {
        // If we have not connected yet
        if ($this->driver === null) {
            $this->database = $database;

            // Check if configured database exists
            $this->driver = new PDO($host, $database, $username, $password, $charset, $port, $driver);

            // Set correct encodings
            $this->execute("set character_set_client='utf8'");
            $this->execute("set character_set_results='utf8'");
            $this->execute("set collation_connection='utf8_general_ci'");

            //new ManagerGenerator($this);
        }
    }

    /**
     * Get database name
     * @return string
     * @deprecated
     */
    public function database()
    {
        return $this->database;
    }

    /**
     * High-level database query executor
     * @param string $sql SQL statement
     * @return mixed Database query result
     * @deprecated Use execute()
     */
    public function query($sql)
    {
        return $this->execute($sql);
    }

    /**
     * Internal error beautifier.
     *
     * @param \Exception $exception
     * @param            $sql
     * @param string     $text
     *
     * @throws \Exception
     */
    private function outputError(\Exception $exception, $sql, $text = 'Error executing database query:')
    {
        throw $exception;

        echo("\n" . '<div style="font-size:12px; position:relative; background:red; z-index:9999999;">'
            .'<div style="padding:4px 10px;">'.$text.'</div>'
            .'<div style="padding:0px 10px;">['.htmlspecialchars($exception->getMessage()).']</div>'
            .'<textarea style="display:block; width:100%; min-height:100px;">'.$sql . '</textarea></div>');
    }

    /**
     * Proxy function for executing database fetching logic with exception,
     * error, profile handling
     * @param callback $fetcher Callback for fetching
     * @return mixed Fetching function result
     */
    private function executeFetcher($fetcher, $sql)
    {
        $result = array();

        if (isset($this->driver)) {
            // Store timestamp
            $tsLast = microtime(true);

            try { // Call fetcher
                // Get argument and remove first one
                $args = func_get_args();
                array_shift($args);

                // Proxy calling of fetcher function with passing parameters
                $result = call_user_func_array($fetcher, $args);
            } catch (\PDOException $exception) {
                $this->outputError($exception, $sql, 'Error executing ['.$fetcher[1].']');
            }

            // Store queries count
            $this->count++;

            // Count elapsed time
            $this->elapsed += microtime(true) - $tsLast;
        }

        return $result;
    }

    /**
     * High-level database query executor
     * @param string $sql SQL statement
     * @return mixed Database query result
     */
    private function innerQuery($sql)
    {
        try {
            // Perform database query
            return $this->driver->prepare($sql)->execute();
        } catch (\PDOException $e) {
            $this->outputError($e, $sql);
        }

        return null;
    }

    /**
     * Retrieve array of records from a database, if $className is passed method
     * will try to create an object of that type. If request has failed than
     * method will return empty array of stdClass all arrays regarding to $className is
     * passed or not.
     *
     * @param string $sql SQL statement
     * @return array Collection of arrays or objects
     */
    private function innerFetch($sql, $className = null)
    {
        try {
            // Perform database query
            if (!isset($className)) { // Return array
                return $this->driver->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
            } else { // Create object of passed class name
                return $this->driver->query($sql)->fetchAll(\PDO::FETCH_CLASS, $className, array(&$this));
            }
        } catch (\PDOException $e) {
            $this->outputError($e, $sql, 'Fetching database records:');
        }

        return array();
    }

    /**
     * Special accelerated function to retrieve db record fields instead of objects
     *
     * @param string $sql SQL statement
     * @param int $columnIndex Needed column index
     *
     * @return array Database records column value collection
     */
    private function innerFetchColumn($sql, $columnIndex)
    {
        try {
            // Perform database query
            return $this->driver->query($sql)->fetchAll(\PDO::FETCH_COLUMN, $columnIndex);
        } catch (\PDOException $e) {
            $this->outputError($e, $sql, 'Error fetching records column values:');
        }

        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(string $sql)
    {
        return $this->executeFetcher(array($this, 'innerQuery'), $sql);
    }

    /**
     * {@inheritdoc}
     */
    public function fetch(string $sql)
    {
        return $this->executeFetcher(array($this, 'innerFetch'), $sql);
    }

    /**
     * Special accelerated function to retrieve db record fields instead of objects
     * TODO: Change to be independent of query and class name, just SQL, this SQL
     * should only have one column in SELECT part and then we do not need parameter
     * for this as we can always take 0.
     *
     * @param string $entity Entity identifier
     * @param QueryInterface Query object
     * @param string $field Entity field identifier
     *
     * @return array Collection of rows with field value
     * @deprecated
     */
    public function fetchColumn($entity, QueryInterface $query, $field)
    {
        // TODO: Remove old attributes retrieval

        return $this->executeFetcher(
            array($this, 'innerFetchColumn'),
            $this->prepareSQL($entity, $query),
            array_search($field, array_values($entity::$_table_attributes))
        );
    }

    /**
     * Count resulting rows.
     *
     * @param string Entity identifier
     * @param QueryInterface Query object
     *
     * @return int Amount of rows
     */
    public function count($entity, QueryInterface $query)
    {
        // Modify query SQL and add counting
        $result = $this->fetch('SELECT Count(*) as __Count FROM (' . $this->prepareSQL($entity, $query) . ') as __table');

        return isset($result[0]) ? (int)$result[0]['__Count'] : 0;
    }

    /**
     * Quote variable for security reasons.
     *
     * @param string $value
     * @return string Quoted value
     */
    protected function quote($value)
    {
        return $this->driver->quote($value);
    }

    /**
     * Convert QueryInterface into SQL statement.
     *
     * @param string Entity identifier
     * @param QueryInterface Query object
     *
     * @return string SQL statement
     */
    protected function prepareSQL($entity, QueryInterface $query)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function fetchArray(string $sql) : array
    {
        // TODO: Implement fetchArray() method.
    }

    /**
     * {@inheritdoc}
     */
    public function fetchObjects(string $sql, string $className) : array
    {
        return $this->toRecords(
            $className,
            $this->fetchArray($sql),
            $query->join,
            array_merge($query->own_virtual_fields, $query->virtual_fields)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function fetchColumns(string $sql, int $columnIndex) : array
    {
        return $this->executeFetcher(array($this, 'innerFetchColumn'), $sql, $columnIndex);
    }

    /**
     * Create object instance by specified parameters
     * @param string $className Object class name
     * @param RelationData $metaData Object metadata for creation and filling
     * @param array $dbData Database record with object data
     *
     * @return idbRecord Database record object instance
     */
    public function &createObject(
        $className,
        $identifier,
        array & $attributes,
        array & $dbData,
        array & $virtualFields = array()
    )
    {
        // If this object instance is not cached
        if (!isset(dbRecord::$instances[$className][$identifier]) || isset($dbData['__Count']) || sizeof($virtualFields)) {

            // Create empry dbRecord ancestor and store it to cache
            dbRecord::$instances[$className][$identifier] = new $className($this, new dbQuery());

            // Pointer to object
            $object = &dbRecord::$instances[$className][$identifier];

            // Set object identifier
            $object->id = $identifier;

            // Fix object connection with DB record
            $object->attached = true;

            // Fill object attributes
            foreach ($attributes as $lc_field => $field) {
                $object->$lc_field = $dbData[$field];
            }

            // Fill virtual fields
            foreach ($virtualFields as $alias => $virtual_field) {
                // If DB record contains virtual field data
                if (isset($dbData[$alias])) {
                    $object->$alias = $dbData[$alias];
                }
            }

            return $object;

        } else { // Get object instance from cache
            return dbRecord::$instances[$className][$identifier];
        }
    }

    /**
     * Regroup database rows by primary field value.
     *
     * @param array  $rows Collection of records received from database
     * @param string $primaryField Primary field name for grouping
     *
     * @return array Grouped rows by primary field value
     */
    protected function groupResults(array $rows, string $primaryField) : array
    {
        /** @var array $grouped Collection of database rows grouped by primary field value */
        $grouped = [];

        // Iterate result set
        for ($i = 0, $rowsCount = count($rows); $i < $rowsCount; $i++) {
            $row = $rows[$i];

            // Group by primary field value
            $grouped[$row[$primaryField]][] = $row;
        }

        return $grouped;
    }

    /**
     * Fill entity instance fields from row column values according to entity metadata attributes.
     *
     * @param mixed $instance Entity instance
     * @param array $attributes Metadata entity attributes
     * @param array $row        Database results row
     */
    protected function fillEntityFieldValues($instance, array $attributes, array $row)
    {
        // Iterate attribute metadata
        foreach ($attributes as $alias) {
            // If database row has aliased field column
            if (array_key_exists($alias, $row)) {
                // Store attribute value
                $instance->$alias = $row[$alias];
            } else {
                throw new \InvalidArgumentException('Database row does not have requested column:'.$alias);
            }
        }

        // Call handler for object filling
        $instance->filled();
    }

    protected function getEntities(array $rows, string $primaryField, string $className, array $joinedClassNames)
    {
        $objects = [];

        /** @var array $entityRows Iterate entity rows */
        foreach ($this->groupResults($rows, $primaryField) as $primaryValue => $entityRows) {
            // Create entity instance
            $instance = $objects[$primaryValue] = new $className();

            // TODO: $attributes argument should be filled with selected fields?
            $this->fillEntityFieldValues($instance, $className::$_attributes, $entityRows[0]);

            // Iterate inner rows for nested entities creation
            foreach ($entityRows as $row) {
                // Iterate all joined entities
                foreach ($joinedClassNames as $joinedClassName) {
                    // Create joined instance and add to parent instance
                    $joinedInstance = $instance->$joinedClassName[] = new $joinedClassName();

                    // TODO: We need to change metadata retrieval
                    $this->fillEntityFieldValues($joinedInstance, $joinedClassName::$_attributes, $row);
                }
            }
        }

        return $objects;
    }

    /**
     * Преобразовать массив записей из БД во внутреннее представление dbRecord
     * @param string $class_name Имя класса
     * @param array $response Массив записей полученных из БД
     * @return array Коллекцию записей БД во внутреннем формате
     * @see dbRecord
     */
    protected function toRecords($class_name, array &$response, array $join = array(), array $virtual_fields = array())
    {
        // Сформируем правильное имя класса
        $class_name = strpos($class_name, '\\') !== false ? $class_name : '\\samson\activerecord\\'.$class_name;

        // Результирующая коллекция полученных записей из БД
        $collection = array();

        // Получим переменные для запроса
        extract($this->__get_table_data($class_name));

        // Generate table metadata for joined tables
        $joinedTableData = array();
        foreach ($join as $relationData) {

            // Generate full joined table name(including prefix)
            $joinTable = self::$prefix . $relationData->table;

            // Get real classname of the table without alias
            $tableName = $_relation_alias[$joinTable];

            // Get joined table class metadata
            $joinedTableData[$tableName] = $this->__get_table_data($tableName);
        }

        // Получим имя главного
        $main_primary = $_primary;

        // Перебем массив полученных данных от БД - создадим для них объекты
        $records_count = sizeof($response);

        // Идентификатор текущего создаваемого объекта
        $main_id = isset($response[0]) ? $response[0][$main_primary] : 0;

        // Указатель на текущий обрабатываемый объект
        $main_obj = null;

        // Переберем полученные записи из БД
        for ($i = 0; $i < $records_count; $i++) {
            // Строка данных полученная из БД
            $db_row = &$response[$i];

            // Get object instance
            $collection[$main_id] = &$this->createObject($class_name, $main_id, $_attributes, $db_row, $virtual_fields);

            // Pointer to main object
            $main_obj = &$collection[$main_id];

            // Выполним внутренний перебор строк из БД начиная с текущей строки
            // Это позволит нам розабрать объекты полученные со связью один ко многим
            // А если это связь 1-1 то цикл выполниться только один раз
            for ($j = $i; $j < $records_count; $j++) {
                // Строка данных полученная из БД
                $db_inner_row = &$response[$j];

                // Получим идентфиикатор главного объекта в текущей строче БД
                $obj_id = $db_inner_row[$main_primary];

                // Если в строке из БД новый идентификатор
                if ($obj_id != $main_id) {
                    // Установим новый текущий идентификатор материала
                    $main_id = $obj_id;

                    // Установим индекс главного цикла на строку с новым главным элементом
                    // учтем что главный цикл сам увеличит на единицу индекс
                    $i = $j - 1;

                    //trace(' - Найден новый объект на строке №'.$j.'-'.$db_inner_row[$main_primary]);

                    // Прервем внутренний цикл
                    break;
                }
                //else trace(' + Заполняем данные из строки №'.$j);

                // Переберем все присоединенные таблицы в запросе
                foreach ($join as $relation_data) {
                    /**@var \samson\activerecord\RelationData $relation_data */

                    // If this table is not ignored
                    if (!$relation_data->ignore) {

                        // TODO: Prepare all data in RelationObject to speed up this method

                        $join_name = $relation_data->relation;

                        $join_table = self::$prefix . $relation_data->table;

                        //trace('Filling related table:'.$join_name.'/'.$join_table);

                        // Get real classname of the table without alias
                        $_relation_name = $_relation_alias[$join_table];
                        $join_class = str_replace(self::$prefix, '', $relation_data->table);

                        // Get joined table metadata from previously prepared object
                        $r_data = $joinedTableData[$_relation_name];

                        // Try to get identifier
                        if (isset($_relations[$join_table][$r_data['_primary']])) {
                            $r_obj_id_field = $_relations[$join_table][$r_data['_primary']];
                        } // Получим имя ключевого поля связанного объекта
                        else {
                            e('Cannot find related table(##) primary field(##) description',
                                E_SAMSON_ACTIVERECORD_ERROR, array($join_table, $r_data['_primary']));
                        }

                        // Если задано имя ключевого поля связанного объекта - создадим его
                        if (isset($db_inner_row[$r_obj_id_field])) {
                            // Получим ключевое поле связанного объекта
                            $r_obj_id = $db_inner_row[$r_obj_id_field];

                            // Get joined object instance
                            $r_obj = &$this->createObject($join_name, $r_obj_id, $_relations[$join_table],
                                $db_inner_row);

                            // Call handler for object filling
                            $r_obj->filled();

                            // TODO: Это старый подход - сохранять не зависимо от алиаса под реальным именем таблицы

                            // Если связанный объект привязан как один-к-одному - просто довами ссылку на него
                            if ($_relation_type[$join_table] == 0) {
                                $main_obj->onetoone['_' . $join_table] = $r_obj;
                                $main_obj->onetoone['_' . $join_class] = $r_obj;
                            } // Иначе создадим массив типа: идентификатор -> объект
                            else {
                                $main_obj->onetomany['_' . $join_table][$r_obj_id] = $r_obj;
                                $main_obj->onetomany['_' . $join_class][$r_obj_id] = $r_obj;
                            }
                        }
                    }
                }
            }

            // Call handler for object filling
            $main_obj->filled();

            // Если внутренний цикл дошел до конца остановим главный цикл
            if ($j == $records_count) {
                break;
            }
        }

        // Вернем то что у нас вышло
        return $collection;
    }

    /**
     * Обратная совместить с PHP < 5.3 т.к. там нельзя подставлять переменное имя класса
     * в статическом контексте
     * @param unknown_type $class_name
     */
    public function __get_table_data($class_name)
    {
        // Remove table prefix
        $class_name = str_replace(self::$prefix, '', $class_name);

        // Сформируем правильное имя класса
        $class_name = strpos($class_name, '\\') !== false ? $class_name : '\samson\activerecord\\'.$class_name;

        // Сформируем комманды на получение статических переменных определенного класса
        $_table_name = '$_table_name = ' . $class_name . '::$_table_name;';
        $_own_group = '$_own_group = ' . $class_name . '::$_own_group;';
        $_table_attributes = '$_table_attributes = ' . $class_name . '::$_table_attributes;';
        $_primary = '$_primary = ' . $class_name . '::$_primary;';
        $_sql_from = '$_sql_from = ' . $class_name . '::$_sql_from;';
        $_sql_select = '$_sql_select = ' . $class_name . '::$_sql_select;';
        $_attributes = '$_attributes = ' . $class_name . '::$_attributes;';
        $_types = '$_types = ' . $class_name . '::$_types;';
        $_map = '$_map = ' . $class_name . '::$_map;';
        $_relations = '$_relations = ' . $class_name . '::$_relations;';
        $_unique = '$_unique = ' . $class_name . '::$_unique;';
        $_relation_type = '$_relation_type = ' . $class_name . '::$_relation_type;';
        $_relation_alias = '$_relation_alias = ' . $class_name . '::$_relation_alias;';

        //trace($_table_name.$_primary.$_sql_from.$_sql_select.$_map.$_attributes.$_relations.$_relation_type.$_types.$_unique);

        // Выполним специальный код получения значений переменной
        eval($_own_group . $_table_name . $_primary . $_sql_from . $_sql_select . $_map . $_attributes . $_relations . $_relation_type . $_relation_alias . $_types . $_unique . $_table_attributes);

        // Вернем массив имен переменных и их значений
        return array
        (
            '_table_name' => $_table_name,
            '_own_group' => $_own_group,
            '_primary' => $_primary,
            '_attributes' => $_attributes,
            '_table_attributes' => $_table_attributes,
            '_types' => $_types,
            '_map' => $_map,
            '_relations' => $_relations,
            '_relation_type' => $_relation_type,
            '_relation_alias' => $_relation_alias,
            '_sql_from' => $_sql_from,
            '_sql_select' => $_sql_select,
            '_unique' => $_unique,
        );
    }
}
