<?php

declare(strict_types=1);

namespace Mrap\GraphCool\DataSource\FileSystem;

use GraphQL\Error\Error;
use Mrap\GraphCool\DataSource\FileProvider;
use Mrap\GraphCool\Utils\ClassFinder;
use Mrap\GraphCool\Utils\Env;
use Mrap\GraphCool\Utils\FileUpload;
use RuntimeException;
use stdClass;

class SystemFileProvider implements FileProvider
{
    protected string $path;

    /**
     * @throws Error
     */
    public function store(string $name, string $id, string $key, array $input): stdClass
    {
        $file = FileUpload::get($input);
        $file->id = $file->filename;
        file_put_contents($this->filename($name, $id, $key), base64_decode($file->data_base64));
        return $file;
    }

    public function retrieve(string $name, string $id, string $key, string $value): ?stdClass
    {
        $filename = $this->filename($name, $id, $key);
        if (!file_exists($filename)) {
            return null;
        }
        return (object) [
            'filename' => $value,
            'mime_type' => function() use ($filename) {
                return mime_content_type($filename);
            },
            'url' => '/' . Env::get('APP_NAME') . '/download/' . $name . '.' . $id . '.' . $key,
            'data_base64' => function() use ($filename) {
                return base64_encode(file_get_contents($filename));
            }
        ];
    }

    public function delete(string $name, string $id, string $key, string $value): void
    {
        $filename = $this->filename($name, $id, $key);
        if (file_exists($filename)) {
            unlink($filename);
        }
    }

    protected function filename(string $name, string $id, string $key): string
    {
        $path = $this->getPath() . '/'
            . $name . '/'
            . substr($id, 0, 3) . '/'
            . substr($id, 3, 3);
        if (!file_exists($path) && !@mkdir($path, 0777, true) && !is_dir($path)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $path));
        }
        return $path . '/' . $name . '.' . $id . '.' . $key;
    }

    protected function getPath(): string
    {
        if (!isset($this->path)) {
            $this->setPath(Env::get('STORAGE_PATH') ?? (ClassFinder::rootPath().'/storage/files'));
        }
        return $this->path;
    }

    public function setPath(string $path): void
    {
        if (!file_exists($path) && !@mkdir($path, 0777, true) && !is_dir($path)) {
            throw new RuntimeException(sprintf('Directory "%s" could not be created', $path));
        }
        $this->path = $path;
    }
}