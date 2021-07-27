<?php

declare(strict_types=1);

namespace Mrap\GraphCool\DataSource;

use Mrap\GraphCool\Utils\FileExport;
use Mrap\GraphCool\Utils\FileImport2;
use stdClass;

class File
{
    protected static FileExport $exporter;
    protected static FileImport2 $importer;

    public static function write(string $name, array $data, array $args, string $type = 'xlsx'): stdClass
    {
        return self::getExporter()->export($name, $data, $args, $type);
    }

    /**
     * @codeCoverageIgnore
     */
    protected static function getExporter(): FileExport
    {
        if (!isset(static::$exporter)) {
            static::$exporter = new FileExport();
        }
        return static::$exporter;
    }

    public static function setExporter(FileExport $exporter)
    {
        static::$exporter = $exporter;
    }

    public static function read(string $name, array $args, int $index): array
    {
        return self::getImporter()->import($name, $args, $index);
    }

    /**
     * @codeCoverageIgnore
     */
    protected static function getImporter(): FileImport2
    {
        if (!isset(static::$importer)) {
            static::$importer = new FileImport2();
        }
        return static::$importer;
    }

    public static function setImporter($importer)
    {
        static::$importer = $importer;
    }

}