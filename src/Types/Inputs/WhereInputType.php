<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Inputs;

use GraphQL\Type\Definition\InputObjectType;
use Mrap\GraphCool\Types\Type;

class WhereInputType extends InputObjectType
{

    public function __construct(string $name)
    {
        $fields = [
            'column' => Type::get(substr($name, 0, -15) . 'Column'),
            'operator' => Type::get('_SQLOperator'),
            'value' => Type::get('Mixed'),
            'fulltextSearch' => Type::string(),
            'AND' => Type::listOf($this),
            'OR' => Type::listOf($this)
        ];
        $config = [
            'name' => $name,
            'fields' => $fields
        ];
        parent::__construct($config);
    }

}