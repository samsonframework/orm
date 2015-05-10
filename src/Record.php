<?php
namespace samsonframework\orm;

use samson\core\iModuleViewable;

/**
 * ORM Active record class
 * @author Vitaly Iegorov <egorov@samsonos.com>
 * @author Nikita Kotenko <kotenko@samsonos.com>
 */
class Record implements \samson\core\iModuleViewable, \ArrayAccess
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

    /**
     * Конструктор
     *
     * Если идентификатор не передан - выполняется создание новой записи в БД
     * Если идентификатор = FALSE - выполняеся создание объекта без его привязки к БД
     * Если идентификатор > 0 - выполняется поиск записи в БД и привязка к ней в случае нахождения
     *
     * @param mixed $id Идентификатор объекта в БД
     * @param string $class_name Имя класса
     */
    public function __construct($id = null, $class_name = null)
    {
        // Запишем имя текущего класса
        if (!isset($this->className)) {
            $this->className = get_class($this);
        } //$class_name;

        //if( get_class($this) == 'Order') elapsed('ЩКВУК!!!');

        // Если установлен флаг создания объекта без привязки к записи в БД
        if ($id === false) { /* Пустое условие для оптимизации */
        } else {
            // Если идентификатор записи в БД НЕ передан
            if (!isset($id)) {
                $this->create();
            } // Мы получили положительный идентификатор и нашли запись в БД с ним - Выполним привязку данного объекта к записи БД
            else {
                if (null !== ($db_record = db()->find_by_id($class_name, $id))) {
                    // Если по переданному ID запись была успешно получена из БД
                    // установим его как основной идентификатор объекта
                    $this->id = $id;

                    // Пробежимся по переменным класса
                    foreach ($db_record as $var => $value) {
                        $this->$var = $value;
                    }

                    // Установим флаг привязки к БД
                    $this->attached = true;
                }
            }

            // Зафиксируем данный класс в локальном кеше
            self::$instances[$class_name][$this->id] = $this;
        }
    }

    /**
     * @see idbRecord::create()
     */
    public function create()
    {
        // Если запись уже привязана к БД - ничего не делаем
        if (!$this->attached) {
            // Получим имя класса
            $class_name = $this->className;

            // Получим переменные для запроса
            extract(db()->__get_table_data($class_name));

            // Выполним создание записи в БД
            // и сразу заполним её значениями атрибутов объекта
            $this->id = db()->create($class_name, $this);

            // Получим созданную запись из БД
            $db_record = db()->find_by_id($class_name, $this->id);

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
            db()->update($this->className, $this);
        } // Иначе создадим новую запись с привязкой к данному объекту
        else {
            $this->create();
        }

        //elapsed('saving to cache:'.get_class($this).'-'.$this->id);

        // Обновим указатель на текущую запись в локальном кеше АктивРекорд
        self::$instances[ns_classname(get_class($this), '')][$this->id] = &$this;
    }

    /**    @see idbRecord::delete() */
    public function delete()
    {
        // Если запись привязана к БД то удалим её оттуда
        if ($this->attached) {
            db()->delete($this->className, $this);
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
        $this->id = db()->create($this->className, $this);

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
