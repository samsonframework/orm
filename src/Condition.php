<?php
namespace samsonframework\orm;

/**
 * Query condition arguments group
 * @author Vitaly Iegorov <egorov@samsonos.com>
 * @version 2.0
 */
class Condition implements ConditionInterface
{
    /** @var string Relation logic between arguments */
    public $relation = ConditionInterface::CONJUNCTION;

    /** @var Argument[] Collection of condition arguments */
    protected $arguments = array();

    /**
     * Add condition argument to this condition group
     * @param ArgumentInterface $argument Condition argument to be added
     * @return self Chaining
     */
    public function addArgument(ArgumentInterface $argument)
    {
        // Add condition as current condition argument
        $this->arguments[] = $argument;

        return $this;
    }

    /**
     * Add condition group to this condition group
     * @param ConditionInterface $condition Condition group to be added
     * @return self Chaining
     */
    public function addCondition(ConditionInterface $condition)
    {
        // Add condition as current condition argument
        $this->arguments[] = $condition;

        return $this;
    }

    /**
     * Generic condition addiction function
     * @param string $argument Entity for adding to arguments collection
     * @param mixed $value Argument value
     * @param string $relation Relation between argument and value
     * @return self Chaining
     */
    public function add($argument, $value, $relation = ArgumentInterface::EQUAL)
    {
        if (is_string($argument)) {
            // Add new argument to arguments collection
            $this->arguments[] = new Argument($argument, $value, $relation);
        }

        return $this;
    }

    /**
     * Constructor
     * @param string $relation Relation type between arguments
     */
    public function __construct($relation = null)
    {
        $this->relation = isset($relation) ? $relation : ConditionInterface::CONJUNCTION;
    }

    /**
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     * @since 5.0.0
     */
    public function current()
    {
        return current($this->arguments);
    }

    /**
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function next()
    {
        next($this->arguments);
    }

    /**
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key()
    {
        return key($this->arguments);
    }

    /**
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     * @since 5.0.0
     */
    public function valid()
    {
        return key($this->arguments) !== null;
    }

    /**
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind()
    {
        reset($this->arguments);
    }
}
