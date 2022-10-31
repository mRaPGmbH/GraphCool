<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Inputs;

use GraphQL\Type\Definition\InputObjectType;
use Mrap\GraphCool\Types\Type;

class OrderByClauseType extends InputObjectType
{

    public function __construct(string $name)
    {
        $fields = [
            'field' => Type::get(substr($name, 0, -13) . 'Column'),
            'order' => Type::get('_SortOrder'),
        ];
        $config = [
            'name' => $name,
            'fields' => $fields,
        ];
        parent::__construct($config);
    }

}