<?php

declare(strict_types=1);

namespace Mrap\GraphCool\DataSource;

use Mrap\GraphCool\DataSource\Aws\AwsFileProvider;
use Mrap\GraphCool\DataSource\FileSystem\SystemFileProvider;
use Mrap\GraphCool\DataSource\Microservice\MicroserviceFileProvider;
use Mrap\GraphCool\Utils\Env;
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
    public static function write(string $name, array $data, array $args, string $type = 'xlsx', string $postFix = ''): stdClass
    {
        return self::getExporter()->export($name, $data, $args, $type, $postFix);
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
    public static function read(string $name, array $args): array
    {
        return self::getImporter()->import($name, $args);
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

    public static function store(string $name, string $id, string $key, array $file): stdClass
    {
        return static::getFileProvider()->store($name, $id, $key, $file);
    }

    public static function retrieve(string $name, string $id, string $key, string $value): stdClass
    {
        return static::getFileProvider()->retrieve($name, $id, $key, $value);
    }

    public static function delete(string $name, string $id, string $key, string $value): void
    {
        static::getFileProvider()->delete($name, $id, $key, $value);
    }

    public static function softDelete(string $name, string $id, string $key, string $value): void
    {
        static::getFileProvider()->softDelete($name, $id, $key, $value);
    }

    public static function restore(string $name, string $id, string $key, string $value): void
    {
        static::getFileProvider()->restore($name, $id, $key, $value);
    }

    public static function getToken(): string
    {
        return static::getFileProvider()->getToken();
    }

    /**
     * @codeCoverageIgnore
     */
    protected static function getFileProvider(): FileProvider
    {
        if (!isset(static::$storage)) {
            static::$storage = match(Env::get('FILE_PROVIDER')) {
                default => new SystemFileProvider(),
                'aws_bucket' => new AwsFileProvider(),
                'microservice' => new MicroserviceFileProvider(),
            };
        }
        return static::$storage;
    }

    public static function setFileProvider(FileProvider $storage): void
    {
        static::$storage = $storage;
    }

}
