<?php

declare(strict_types=1);

namespace Mrap\GraphCool\DataSource\Microservice;

use Mrap\GraphCool\DataSource\FileProvider;
use Mrap\GraphCool\Utils\Env;
use Mrap\GraphCool\Utils\FileUpload;
use stdClass;

class MicroserviceFileProvider implements FileProvider
{

    protected array $cache;

    public function store(string $name, string $id, string $key, array $input): stdClass
    {
        $file = FileUpload::get($input);
        $result = Microservice::endpoint('file:mutation:createFile')
            ->authorization($_SERVER['HTTP_AUTHORIZATION'])
            ->paramString('service', Env::get('APP_NAME'))
            ->paramString('model', $name)
            ->paramString('model_id', $id)
            ->paramString('property', $key)
            ->paramRawValue('file', $this->getParamValue($file))
            ->fields(['id'])
            ->call();
        $file->id = $result->id;
        $this->cache[$result->id] = $file;
        return $file;
    }

    public function retrieve(string $name, string $id, string $key, string $value): ?stdClass
    {
        if (empty($value)) {
            return null;
        }
        if (isset($this->cache[$value])) {
            return $this->cache[$value];
        }
        $result = Microservice::endpoint('file:query:file')
            ->authorization($_SERVER['HTTP_AUTHORIZATION'])
            ->paramString('id', $value)
            ->fields(['id', 'filesize', 'file' => ['data_base64'], 'filename', 'mime_type'])
            ->call();
        if ($result !== null) {
            $result->data_base64 = $result->file->data_base64 ?? null;
        }
        return $result;
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

    protected function getParamValue(stdClass $file): string
    {
        return '{filename: "'.str_replace('"', '', $file->filename).'" data_base64: "'.$file->data_base64.'"}';
    }

}