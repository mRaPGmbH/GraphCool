<?php

declare(strict_types=1);

namespace Mrap\GraphCool\DataSource\Aws;

use Aws\S3\S3Client;
use GuzzleHttp\Psr7\Stream;
use Mrap\GraphCool\DataSource\FileProvider;
use Mrap\GraphCool\Utils\Env;
use Mrap\GraphCool\Utils\FileUpload;
use stdClass;

class AwsFileProvider implements FileProvider
{
    protected S3Client $client;

    public function store(string $name, string $id, string $key, array $input): stdClass
    {
        $file = FileUpload::get($input);
        $result = $this->getClient()->putObject([
            'Bucket' => $this->bucket(),
            'Key' => $this->key($name, $id, $key),
            'Body' => $file->data_base64
        ]);
        // TODO: check result?
        $file->id = $input['filename'];
        return $file;
    }

    public function retrieve(string $name, string $id, string $key, string $value): ?stdClass
    {
        $fileKey = $this->key($name, $id, $key);
        return (object) [
            'filename' => $value,
            'mime_type' => function() use ($fileKey) {
                return FileUpload::getMimetype($this->loadData($fileKey));
            },
            'data_base64' => function() use ($fileKey) {
                return $this->loadData($fileKey);
            }
        ];
    }

    public function delete(string $name, string $id, string $key, string $value): void
    {
        $this->getClient()->deleteObject([
            'Bucket' => $this->bucket(),
            'Key' => $this->key($name, $id, $key),
        ]);
    }

    protected function key(string $name, string $id, string $key): string
    {
        return $name.'.'.$id.'.'.$key;
    }

    protected function loadData(string $key): string
    {
        if (!isset($this->data[$key])) {
            $result = $this->getClient()->getObject([
                'Bucket' => $this->bucket(),
                'Key' => $key
            ]);
            /** @var Stream $stream */
            $stream = $result['Body'];
            $this->data[$key] = $stream->getContents();
        }
        return $this->data[$key];
    }

    /**
     * @codeCoverageIgnore
     */
    protected function getClient(): S3Client
    {
        if (!isset($this->client)) {
            $this->client = new S3Client([
                'version' => 'latest',
                'region' => Env::get('AWS_REGION'),
                'credentials' => [
                    'key'    => Env::get('AWS_ACCESS_KEY_ID'),
                    'secret' => Env::get('AWS_SECRET_ACCESS_KEY'),
                ],
            ]);
        }
        return $this->client;
    }

    public function setClient(S3Client $client): void
    {
        $this->client = $client;
    }

    protected function bucket(): string
    {
        return Env::get('AWS_BUCKET_NAME', 'GraphCool-Uploaded-Files');
    }
}