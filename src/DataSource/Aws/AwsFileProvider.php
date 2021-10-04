<?php

declare(strict_types=1);

namespace Mrap\GraphCool\DataSource\Aws;

use Aws\S3\S3Client;
use GraphQL\Error\Error;
use JsonException;
use Mrap\GraphCool\DataSource\FileProvider;
use Mrap\GraphCool\Utils\FileUpload;
use stdClass;

class AwsFileProvider implements FileProvider
{
    protected S3Client $client;

    /**
     * @throws JsonException
     */
    public function store(string $key, array $input): ?string
    {
        $base64 = $input['data_base64'] ?? null;
        if ($base64 === null) {
            $tmpFile = $input['file']['tmp_name'] ?? null;
            $data = file_get_contents($tmpFile);
            if ($data === false) {
                throw new Error('Uploaded file could not be read.');
            }
            $base64 = base64_encode($data);
        }
        $result = $this->getClient()->putObject([
            'Bucket' => 'my-bucket',
            'Key' => $key,
            'Body' => $base64
        ]);
        // TODO: check result?
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
                return $this->loadData($key);
            }
        ];
    }

    public function delete(string $key): void
    {
        $this->getClient()->deleteObject([
            'Bucket' => 'my-bucket',
            'Key' => $key
        ]);
    }

    protected function loadData(string $key): string
    {
        if (!isset($this->data[$key])) {
            $result = $this->getClient()->getObject([
                'Bucket' => 'my-bucket',
                'Key' => $key
            ]);
            $this->data[$key] = $result['Body'];
        }
        return $this->data[$key];
    }

    protected function getClient(): S3Client
    {
        if (!isset($this->client)) {
            // AWS_ACCESS_KEY_ID
            // AWS_SECRET_ACCESS_KEY -> must be in environment -> $_ENV[]?
            $this->client = new S3Client([
                'version' => 'latest',
                'region' => 'eu-central-1'
            ]);
        }
        return $this->client;
    }

    public function setClient(S3Client $client): void
    {
        $this->client = $client;
    }
}