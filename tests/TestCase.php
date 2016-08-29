<?php declare(strict_types = 1);
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>.
 * on 06.08.16 at 13:56
 */
namespace samsonframework\orm\tests;

class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * Get $object private/protected property value.
     *
     * @param string $property Private/protected property name
     *
     * @param object $object   Object instance for getting private/protected property value
     *
     * @return mixed Private/protected property value
     */
    protected function getProperty($property, $object)
    {
        $property = (new \ReflectionClass($object))->getProperty($property);
        $property->setAccessible(true);
        try {
            return $property->getValue($object);
        } catch (\Exception $e) {
            return null;
        }
    }
}