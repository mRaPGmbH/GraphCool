<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Inputs;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\Type;
use Mrap\GraphCool\Types\TypeLoader;

class FileType extends InputObjectType
{

    public function __construct(TypeLoader $typeLoader)
    {
        $fields = [
            'file' => $typeLoader->load('_Upload')(),
            'filename' => new NonNull(Type::string()),
            'data_base64' => Type::string(),
        ];
        $config = [
            'name' => '_File',
            'fields' => $fields,
        ];
        parent::__construct($config);
    }

}