<?php
namespace samsonframework\orm;

/**
 * Universal class for storing query condition argument
 * @author Vitaly Iegorov <egorov@samsonos.com>
 * @version 2.0
 */
class Argument
{
    /** Query condition field name */
    public $field = '';

    /** Query condition field value */
    public $value;

    /**
     * Query argument relation beetween field and value
     * @var dbRelation
     */
    public $relation = Relation::EQUAL;

    /**
     * Construcor
     * @param string $relation Query argument relation beetween field and value
     * @see \samson\activerecord\Argument:relation
     */
    public function __construct($field, $value, $relation = Relation::EQUAL)
    {
        // Установим поле условия
        $this->field = $field;

        // Установим значение поля условия
        $this->value = $value;

        // Установим отношение
        $this->relation = !isset($relation) ? Relation::EQUAL : $relation;
    }
}
