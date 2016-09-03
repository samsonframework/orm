<?php
//[PHPCOMPRESSOR(remove,start)]
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>.
 * on 22.03.16 at 17:50
 */
namespace samsonframework\orm\generator;

use samsonframework\orm\generator\metadata\RealMetadata;

/**
 * Real database instances class generator.
 *
 * @package samsonframework\orm\generator
 */
class RealEntity extends Generic
{
    /**
     * Class definition generation part.
     *
     * @param \samsonframework\orm\generator\metadata\GenericMetadata $metadata Entity metadata
     */
    protected function createDefinition($metadata)
    {
        $this->generator
            ->multiComment(array('"' . $metadata->entity . '" database entity class'))
            ->defClass($this->className, '\\' . \samsonframework\orm\Record::class);
    }

    /**
     * Class constants generation part.
     *
     * @param RealMetadata $metadata Entity metadata
     */
    protected function createConstants($metadata)
    {
        $this->generator
            ->commentVar('string', 'Entity full class name, use ::class instead')
            ->defClassConst('ENTITY', $metadata->entityClassName)
            ->commentVar('string', 'Entity primary field name')
            ->defClassConst('F_PRIMARY', $metadata->primaryField)
            ->commentVar('string', 'Entity deleted flag field name')
            ->defClassConst('F_DELETION', 'Active')
            ->commentVar('string', 'Entity manager full class name')
            ->defClassConst('MANAGER', $metadata->entityClassName . 'Query');

        // Create all entity fields constants storing each additional field metadata
        foreach ($metadata->fields as $fieldID => $fieldName) {
            $this->generator
                ->commentVar('string', $fieldName . ' entity field')
                ->defClassConst('F_' . $fieldName, $fieldName);
        }
    }

    /**
     * Class static fields generation part.
     *
     * @param RealMetadata $metadata Entity metadata
     */
    protected function createStaticFields($metadata)
    {
        $this->generator
//            ->commentVar('array', '@deprecated Old ActiveRecord data')
//            ->defClassVar('$_sql_select', 'public static ', $metadata->arSelect)
//            ->commentVar('array', '@deprecated Old ActiveRecord data')
//            ->defClassVar('$_attributes', 'public static ', $metadata->arAttributes)
//            ->commentVar('array', '@deprecated Old ActiveRecord data')
//            ->defClassVar('$_types', 'public static ', $metadata->arTypes)
//            ->commentVar('array', '@deprecated Old ActiveRecord data')
//            ->defClassVar('$_table_attributes', 'public static ', $metadata->arTableAttributes)
//            ->commentVar('array', '@deprecated Old ActiveRecord data')
//            ->defClassVar('$_map', 'public static ', $metadata->arMap)
//            ->commentVar('array', '@deprecated Old ActiveRecord data')
//            ->defClassVar('$_sql_from', 'public static ', $metadata->arFrom)
//            ->commentVar('array', '@deprecated Old ActiveRecord data')
//            ->defClassVar('$_own_group', 'public static ', $metadata->arGroup)
//            ->commentVar('array', '@deprecated Old ActiveRecord data')
//            ->defClassVar('$_relation_alias', 'public static ', $metadata->arRelationAlias)
//            ->commentVar('array', '@deprecated Old ActiveRecord data')
//            ->defClassVar('$_relation_type', 'public static ', $metadata->arRelationType)
//            ->commentVar('array', '@deprecated Old ActiveRecord data')
//            ->defClassVar('$_relations', 'public static ', $metadata->arRelations)
            ->commentVar('string', '@deprecated Entity table primary field name')
            ->defClassVar('$_primary', 'public static', $metadata->primaryField)
            ->commentVar('array', '@deprecated Entity fields and aliases')
            ->defClassVar('$_attributes', 'public static', $metadata->fields)
            ->commentVar('array', '@deprecated Old ActiveRecord data')
            ->defClassVar('$fieldIDs', 'public static ', $metadata->fields);
    }

    /**
     * Class fields generation part.
     *
     * @param RealMetadata $metadata Entity metadata
     */
    protected function createFields($metadata)
    {
        foreach ($metadata->fields as $fieldID => $fieldName) {
            $this->generator
                ->commentVar($metadata->types[$fieldID], $fieldName . ' entity field')
                ->defClassVar('$' . $fieldName, 'public');
        }
    }
}
//[PHPCOMPRESSOR(remove,end)]
