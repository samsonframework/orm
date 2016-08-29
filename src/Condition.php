<?php declare(strict_types=1);
namespace samsonframework\orm;

/**
 * Query condition arguments group.
 *
 * @author Vitaly Iegorov <egorov@samsonos.com>
 */
class Condition implements ConditionInterface
{
    /** @var string Relation logic between arguments */
    public $relation = ConditionInterface::CONJUNCTION;

    /** @var Argument[] Collection of condition arguments */
    protected $arguments = [];

    /**
     * {@inheritdoc}
     */
    public function addArgument(ArgumentInterface $argument)
    {
        // Add condition as current condition argument
        $this->arguments[] = $argument;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addCondition(ConditionInterface $condition)
    {
        // Add condition as current condition argument
        $this->arguments[] = $condition;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function size()
    {
        return count($this->arguments);
    }

    /**
     * {@inheritdoc}
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
        $this->relation = null !== $relation ? $relation : ConditionInterface::CONJUNCTION;
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return current($this->arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        next($this->arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return key($this->arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return key($this->arguments) !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        reset($this->arguments);
    }
}
