<?php
namespace samsonframework\orm;

/**
 * Database field relation types
 * @author Vitaly Iegorov <egorov@samsonos.com>
 */
class Relation
{
    const EQUAL = '=';
    const NOT_EQUAL = '!=';
    const GREATER = '>';
    const LOWER = '<';
    const GREATER_EQ = '>=';
    const LOWER_EQ = '<=';
    const LIKE = ' LIKE ';
    const NOTNULL = ' IS NOT NULL ';
    const ISNULL = ' IS NULL ';
    const OWN = ' !!! ';
}