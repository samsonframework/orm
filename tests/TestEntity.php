<?php declare(strict_types = 1);
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>.
 * on 29.08.16 at 10:56
 */
namespace samsonframework\orm\tests;

use samsonframework\orm\Record;

/**
 * Class TestEntity
 *
 * @author Vitaly Egorov <egorov@samsonos.com>
 */
class TestEntity extends Record
{
    /** @var string Primary field */
    public $primary;
    /** @var string Test field */
    public $testField;
    /** @var int Test field */
    public $testColumn;
    /** @var string Test field */
    public $testColumn2;
    /** @var string Test field */
    public $testColumn3;
}
