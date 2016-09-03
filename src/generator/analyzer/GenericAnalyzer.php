<?php declare(strict_types = 1);
//[PHPCOMPRESSOR(remove,start)]
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>.
 * on 23.03.16 at 11:45
 */
namespace samsonframework\orm\generator\analyzer;

use samsonframework\orm\DatabaseInterface;
use samsonframework\orm\generator\Generic;
use samsonframework\orm\generator\metadata\GenericMetadata;

/**
 * Generic entities metadata analyzer.
 *
 * @package samsonframework\orm\analyzer
 */
abstract class GenericAnalyzer
{
    /** @var DatabaseInterface */
    protected $database;

    /** @var string Metadata class */
    protected $metadataClass = GenericMetadata::class;

    /**
     * Generator constructor.
     *
     * @param DatabaseInterface $database Database instance
     */
    public function __construct(DatabaseInterface $database)
    {
        $this->database = $database;
    }

    /**
     * Analyze and create metadata collection.
     *
     * @return GenericMetadata[] Metadata collection
     */
    abstract public function analyze() : array;

    /**
     * Get correct field name.
     *
     * @param string $fieldName Original field name
     *
     * @return string Correct PHP-supported field name
     */
    protected function fieldName(string $fieldName) : string
    {
        return lcfirst($this->transliterated($fieldName));
    }

    /**
     * Transliterate string to english.
     *
     * @param string $string Source string
     *
     * @return string Transliterated string
     */
    protected function transliterated(string $string) : string
    {
        return str_replace(
            ' ',
            '',
            ucwords(iconv('UTF-8', 'UTF-8//IGNORE',
                    strtr($string, [
                            '\'' => '',
                            '`' => '',
                            '-' => ' ',
                            '_' => ' ',
                            'а' => 'a', 'А' => 'a',
                            'б' => 'b', 'Б' => 'b',
                            'в' => 'v', 'В' => 'v',
                            'г' => 'g', 'Г' => 'g',
                            'д' => 'd', 'Д' => 'd',
                            'е' => 'e', 'Е' => 'e',
                            'ж' => 'zh', 'Ж' => 'zh',
                            'з' => 'z', 'З' => 'z',
                            'и' => 'i', 'И' => 'i',
                            'й' => 'y', 'Й' => 'y',
                            'к' => 'k', 'К' => 'k',
                            'л' => 'l', 'Л' => 'l',
                            'м' => 'm', 'М' => 'm',
                            'н' => 'n', 'Н' => 'n',
                            'о' => 'o', 'О' => 'o',
                            'п' => 'p', 'П' => 'p',
                            'р' => 'r', 'Р' => 'r',
                            'с' => 's', 'С' => 's',
                            'т' => 't', 'Т' => 't',
                            'у' => 'u', 'У' => 'u',
                            'ф' => 'f', 'Ф' => 'f',
                            'х' => 'h', 'Х' => 'h',
                            'ц' => 'c', 'Ц' => 'c',
                            'ч' => 'ch', 'Ч' => 'ch',
                            'ш' => 'sh', 'Ш' => 'sh',
                            'щ' => 'sch', 'Щ' => 'sch',
                            'ъ' => '', 'Ъ' => '',
                            'ы' => 'y', 'Ы' => 'y',
                            'ь' => '', 'Ь' => '',
                            'э' => 'e', 'Э' => 'e',
                            'ю' => 'yu', 'Ю' => 'yu',
                            'я' => 'ya', 'Я' => 'ya',
                            'і' => 'i', 'І' => 'i',
                            'ї' => 'yi', 'Ї' => 'yi',
                            'є' => 'e', 'Є' => 'e'
                        ]
                    )
                )
            )
        );
    }

    /**
     * Get correct full entity name with name space.
     *
     * @param string $navigationName Original navigation entity name
     * @param string $namespace      Namespace
     *
     * @return string Correct PHP-supported entity name
     */
    protected function fullEntityName(string $navigationName, string $namespace = null) : string
    {
        return ($namespace ?? Generic::GENERATED_NAMESPACE) . $this->entityName($navigationName);
    }

    /**
     * Get correct entity name.
     *
     * @param string $navigationName Original navigation entity name
     *
     * @return string Correct PHP-supported entity name
     */
    protected function entityName(string $navigationName) : string
    {
        return ucfirst($this->getValidName($this->transliterated($navigationName)));
    }

    /**
     * Remove all wrong characters from entity name
     *
     * @param string $navigationName Original navigation entity name
     *
     * @return string Correct PHP-supported entity name
     */
    protected function getValidName(string $navigationName) : string
    {
        return preg_replace('/(^\d*)|([^\w\d_])/', '', $navigationName);
    }

    /**
     * Get class constant name by its value.
     *
     * @param string $value     Constant value
     * @param string $className Class name
     *
     * @return string Full constant name
     */
    protected function constantNameByValue(string $value, string $className) : string
    {
        // Get array where class constants are values and their values are keys
        $nameByValue = array_flip((new \ReflectionClass($className))->getConstants());

        // Try to find constant by its value
        if (null !== $nameByValue[$value]) {
            // Return constant name
            return $nameByValue[$value];
        }

        return '';
    }
}

//[PHPCOMPRESSOR(remove,end)]