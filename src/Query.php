<?php
namespace samsonframework\orm;
use samsonframework\orm\exception\EntityNotFound;

/**
 * Database query builder and executer.
 * @author Vitaly Iegorov <egorov@samsonos.com>
 * @version 2.0
 */
class Query extends QueryHandler implements QueryInterface
{
    /** @var string Class name for interacting with database */
    protected $class_name;

    /** @var array Collection of entity field names for sorting order */
    protected $sorting = array();

    /** @var array Collection of entity field names for grouping query results */
    protected $grouping = array();

    /** @var array Collection of query results limitations */
    protected $limitation = array();

    /** @var Condition Query base entity condition group */
    protected $own_condition;

    /** @var Condition Query entity condition group */
    protected $cConditionGroup;

    /** @var Database Database instance */
    protected $database;

    /**
     * Calling this class will result as changing entity.
     *
     * @param String $entity
     * @see self::entity()
     * @return self Chaining
     * @throws EntityNotFound
     */
    public function __invoke($entity)
    {
        return $this->entity($entity);
    }

    /**
     * Query constructor.
     * @param string|null $entity Entity identifier
     * @param Database Database instance
     * @throws EntityNotFound
     */
    public function __construct($entity, Database &$database)
    {
        $this->database = &$database;
        $this->entity($entity);
        $this->flush();
    }

    /**
     * Reset all query parameters
     * @return self Chaining
     */
    public function flush()
    {
        $this->grouping = array();
        $this->limitation = array();
        $this->sorting = array();

        $this->cConditionGroup = new Condition();
        $this->own_condition = new Condition();

        return $this;
    }

    /**
     * Perform database request and get collection of database record objects
     * @see \samson\activerecord\Query::execute()
     * @param mixed $return External variable to store query results
     * @return mixed If no arguments passed returns query results collection, otherwise query success status
     */
    public function exec(& $return = null)
    {
        $args = func_num_args();
        return $this->execute($return, $args);
    }

    /**
     * Perform database request and get first record from results collection
     * @see \samson\activerecord\Query::execute()
     * @param mixed $return External variable to store query results
     * @return mixed If no arguments passed returns query results first database record object,
     * otherwise query success status
     */
    public function first(& $return = null)
    {
        $args = func_num_args();
        return $this->execute($return, $args, 1);
    }

    /**
     * Perform database request and get array of record field values
     * @see \samson\activerecord\Query::execute()
     * @param string $fieldName Record field name to get value from
     * @param string $return External variable to store query results
     * @return Ambigous <boolean, NULL, mixed>
     */
    public function fields($fieldName, & $return = null)
    {
        // Call handlers stack
        $this->_callHandlers();

        // Perform DB request
        $return = $this->database->fetchColumn($this->class_name, $this, $fieldName);

        $success = is_array($return) && sizeof($return);

        // If parent function has arguments - consider them as return value and return request status
        if (func_num_args() - 1 > 0) {
            return $success;
        } else { // Parent function has no arguments, return request result
            return $return;
        }
    }


    /**
     * Perform database request and return different results depending on function arguments.
     * @see \samson\activerecord\Record
     * @param array $result External variable to store dabatase request results collection
     * @param integer|bool $rType Amount of arguments passed to parent function
     * @param integer $limit Quantity of records to return
     * @param callable $handler External callable handler for results modification
     * @param array $handlerArgs External callable handler arguments
     * @return boolean/array Boolean if $r_type > 0, otherwise array of request results
     */
    protected function &execute(
        & $result = null,
        $rType = false,
        $limit = null
    ) {
        // Call handlers stack
        $this->_callHandlers();

        // Perform DB request
        $result = $this->database->find($this->class_name, $this);

        // Clear this query
        $this->flush();

        // Count records
        $count = sizeof($result);

        // Define is request was successful
        $success = is_array($result) && $count;

        // Is amount of records is specified
        if (isset($limit)) {
            // If we have not enought records - return null
            if ($count < $limit) {
                $result = null;
            } elseif ($limit === 1) { // If we need first record
                $result = array_shift($result);
            } elseif ($limit > 1) { // Slice array for nessesar amount
                $result = array_slice($result, 0, $limit);
            }
        }

        // If parent function has arguments - consider them as return value and return request status
        if ($rType > 0) {
            return $success;
        } else { // Parent function has no arguments, return request result
            return $result;
        }
    }

    /**
     * Set query entity to work with.
     *
     * @param string $entity Entity identifier
     * @return Query|string Chaining or current entity identifier if nothing is passed
     * @throws EntityNotFound
     */
    public function entity($entity = null)
    {
        if (func_num_args() > 0) {
            if (class_exists($entity)) {
                $this->flush();
                $this->class_name = $entity;
            } else {
                throw new EntityNotFound('['.$entity.'] not found');
            }

            return $this;
        }

        return $this->class_name;
    }

    /**
     * Get correct query condition depending on entity field name.
     * If base entity has field with this name - use base entity condition
     * group, otherwise default condition group.
     *
     * @param string $fieldName Entity field name
     * @return Condition Correct query condition group
     */
    protected function &getConditionGroup($fieldName)
    {
        if (property_exists($this->class_name, $fieldName)) {
            // Add this condition to base entity condition group
            return $this->own_condition;
        }

        return $this->cConditionGroup;
    }

    /**
     * Add query condition as prepared Condition instance.
     *
     * @param ConditionInterface $condition Condition to be added
     * @return self Chaining
     */
    public function whereCondition(ConditionInterface $condition)
    {
        // Iterate condition arguments
        foreach ($condition as $argument) {
            // If passed condition group has another condition group as argument
            if (is_a($argument, __NAMESPACE__ . '\Condition')) {
                // Go deeper in recursion
                $this->whereCondition($argument);
            } else { // Otherwise add condition argument to correct condition group
                $this->getConditionGroup($argument->field)->addArgument($argument);
            }
        }

        return $this;
    }

    /**
     * Add condition to current query.
     *
     * @param string|Condition|Argument $fieldName Entity field name
     * @param string $fieldValue Value
     * @param string $relation Relation between field name and its value
     * @return self Chaining
     */
    public function where($fieldName, $fieldValue = null, $relation = '=')
    {
        // If empty array is passed
        if (is_string($fieldName)) {
            // Handle empty field value passing to avoid unexpected behaviour
            if (!isset($fieldValue)) {
                $relation = ArgumentInterface::ISNULL;
                $fieldValue = '';
            }

            // Add condition argument
            $this->getConditionGroup($fieldName)->add($fieldName, $fieldValue, $relation);
        } else {
            throw new \InvalidArgumentException('You can only pass string as first argument');
        }

        return $this;
    }

    /**
     * Join entity to query.
     *
     * @param string $entityName Entity identifier
     * @return self Chaining
     */
    public function join($entityName)
    {
        // TODO: We need to implement this logic
        $entityName .= '';

        // Chaining
        return $this;
    }

    /**
     * Add query result grouping.
     *
     * @param string $fieldName Entity field identifier for grouping
     * @return self Chaining
     */
    public function groupBy($fieldName)
    {
        $this->grouping[] = $fieldName;

        // Chaining
        return $this;
    }

    /**
     * Add query result quantity limitation.
     *
     * @param int $offset Resulting offset
     * @param null|int $quantity Amount of RecordInterface object to return
     * @return self Chaining
     */
    public function limit($offset, $quantity = null)
    {
        $this->limitation = array($offset, $quantity);

        // Chaining
        return $this;
    }

    /**
     * Add query result sorting.
     *
     * @param string $fieldName Entity field identifier for worting
     * @param string $order Sorting order
     * @return self Chaining
     */
    public function orderBy($fieldName, $order = 'ASC')
    {
        $this->sorting[] = array($fieldName, $order);

        // Chaining
        return $this;
    }

    /**
     * Add condition by primary field
     *
     * @param string $value Primary field value
     * @return self Chaining
     */
    public function id($value)
    {
        // PHP 5.2 get primary field
        $_primary = null;
        eval('$_primary = ' . $this->class_name . '::$_primary;');

        // Set primary field value
        return $this->where($_primary, $value);
    }

    /**
     * Add condition to current query.
     * This method supports receives three possible types for $fieldName,
     * this is deprecated logic and this should be changed to use separate methods
     * for each argument type.
     *
     * @param string|Condition|Argument $fieldName Entity field name
     * @param string $fieldValue Value
     * @param string $relation Relation between field name and its value
     * @deprecated @see self::where()
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
        } elseif (is_a($fieldName, __NAMESPACE__.'\Condition')) {
            $this->whereCondition($fieldName);
        } elseif (is_a($fieldName, __NAMESPACE__.'\Argument')) {
            $this->getConditionGroup($fieldName->field)->addArgument($fieldName);
        }

        return $this;
    }
}
