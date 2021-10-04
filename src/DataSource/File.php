<?php

declare(strict_types=1);

namespace Mrap\GraphCool\DataSource;

use Mrap\GraphCool\DataSource\FileSystem\SystemFileProvider;
use Mrap\GraphCool\Utils\FileExport;
use Mrap\GraphCool\Utils\FileImport2;
use stdClass;

class File
{
    protected static FileExport $exporter;
    protected static FileImport2 $importer;
    protected static FileProvider $storage;

    /**
     * @param string $name
     * @param mixed[] $data
     * @param mixed[] $args
     * @param string $type
     * @return stdClass
     * @throws \Box\Spout\Common\Exception\IOException
     * @throws \Box\Spout\Writer\Exception\WriterNotOpenedException
     */
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

    public static function setExporter(FileExport $exporter): void
    {
        static::$exporter = $exporter;
    }

    /**
     * @param string $name
     * @param mixed[] $args
     * @param int $index
     * @return array[]
     * @throws \Box\Spout\Reader\Exception\ReaderNotOpenedException
     * @throws \GraphQL\Error\Error
     */
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

    public static function setImporter(FileImport2 $importer): void
    {
        static::$importer = $importer;
    }

    public static function store(string $key, array $file): ?string
    {
        return static::getFileProvider()->store($key, $file);
    }

    public static function retrieve(string $key, string $value): stdClass
    {
        return static::getFileProvider()->retrieve($key, $value);
    }

    public static function delete(string $key): void
    {
        static::getFileProvider()->delete($key);
    }

    public static function getFileProvider(): FileProvider
    {
        if (!isset(static::$storage)) {
            //static::$storage = new AwsFileProvider();
            static::$storage = new SystemFileProvider();
        }
        return static::$storage;
    }

    public static function setFileProvider(FileProvider $storage): void
    {
        static::$storage = $storage;
    }

}