<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Enums;

use GraphQL\Type\Definition\EnumType;
use Mrap\GraphCool\Types\StaticTypeTrait;

class SortOrder extends EnumType
{

    use StaticTypeTrait;

    public static function staticName(): string
    {
        return '_SortOrder';
    }

    public function __construct()
    {
        $config = [
            'name' => static::getFullName(),
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
