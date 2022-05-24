<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Utils;

use GraphQL\Error\Error;
use JsonException;
use RuntimeException;
use stdClass;

class FileUpload
{

    public static function parse(array $request, string $mapJson): array
    {
        try {
            $map = json_decode($mapJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new Error('Could not parse file map - not a valid JSON. ' . $e->getMessage());
        }
        foreach ($map as $index => $paths) {
            foreach ($paths as $path) {
                $reference = &$request;
                foreach (explode('.', $path) as $key) {
                    if (!isset($reference[$key]) || !is_array($reference[$key])) {
                        $reference[$key] = [];
                    }
                    $reference = &$reference[$key];
                }
                if (!isset($_FILES[$index])) {
                    throw new Error('File map references a file that has not been uploaded.');
                }
                $reference = $_FILES[$index];
            }
        }
        return $request;
    }

    public static function getMimetype(?string $data): string
    {
        if (empty($data)) {
            return 'application/octet-stream';
        }
        $file = tempnam(sys_get_temp_dir(), 'file');
        if ($file === false) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException('Could not save temporary file.');
            // @codeCoverageIgnoreEnd
        }
        file_put_contents($file, $data);
        $mimeType = mime_content_type($file);
        unlink($file);
        return $mimeType;
    }

    public static function get(array $input): stdClass
    {
        $base64 = $input['data_base64'] ?? null;
        if ($base64 !== null && strlen($base64) === 0) {
            throw new Error('Received empty base64 string.');
        }
        if ($base64 !== null) {
            $file = tempnam(sys_get_temp_dir(), 'file');
            if ($file == false) {
                // @codeCoverageIgnoreStart
                throw new RuntimeException('Could not save temporary file.');
                // @codeCoverageIgnoreEnd
            }
            file_put_contents($file, base64_decode($base64));
        } else {
            $file = $input['file']['tmp_name'] ?? null;
            if ($file === null || !file_exists($file)) {
                throw new Error('Uploaded file could not be read.');
            }
            $base64 = base64_encode(file_get_contents($file));
        }
        $size = filesize($file);
        if ($size === 0) {
            // @codeCoverageIgnoreStart
            throw new Error('Received empty file.');
            // @codeCoverageIgnoreEnd
        }
        return (object)[
            'filename' => $input['filename'],
            'filesize' => $size,
            'mime_type' => mime_content_type($file),
            'data_base64' => $base64
        ];
    }


}