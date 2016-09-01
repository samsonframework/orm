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
    /** @var string Primary field */
    public $primary2;
    /** @var string Test field */
    public $testField;
    /** @var string Test field */
    public $testColumn3;
    /** @var string Test field */
    public $testColumn4;
}
