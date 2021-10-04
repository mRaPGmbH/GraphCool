<?php

declare(strict_types=1);

namespace Mrap\GraphCool\DataSource\FileSystem;

use GraphQL\Error\Error;
use JsonException;
use Mrap\GraphCool\DataSource\FileProvider;
use Mrap\GraphCool\Utils\ClassFinder;
use Mrap\GraphCool\Utils\Env;
use Mrap\GraphCool\Utils\FileUpload;
use RuntimeException;
use stdClass;

class SystemFileProvider implements FileProvider
{
    protected string $path;
    protected array $data;

    /**
     * @throws JsonException
     */
    public function store(string $key, array $input): ?string
    {
        $base64 = $input['data_base64'] ?? null;
        if ($base64 !== null) {
            $data = base64_decode($base64);
        } else {
            $tmpFile = $input['file']['tmp_name'] ?? null;
            $data = file_get_contents($tmpFile);
        }
        if ($data === false) {
            throw new Error('Uploaded file could not be read.');
        }
        file_put_contents($this->filename($key), $data);
        return $input['filename'];
    }

    /**
     * @throws JsonException
     */
    public function retrieve(string $key, string $value): stdClass
    {
        return (object) [
            'filename' => $value,
            'mime_type' => function() use ($key) {
                return FileUpload::getMimetype($this->loadData($key));
            },
            'data_base64' => function() use ($key) {
                return base64_encode($this->loadData($key));
            }
        ];
    }

    public function delete(string $key): void
    {
        $filename = $this->filename($key);
        if (file_exists($filename)) {
            unlink($filename);
        }
    }

    protected function loadData(string $key): string
    {
        if (!isset($this->data[$key])) {
            $this->data[$key] = file_get_contents($this->filename($key));
        }
        return $this->data[$key];
    }

    protected function filename(string $key): string
    {
        $parts = explode('.', $key);
        $path = $this->getPath() . '/'
            . $parts[0] . '/'
            . substr($parts[1], 0, 3) . '/'
            . substr($parts[1], 3, 3);
        if (!file_exists($path) && !mkdir($path, 0777, true) && !is_dir($path)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $path));
        }
        return $path . '/' . $key;
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
        if (!file_exists($path) && !mkdir($path, 0777, true) && !is_dir($path)) {
            throw new RuntimeException(sprintf('Directory "%s" could not be created', $path));
        }
        $this->path = $path;
    }
}