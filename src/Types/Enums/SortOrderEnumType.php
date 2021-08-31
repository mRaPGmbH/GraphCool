<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Enums;

use GraphQL\Type\Definition\EnumType;

class SortOrderEnumType extends EnumType
{

    public function __construct()
    {
        $config = [
            'name' => '_SortOrder',
            'description' => 'The available direction for ordering a list of records',
            'values' => [
                'ASC' => ['value' => 'ASC', 'description' => 'Sort records in ascending order.'],
                'DESC' => ['value' => 'DESC', 'description' => 'Sort records in descending order.'],
                'RAND' => ['value' => 'RAND', 'description' => 'Sort records in random order.']
            ]
        ];
        parent::__construct($config);
    }
}