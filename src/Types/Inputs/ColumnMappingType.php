<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Inputs;

use GraphQL\Type\Definition\InputObjectType;
use Mrap\GraphCool\Types\Type;

class ColumnMappingType extends InputObjectType
{

    public function __construct(string $name)
    {
        $fields = [
            'column' => Type::nonNull(Type::get(substr($name, 0, -13) . 'Column')),
            'label' => Type::string(),
        ];
        $config = [
            'name' => $name,
            'fields' => $fields,
        ];
        parent::__construct($config);
    }

}