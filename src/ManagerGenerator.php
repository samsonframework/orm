<?php
/**
 * Created by PhpStorm.
 * User: VITALYIEGOROV
 * Date: 30.11.15
 * Time: 16:38
 */
namespace samsonframework\orm;

/**
 * Database entity manager.
 * @package samsonframework\orm
 */
class ManagerGenerator
{
    /** @var QueryInterface Database query manager */
    protected $query;

    /** @var string SQL statement for retrieving database tables metadata */
    protected $metadataSQL = '
      SELECT
      `TABLES`.`TABLE_NAME` as `TABLE_NAME`,
      `COLUMNS`.`COLUMN_NAME` as `Field`,
      `COLUMNS`.`DATA_TYPE` as `Type`,
      `COLUMNS`.`IS_NULLABLE` as `Null`,
      `COLUMNS`.`COLUMN_KEY` as `Key`,
      `COLUMNS`.`COLUMN_DEFAULT` as `Default`,
      `COLUMNS`.`EXTRA` as `Extra`
      FROM `information_schema`.`TABLES` as `TABLES`
      LEFT JOIN `information_schema`.`COLUMNS` as `COLUMNS`
      ON `TABLES`.`TABLE_NAME`=`COLUMNS`.`TABLE_NAME`
      WHERE `TABLES`.`TABLE_SCHEMA`="\' . $this->database . \'" AND `COLUMNS`.`TABLE_SCHEMA`="\' . $this->database . \'"\
      ';

    /**
     * ManagerGenerator constructor.
     *
     * @param QueryInterface $query Database query manager
     */
    public function __construct(QueryInterface $query)
    {
        $this->query = $query;
    }

    protected function metadata()
    {
        // Получим информацию о всех таблицах из БД
        foreach ($this->query->sql($this->metadataSQL) as $tableMetadata) {
            trace($tableMetadata, 1);
        }

//        foreach ($rows as $row) {
//            // Получим имя таблицы
//            $table_name = $row['TABLE_NAME'];
//
//            // Создадим коллекцию для описания структуры таблицы
//            if (!isset(self::$tables[$table_name])) {
//                self::$tables[$table_name] = array();
//            }
//
//            // Удалим имя таблицы из масива
//            unset($row['TABLE_NAME']);
//
//            // Запишем описание каждой колонки таблиц в специальный массив
//            self::$tables[$table_name][] = $row;
//        }
//
//        $bstr = md5(serialize(self::$tables));
//
//        //TODO: check if virtual table has not changed and add it to hash
//
//        // Создадим имя файла содержащего пути к модулям
//        $md5_file = $cachePath . 'metadata/classes_' . $bstr . '.php';
//        $md5_file_func = $cachePath . 'metadata/func_' . $bstr . '.php';
//
//        // Если еще не создан отпечаток базы данных - создадим его
//        if (!file_exists($md5_file) || $force) {
//            // Get directory path
//            $dir = pathname($md5_file);
//
//            // Create folder
//            if (!file_exists($dir)) {
//                mkdir($dir, 0777, true);
//            } //  Clear folder
//            else {
//                File::clear($dir);
//            }
//
//            // Удалим все файлы с расширением map
//            //foreach ( \samson\core\File::dir( getcwd(), 'dbs' ) as $file ) unlink( $file );
//
//            // Если еще не создан отпечаток базы данных - создадим его
//
//            // Сохраним классы БД
//            $db_classes = 'namespace samson\activerecord;';
//
//            $db_func = '';
//
//            // Создадим классы
//            foreach ($db_mapper as $table_name => $table_data) {
//                $file_full = $this->classes($table_data, $table_name, $virtualTable->table, $db_relations);
//                $db_classes .= $file_full[0];
//                $db_func .= $file_full[1];
//            }
//
//            // Создадим классы
//            foreach (self::$tables as $table_name => $table_data) {
//                $file_full = $this->classes(self::$tables[$table_name], $table_name, $table_name, $db_relations);
//                $db_classes .= $file_full[0];
//                $db_func .= $file_full[1];
//            }
//
//
//
//            // Подключим наш ХУК для АктивРекорда!!!!!
//            eval($db_classes);
//            eval($db_func);
//        } // Иначе просто его подключим
//        else {
//            include($md5_file);
//            include($md5_file_func);
//        }
//
//        //elapsed('end');
    }

    /**
     * Convert RecordInterface instance to collection of its field name => value,
     * returning only fields that needs to participate in SQL statements.
     * TODO: We need to generate this collection in entity class generation.
     *
     * @param RecordInterface $object Database record instance to convert
     * @return array Collection of key => value with SQL fields statements
     */
    protected function &getQueryFields(RecordInterface &$object = null)
    {
        $collection = array();
        foreach ($this->fieldsAndTypes as $attribute => $type) {
            if ($type == 'timestamp') {
                continue;
            } elseif ($this->primaryFieldName == $attribute) {
                continue;
            }

            $collection[$attribute] = $object->$attribute;
        }

        return $collection;
    }
}
