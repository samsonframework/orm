<?php
namespace samsonframework\orm;

/**
 * Database query builder and executer.
 * @author Vitaly Iegorov <egorov@samsonos.com>
 * @version 2.0
 */
class Query extends QueryHandler implements QueryInterface
{
    /** @var string Class name for interacting with database */
    protected $class_name;

    /** @var self[] Collection of query parameters objects */
    protected $parameters = array();

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

    /**
     * Reset all query parameters
     * @return self Chaining
     */
    public function flush()
    {
        // TODO: Do we need it?
        foreach ($this->parameters as $param) {
            $param->flush();
        }

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
        $return = db()->fetchColumn($this->class_name, $this, $fieldName);

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
        $limit = null,
        $handler = null,
        $handlerArgs = array()
    )
    {
        // Call handlers stack
        $this->_callHandlers();

        // Perform DB request
        $result = db()->find($this->class_name, $this);

        // If external result handler is passed - use it
        if (isset($handler)) {
            // Add results collection to array
            array_unshift($handlerArgs, $result);

            // Call external handler with parameters
            $result = call_user_func_array($handler, $handlerArgs);
        }

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
     * @return self|string Chaining or current entity identifier if nothing is passed
     */
    public function entity($entity = null)
    {
        $this->class_name = isset($entity) ? $entity : $this->class_name;

        return func_num_args() > 0 ? $this->class_name : $this;
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
        } elseif (is_array($fieldValue) && !sizeof($fieldValue)) {
            $this->empty = true;
            return $this;
        } elseif (is_a($fieldName, __NAMESPACE__.'\Condition')) {
            foreach ($fieldName as $argument) {
                // If passed condition group has another condition group as argument
                if (is_a($fieldName, __NAMESPACE__.'\Condition')) {
                    // Go deeper in recursion
                    return $this->cond($argument, $fieldValue, $relation);
                } else { // Otherwise add condition argument to correct condition group
                    $this->getConditionGroup($argument->field)->addArgument($fieldName);
                }
            }
        } elseif (is_a($fieldName, __NAMESPACE__.'\Argument')) {
            $this->getConditionGroup($fieldName->field)->addArgument($fieldName);
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
     * Add condition to current query.
     *
     * @param string|Condition|Argument $fieldName Entity field name
     * @param string $fieldValue Value
     * @param string $relation Relation between field name and its value
     * @deprecated @see self::where()
     * @return self Chaining
     */
    public function cond($fieldName, $fieldValue = null, $relation = '=')
    {
        return $this->where($fieldName, $fieldValue, $relation);
    }
}
