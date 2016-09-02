<?php declare(strict_types = 1);
namespace samson\activerecord;

use samsonframework\orm\ArgumentInterface;
use samsonframework\orm\Condition;
use samsonframework\orm\ConditionInterface;
use samsonframework\orm\QueryHandler;
use samsonframework\orm\QueryInterface;

/**
 * This class will be removed in next major release.
 * @author     Vitaly Iegorov <vitalyiegorov@gmail.com>
 * @deprecated Should be removed ASAP in favor of generated classes or DI
 *
 */
class dbQuery extends QueryHandler
{
    /** Virtual field for base table */
    public $own_virtual_fields = array();
    /** Virtual fields */
    public $virtual_fields = array();
    public $empty = false;
    protected $class_name;
    /**
     * @var QueryInterface
     * @\samsonframework\containerannotation\Injectable
     */
    protected $query;
    /** @var bool True to show requests */
    protected $debug = false;

    /** Constructor */
    public function __construct()
    {
        $this->database = $GLOBALS['__core']->getContainer()->getDatabase();
        $this->sqlBuilder = $GLOBALS['__core']->getContainer()->getSqlBuilder();
    }
    
    /**
     * @param string $metadata
     *
     * @deprecated Use entity()
     * @return QueryInterface|string
     */
    public function className(string $metadata = null)
    {
        if (func_num_args() === 0) {
            return $this->metadata->className;
        } else {
            return $this->entity($metadata);
        }
    }


    /** @deprecated Use QueryInterface implementation */
    public function own_limit($st, $en = 0)
    {
        $this->query->limit($st, $en);

        return $this;
    }

    /** @deprecated Use QueryInterface implementation */
    public function own_group_by($params)
    {
        $this->query->groupBy($this->class_name, $params);

        return $this;
    }

    /** @deprecated Use QueryInterface implementation */
    public function own_order_by($field, $direction = 'ASC')
    {
        $this->query->orderBy($this->class_name, $field, $direction);

        return $this;
    }

    /** @deprecated Use QueryInterface implementation */
    public function flush()
    {

    }

    /** @see idbQuery::random() */
    public function random(& $return_value = null)
    {
        // Add random ordering
        $this->order_by('', 'RAND()');

        // Correctly perform db request for multiple data
        return func_num_args() ? $this->exec($return_value) : $this->exec();
    }

    /**
     * @param        $columnName
     * @param string $sorting
     *
     * @deprecated Use groupBy()
     * @return QueryInterface|static
     */
    public function order_by($columnName, $sorting = 'ASC')
    {
        return $this->orderBy($this->metadata->tableName, $columnName, $sorting);
    }

    /**
     * Execute current query and receive collection of RecordInterface objects from database.
     * @deprecated Use self::find()
     * @return RecordInterface[] Database entities collection
     */
    public function exec() : array
    {
        return $this->find();
    }

    /** @deprecated Use QueryInterface implementation */
    public function or_($relation = 'OR')
    {
        // Получим либо переданную группу условий, либо создадим новую, потом добавим её в массив групп условий запроса
        $cond_group = new Condition($relation);

        // Установим текущую группу условий с которой работает запрос
        $this->cConditionGroup = &$cond_group;

        // Добавим нову группу условий в коллекцию групп
        $this->condition->arguments[] = $cond_group;

        // Вернем себя для цепирования
        return $this;
    }

    /** @deprecated Use QueryInterface implementation */
    public function debug($value = true)
    {
        db()->debug($this->debug = $value);

        return $this;
    }

    /** @deprecated Use QueryInterface implementation */
    public function fieldsNew($fieldName, & $return = null)
    {
        return call_user_func_array(array($this, 'fields'), func_get_args());
    }

    /** @deprecated Use QueryInterface implementation */
    public function group_by($field)
    {
        $this->query->groupBy($this->metadata->tablename, $field);

        // Вернем себя для цепирования
        return $this;
    }

    /** @deprecated Use QueryInterface implementation */
    public function add_field($field, $alias = null, $own = true)
    {
        // Если передан псевдоним для поля, то подставим его
        if (isset($alias)) {
            $field = $field . ' as ' . $alias;
        } else {
            $alias = $field;
        }

        // Добавим виртуальное поле
        if ($own) {
            $this->own_virtual_fields[$alias] = $field;
        } else {
            $this->virtual_fields[$alias] = $field;
        }

        // Вернем себя для цепирования
        return $this;
    }

    /** @deprecated Use QueryInterface implementation */
    public function innerCount($field = '*')
    {
        return $this->query->count();
    }

    /** @deprecated Use QueryInterface implementation */
    public function id($value)
    {
        $this->query->primary($value);

        return $this;
    }

    /**
     * Add condition to current query.
     * This method supports receives three possible types for $fieldName,
     * this is deprecated logic and this should be changed to use separate methods
     * for each argument type.
     *
     * @param string|ConditionInterface|ArgumentInterface $fieldName  Entity field name
     * @param string                                      $fieldValue Value
     * @param string                                      $relation   Relation between field name and its value
     *
     * @deprecated Use QueryInterface implementation
     * @return self Chaining
     */
    public function cond($fieldName, $fieldValue = null, $relation = '=')
    {
        // If empty array is passed
        if (is_string($fieldName)) {
            return $this->where($fieldName, $fieldValue, $relation);
        } elseif (is_array($fieldValue) && !sizeof($fieldValue)) {
            $this->empty = true;
            return $this;
        } elseif ($fieldName instanceof ConditionInterface) {
            $this->whereCondition($fieldName);
        } elseif ($fieldName instanceof ArgumentInterface) {
            $this->whereCondition((new Condition())->addArgument($fieldName));
        }

        return $this;
    }
}
