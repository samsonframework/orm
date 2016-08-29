<?php declare(strict_types = 1);
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>.
 * on 29.08.16 at 10:56
 */
namespace samsonframework\orm\tests;

use samsonframework\orm\Record;

/**
 * Class JoinTestEntity
 *
 * @author Vitaly Egorov <egorov@samsonos.com>
 */
class JoinTestEntity extends Record
{
    /** @var string Primary field name */
    public static $_primary = 'primary2';
    /** @var array Attributes metadata */
    public static $_attributes = ['testField2Alias'=>'testField2'];

    /** @var string Primary field */
    public $primary;
    /** @var string Test field */
    public $testField;
}
