<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Inputs;

use GraphQL\Type\Definition\InputObjectType;
use Mrap\GraphCool\Types\Type;

class FileType extends InputObjectType
{

    public function __construct()
    {
        parent::__construct([
            'name' => '_File',
            'fields' => fn() => [
                'file' => Type::get('_Upload'),
                'filename' => Type::nonNull(Type::string()),
                'data_base64' => Type::string(),
            ],
        ]);
    }

}