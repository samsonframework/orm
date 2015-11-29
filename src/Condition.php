<?php
namespace samsonframework\orm;

/**
 * Query condition arguments group
 * @author Vitaly Iegorov <egorov@samsonos.com>
 * @version 2.0
 */
class Condition
{
    /** AND(conjunction) - Condition relation type */
    const REL_AND = 'AND';

    /** OR(disjunction) - Condition relation type */
    const REL_OR = 'OR';

    /** @var Argument[] Collection of condition arguments */
    public $arguments = array();

    /** @var string Relation logic between arguments */
    public $relation = self::REL_AND;

    /**
     * Add condition argument to this condition group
     * @param ArgumentInterface $argument Condition argument to be added
     */
    public function addArgument(ArgumentInterface $argument)
    {
        // Add condition as current condition argument
        $this->arguments[] = $argument;
    }

    /**
     * Add condition group to this condition group
     * @param self $condition Condition group to be added
     */
    public function addCondition(self $condition)
    {
        // Add condition as current condition argument
        $this->arguments[] = $condition;
    }

    /**
     * Generic condition addiction function
     * @param self|Argument|string $argument Entity for adding to arguments collection
     * @param string $value Argument value
     * @param string $relation Relation between argument and value
     * @return self Chaining
     */
    public function add($argument, $value = '', $relation = Relation::EQUAL)
    {
        if (is_string($argument) || is_scalar($argument)) {
            // Add new argument to arguments collection
            $this->arguments[] = new Argument($argument, $value, $relation);
        } elseif (is_a($argument, get_class($this))) {
            $this->addCondition($argument);
        } elseif (is_a($argument, __NAMESPACE__.'Argument')) {
            $this->addArgument($argument);
        }

        return $this;
    }

    /**
     * Constructor
     * @param string $relation Relation type between arguments
     */
    public function __construct($relation = null)
    {
        $this->relation = isset($relation) ? $relation : self::REL_AND;
    }
}
