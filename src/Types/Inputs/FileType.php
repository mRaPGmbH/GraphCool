<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Inputs;

use GraphQL\Type\Definition\InputObjectType;
use Mrap\GraphCool\Types\Type;

class FileType extends InputObjectType
{

    public function __construct()
    {
        $fields = [
            'file' => Type::get('_Upload'),
            'filename' => Type::nonNull(Type::string()),
            'data_base64' => Type::string(),
        ];
        $config = [
            'name' => '_File',
            'fields' => $fields,
        ];
        parent::__construct($config);
    }

}