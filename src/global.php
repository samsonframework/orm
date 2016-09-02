<?php declare(strict_types = 1);
/**
 * @deprecated Use dependency injection or generated class
 */
function db($link_id = null)
{
    static $_db;

    // Get from new container
    if (array_key_exists('__core', $GLOBALS) && $_db === null) {
        $_db = $GLOBALS['__core']->getContainer()->getDatabase();
    }

    return $_db;
}
