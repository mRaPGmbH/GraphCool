<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Objects;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class FileExportType extends ObjectType
{
    public function __construct()
    {
        parent::__construct([
            'name' => '_FileExport',
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
