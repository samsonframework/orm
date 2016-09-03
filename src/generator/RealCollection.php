<?php
//[PHPCOMPRESSOR(remove,start)]
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>.
 * on 22.03.16 at 15:46
 */
namespace samsonframework\orm\generator;

use samsonframework\orm\generator\metadata\RealMetadata;
use samsonphp\generator\Generator;

/**
 * Real entity collection class generator.
 *
 * @package samsonframework\orm\generator
 */
class RealCollection extends Generic
{
    /**
     * Query constructor.
     *
     * @param Generator $generator
     * @param           $metadata
     */
    public function __construct(Generator $generator, $metadata)
    {
        parent::__construct($generator, $metadata);

        $this->parentClass = $this->className . 'Query';
        $this->className .= 'Collection';
    }

    /**
     * Class uses generation part.
     *
     * @param RealMetadata $metadata Entity metadata
     */
    protected function createUses($metadata)
    {
        $this->generator
            ->newLine('use samsonframework\core\ViewInterface;')
            ->newLine('use samsonframework\orm\QueryInterface;')
            ->newLine('use samsonframework\orm\ArgumentInterface;')
            ->newLine('use samson\activerecord\dbQuery;')
            ->newLine();
    }

    /**
     * Class definition generation part.
     *
     * @param RealMetadata $metadata Entity metadata
     */
    protected function createDefinition($metadata)
    {
        $this->generator
            ->multiComment(array(
                'Class for rendering and querying and fetching "' . $metadata->entity . '" instances from database',
                '@method ' . $metadata->entity . ' first();',
                '@method ' . $metadata->entity . '[] find();',
            ))
            ->defClass($this->className, $this->parentClass)
            ->newLine('use \\' . \samsonframework\orm\Renderable::class . ';')
            ->newLine();
    }

    /**
     * Class constructor generation part.
     *
     * @param RealMetadata $metadata Entity metadata
     */
    protected function createConstructor($metadata)
    {
        $class = "\n\t" . '/**';
        $class .= "\n\t" . ' * @param ViewInterface $renderer Rendering instance';
        $class .= "\n\t" . ' * @param QueryInterface $query Database query instance';
        $class .= "\n\t" . ' */';
        $class .= "\n\t" . 'public function __construct(ViewInterface $renderer, QueryInterface $query = null)';
        $class .= "\n\t" . '{';
        $class .= "\n\t\t" . '// TODO: This should be removed!';
        $class .= "\n\t\t" . '$this->renderer = $renderer;';
        $class .= "\n\t\t" . '$container = $GLOBALS[\'__core\']->getContainer();';
        $class .= "\n\t\t" . 'parent::__construct($query ?? $container->getQuery());';
        $class .= "\n\t" . '}';

        $this->generator->text($class);
    }
}
//[PHPCOMPRESSOR(remove,end)]
