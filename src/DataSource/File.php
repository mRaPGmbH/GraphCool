<?php


namespace Mrap\GraphCool\DataSource;



use Mrap\GraphCool\Utils\FileExport;
use Mrap\GraphCool\Utils\FileImport;

class File
{
    protected static $exporter;
    protected static $importer;


    protected static function getExporter()
    {
        if (!isset(static::$exporter)) {
            //$classname = Helper::config('dataProvider');
            //if (!class_exists($classname)) {
            $classname = FileExport::class;
            //}
            static::$exporter = new $classname();
        }
        return static::$exporter;
    }

    public static function setExporter($exporter)
    {
        static::$exporter = $exporter;
    }

    protected static function getImporter()
    {
        if (!isset(static::$importer)) {
            //$classname = Helper::config('dataProvider');
            //if (!class_exists($classname)) {
            $classname = FileExport::class;
            //}
            static::$importer = new $classname();
        }
        return static::$importer;
    }

    public static function setImporter($importer)
    {
        static::$importer = $importer;
    }

    public static function export(string $name, array $data, array $args, string $type = 'xlsx'): \stdClass
    {
        return self::getExporter()->export($name, $data, $args, $type);
    }

    public static function import(string $tenantId, string $name, array $args): \stdClass
    {
        if (is_object(static::$importer)) { // hack for unit testing - remove me later
            return static::$importer->import($args);
        }
        $importer = new FileImport($tenantId, $name); // TODO: separation of concerns - import shouldn't access DB
        return $importer->import($args);
        //return self::getImporter()->import($name, $data, $args, $type);
    }


}