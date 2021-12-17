<?php

declare(strict_types=1);

namespace Mrap\GraphCool\DataSource\Microservice;

use Mrap\GraphCool\DataSource\FileProvider;
use Mrap\GraphCool\Utils\Env;
use Mrap\GraphCool\Utils\FileUpload;
use stdClass;

class MicroserviceFileProvider implements FileProvider
{

    protected array $cache = [];

    public function store(string $name, string $id, string $key, array $input): stdClass
    {
        $file = FileUpload::get($input);
        $result = Microservice::endpoint('file:mutation:createFile')
            ->setTimeout(30)
            ->authorization($_SERVER['HTTP_AUTHORIZATION'])
            ->paramString('service', Env::get('APP_NAME'))
            ->paramString('model', $name)
            ->paramString('model_id', $id)
            ->paramString('property', $key)
            ->paramRawValue('file', $this->getParamValue($file))
            ->fields(['id', 'filesize', 'file' => ['url']])
            ->call();
        $file->id = $result->id;
        $file->filesize = $result->filesize ?? 0;
        $file->url = $result->file->url ?? '';
        $this->cache[$result->id] = $file;
        return $file;
    }

    public function retrieve(string $name, string $id, string $key, string $value): ?stdClass
    {
        return (object)[
            'filename' => function() use($value) {return $this->getField('filename', $value);},
            'filesize' => function() use($value) {return $this->getField('filesize', $value);},
            'mime_type' => function() use($value) {return $this->getField('mime_type', $value);},
            'url' => function() use($value) {return $this->getField('url', $value);},
        ];
    }

    protected function getField(string $field, string $id): mixed
    {
        if (!array_key_exists($id, $this->cache)) {
            $this->fetch($id);
        }
        return $this->cache[$id]->$field ?? null;
    }

    protected function fetch(string $id): void
    {
        $result = Microservice::endpoint('file:query:file')
            ->authorization($_SERVER['HTTP_AUTHORIZATION'])
            ->paramString('id', $id)
            ->fields(['id', 'filesize', 'file' => ['url'], 'filename', 'mime_type'])
            ->call();
        if ($result !== null) {
            $result->url = $result->file->url ?? '';
            $result->data_base64 = $result->file->data_base64 ?? null;
        }
        $this->cache[$id] = $result;
    }

    public function delete(string $name, string $id, string $key, string $value): void
    {
        if (empty($value)) {
            return;
        }
        Microservice::endpoint('file:mutation:deleteFile')
            ->authorization($_SERVER['HTTP_AUTHORIZATION'])
            ->paramString('id', $value)
            ->fields(['id'])
            ->call();
    }

    public function softDelete(string $name, string $id, string $key, string $value): void
    {
        $this->delete($name, $id, $key, $value);
    }

    public function restore(string $name, string $id, string $key, string $value): void
    {
        if (empty($value)) {
            return;
        }
        Microservice::endpoint('file:mutation:restoreFile')
            ->authorization($_SERVER['HTTP_AUTHORIZATION'])
            ->paramString('id', $value)
            ->fields(['id'])
            ->call();
    }

    protected function getParamValue(stdClass $file): string
    {
        return '{filename: "'.str_replace('"', '', $file->filename).'" data_base64: "'.$file->data_base64.'"}';
    }

}