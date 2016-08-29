<?php declare(strict_types=1);
namespace samsonframework\orm;

/**
 * Database query condition argument.
 *
 * @author Vitaly Iegorov <egorov@samsonos.com>
 */
class Argument implements ArgumentInterface
{
    /** @var string Argument field name */
    public $field = '';

    /** @var string Argument field value */
    public $value;

    /** @var string Argument relation between field and its value */
    public $relation = ArgumentInterface::EQUAL;

    /**
     * Constructor
     * @param string $field Argument field name
     * @param mixed $value Argument field value
     * @param string $relation Argument relation between field and its value
     * @see \samson\activerecord\Argument:relation
     */
    public function __construct($field, $value, $relation = ArgumentInterface::EQUAL)
    {
        $this->field = $field;
        $this->value = $value;
        $this->relation = !isset($relation) ? ArgumentInterface::EQUAL : $relation;
    }
}
