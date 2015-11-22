<?php
namespace samsonframework\orm;

use samson\core\iModuleViewable;

/**
 * ORM Active record class
 * @author Vitaly Iegorov <egorov@samsonos.com>
 * @author Nikita Kotenko <kotenko@samsonos.com>
 */
class Record implements iModuleViewable, \ArrayAccess
{
    /** @var array Collection of instances for caching */
    public static $instances = array();

    /** Collection of class fields that would not be passed to module view */
    public static $restricted = array('attached', 'oneToOne', 'oneToMany', 'className');

    /** @var int Identifier */
    public $id = 0;

    /** @var string Entity class name */
    public $className;

    /** @var array Related OTO records grouped by entity class name */
    public $oneToOne = array();

    /** @var array Related OTM records grouped by entity class name */
    public $oneToMany = array();

    /** @var bool Flag if this object has a database record */
    public $attached = false;

    /** @var Database Database layer */
    protected $database;
    
    /**
     * Find database record by column name and its value.
     * This is generic method that should be used in nested classes to find its
     * records by some its column values.
     *
     * @param Query $query Query object instance
     * @param string $columnValue Column name for searching in calling class
     * @param string $columnName Column value
     * @return null|Record  Record instance if it was found and 4th variable has NOT been passed,
     *                      NULL if record has NOT been found and 4th variable has NOT been passed
     */
    public static function oneByColumn(Query $query, $columnValue, $columnName)
    {
        // Perform db request and get materials
        return $query->className(get_called_class())
            ->cond($columnName, $columnValue)
            ->first();
    }

    /**
     * Find database record collection by column name and its value.
     * This is generic method that should be used in nested classes to find its
     * records by some its column values.
     *
     * @param Query $query Query object instance
     * @param string $columnValue Column name for searching in calling class
     * @param string $columnName Column value
     * @return Record[]  Record instance if it was found and 4th variable has NOT been passed,
     *                      NULL if record has NOT been found and 4th variable has NOT been passed
     */
    public static function collectionByColumn(Query $query, $columnValue, $columnName)
    {
        // Perform db request and get materials
        return $query->className(get_called_class())
            ->cond($columnName, $columnValue)
            ->exec();
    }

    /** Serialization handler */
    public function __sleep()
    {
	    $ignore = array('database','_table_name','_own_group','_primary','_attributes',
		    '_table_attributes','_sql_from','_relations','_relation_alias',
		    '_relation_type','_sql_select','_types','_indeces','_unique','_map','instances',
		    'restricted');

        // List of serialized object fields
        return array_diff(array_keys(get_object_vars($this)), $ignore);
    }

    /**
     * Конструктор
     *
     * Если идентификатор не передан - выполняется создание новой записи в БД
     * Если идентификатор = FALSE - выполняеся создание объекта без его привязки к БД
     * Если идентификатор > 0 - выполняется поиск записи в БД и привязка к ней в случае нахождения
     *
     * @param mixed $id Идентификатор объекта в БД
     * @param string $className Имя класса
     */
    public function __construct($database = null)
    {
        // TODO: db() should be removed
        // Get database layer
        $this->database = isset($database) ? $database : db();

        // Get current class name if none is passed
        $this->className = get_class($this);
    }

    /**
     * @see idbRecord::create()
     */
    public function create()
    {
        // Если запись уже привязана к БД - ничего не делаем
        if (!$this->attached) {
            // Получим имя класса
            $className = $this->className;

            // Получим переменные для запроса
            extract($this->database->__get_table_data($className));

            // Выполним создание записи в БД
            // и сразу заполним её значениями атрибутов объекта
            $this->id = $this->database->create($className, $this);

            // Получим созданную запись из БД
            $db_record = $this->database->find_by_id($className, $this->id);

            // Запишем все аттрибуты которые БД выставила новой записи
            foreach ($_attributes as $name => $r_name) {
                $this->$name = $db_record->$name;
            }

            // Установим флаг что мы привязались к БД
            $this->attached = true;
        }
    }

    /**
     * @see idbRecord::save()
     */
    public function save()
    {
        // Если данный объект еще привязан к записи в БД - выполним обновление записи в БД
        if ($this->attached) {
            $this->database->update($this->className, $this);
        } else { // Иначе создадим новую запись с привязкой к данному объекту
            $this->create();
        }

        // Store instance in cache
        self::$instances[$this->className][$this->id] = & $this;
    }

    /**    @see idbRecord::delete() */
    public function delete()
    {
        // Если запись привязана к БД то удалим её оттуда
        if ($this->attached) {
            $this->database->delete($this->className, $this);
        }
    }

    /** Special method called when object has been filled with data */
    public function filled()
    {

    }


    /**
     * Обработчик клонирования записи
     * Этот метод выполняется при системном вызове функции clone
     * и выполняет создание записи в БД и привязку клонированного объекта к ней
     */
    public function __clone()
    {
        // Выполним создание записи в БД
        $this->id = $this->database->create($this->className, $this);

        // Установим флаг что мы привязались к БД
        $this->attached = true;

        // Сохраним запись в БД
        $this->save();
    }

    /** @see \samson\core\iModuleViewable::toView() */
    public function toView($prefix = null, array $restricted = array())
    {
        // Create resulting view data array, add identifier field
        $values = array($prefix . 'id' => $this->id);

        // Учтем поля которые не нужно превращать в массив
        $restricted = array_merge(self::$restricted, $restricted);

        // Пробежимся по переменным класса
        foreach (get_object_vars($this) as $var => $value) {
            // Если это не системное поле записи - запишем его значение
            if (!in_array($var, $restricted)) {
                $values[$prefix . $var] = is_string($value) ? trim($value) : $value;
            }
        }

        // Вернем массив атрибутов представляющий запись БД
        return $values;
    }

    /**
     * Create full entity copy from
     * @param mixed $object Variable to return copied object
     * @return Record New copied object
     */
    public function & copy(&$object = null)
    {
        // Get current entity class
        $entity = get_class($this);

        // Create object instance
        $object = new $entity(false);

        // PHP 5.2 compliant get attributes
        $attributes = array();
        eval('$attributes = ' . $entity . '::$_attributes;');


        // Iterate all object attributes
        foreach ($attributes as $attribute) {
            // If we have this attribute set
            if (isset($this[$attribute])) {
                // Store it in copied object
                $object[$attribute] = $this[$attribute];
            }
        }

        // Save object in database
        $object->save();

        // Return created copied object
        return $object;
    }

    /** @see ArrayAccess::offsetSet() */
    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;
    }

    /** @see ArrayAccess::offsetGet() */
    public function offsetGet($offset)
    {
        return $this->$offset;
    }

    /** @see ArrayAccess::offsetUnset() */
    public function offsetUnset($offset)
    {
        unset($this->$offset);
    }

    /** @see ArrayAccess::offsetExists() */
    public function offsetExists($offset)
    {
        return property_exists($this, $offset);
    }
}
