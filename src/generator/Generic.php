<?php
//[PHPCOMPRESSOR(remove,start)]
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>.
 * on 22.03.16 at 17:50
 */
namespace samsonframework\orm\generator;

use samsonphp\generator\Generator;

/**
 * Generic object-oriented programming class generator.
 *
 * @package samsonframework\ormgenerator
 */
abstract class Generic
{
    /** Generated classes namespace prefix */
    const GENERATED_NAMESPACE = '\samsonframework\orm\generated\\';

    /** @var string Generated class name */
    public $className;

    /** @var string Generated parent class */
    protected $parentClass;

    /** @var Generator Code generation instance */
    protected $generator;

    /** @var \samsonframework\ormgenerator\metadata\GenericMetadata Entity query Generic */
    protected $metadata;

    /**
     * OOP constructor.
     *
     * @param Generator                                               $generator Code generation instance
     * @param \samsonframework\orm\generator\metadata\GenericMetadata $Generic   Entity query metadata
     */
    public function __construct(Generator $generator, $metadata)
    {
        $this->metadata = $metadata;
        $this->generator = $generator;
        $this->className = $metadata->entity;
    }

    /**
     * Generic class generation.
     *
     * @param Generic|metadata\GenericMetadata $metadata Entity metadata
     *
     * @return string Generated PHP class code
     */
    public function generate(Generic $metadata = null)
    {
        $metadata = null === $metadata ? $this->metadata : $metadata;

        $this->createUses($metadata);
        $this->createDefinition($metadata);
        $this->createConstants($metadata);
        $this->createStaticFields($metadata);
        $this->createStaticMethods($metadata);
        $this->createFields($metadata);
        $this->createMethods($metadata);
        $this->createConstructor($metadata);

        return $this->generator->endClass()->flush();
    }

    /**
     * Class uses generation part.
     *
     * @param \samsonframework\orm\generator\metadata\GenericMetadata $metadata Entity metadata
     */
    protected function createUses($metadata)
    {

    }

    /**
     * Class definition generation part.
     *
     * @param \samsonframework\orm\generator\metadata\GenericMetadata $metadata Entity metadata
     */
    abstract protected function createDefinition($metadata);

    /**
     * Class constants generation part.
     *
     * @param \samsonframework\orm\generator\metadata\GenericMetadata $metadata Entity metadata
     */
    protected function createConstants($metadata)
    {

    }

    /**
     * Class static fields generation part.
     *
     * @param \samsonframework\orm\generator\metadata\GenericMetadata $metadata Entity metadata
     */
    protected function createStaticFields($metadata)
    {

    }

    /**
     * Class static methods generation part.
     *
     * @param \samsonframework\orm\generator\metadata\GenericMetadata $metadata Entity metadata
     */
    protected function createStaticMethods($metadata)
    {

    }

    /**
     * Class fields generation part.
     *
     * @param \samsonframework\orm\generator\metadata\GenericMetadata $metadata Entity metadata
     */
    protected function createFields($metadata)
    {

    }

    /**
     * Class methods generation part.
     *
     * @param \samsonframework\orm\generator\metadata\GenericMetadata $metadata Entity metadata
     */
    protected function createMethods($metadata)
    {

    }

    /**
     * Class constructor generation part.
     *
     * @param \samsonframework\orm\generator\metadata\GenericMetadata $metadata Entity metadata
     */
    protected function createConstructor($metadata)
    {

    }
}
//[PHPCOMPRESSOR(remove,end)]
