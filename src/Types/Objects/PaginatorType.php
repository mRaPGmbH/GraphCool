<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Objects;

use GraphQL\Type\Definition\ObjectType;
use Mrap\GraphCool\Types\Type;

class PaginatorType extends ObjectType
{
    public function __construct(string $name)
    {
        $typeName = substr($name, 1, -9);
        if (str_ends_with($typeName, '_Job')) {
            $typeName = '_'. substr($typeName, 0, -4) . 'Job';
        }
        if ($typeName === 'History_') {
            $typeName = '_History';
        }
        $config = [
            'name' => $name,
            'description' => 'A paginated list of ' . $typeName . ' items.',
            'fields' => [
                'paginatorInfo' => Type::get('_PaginatorInfo'),
                'data' => Type::listOf(Type::nonNull(Type::get($typeName)))
            ],
        ];
        parent::__construct($config);
    }
}