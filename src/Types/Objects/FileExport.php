<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Objects;

use GraphQL\Type\Definition\ObjectType;
use Mrap\GraphCool\Types\StaticTypeTrait;
use Mrap\GraphCool\Types\Type;

class FileExport extends ObjectType
{
    use StaticTypeTrait;

    public static function staticName(): string
    {
        return '_FileExport';
    }

    public function __construct()
    {
        parent::__construct([
            'name' => static::getFullName(),
            'description' => 'An exported file in base64 encoding',
            'fields' => fn() => [
                'filename' => Type::string(),
                'mime_type' => Type::string(),
                'data_base64' => Type::string(),
                'url' => Type::string(),
                'filesize' => Type::int(),
            ],
        ]);
    }

}
