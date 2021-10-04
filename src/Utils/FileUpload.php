<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Utils;

use finfo;
use GraphQL\Error\Error;
use JsonException;

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

    public static function getMimetype(string $data): string
    {
        if (empty($data)) {
            return 'application/octet-stream';
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        /** @var string|bool $mimeType */
        $mimeType = $finfo->buffer($data);
        if ($mimeType === false) {
            return 'application/octet-stream';
        }
        return $mimeType;
    }


}