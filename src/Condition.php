<?php
namespace samsonframework\orm;

use samsonframework\orm\ConditionInterface;
use samsonframework\orm\ArgumentInterface;

/**
 * Query condition arguments group
 * @author Vitaly Iegorov <egorov@samsonos.com>
 * @version 2.0
 */
class Condition implements ConditionInterface
{
    /** @var Argument[] Collection of condition arguments */
    public $arguments = array();

    /** @var string Relation logic between arguments */
    public $relation = ConditionInterface::CONJUNCTION;

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
     * @param self $condition Condition group to be added
     * @return self Chaining
     */
    public function addCondition(self $condition)
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
}
