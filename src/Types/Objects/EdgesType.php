<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Objects;

use GraphQL\Type\Definition\ObjectType;
use Mrap\GraphCool\Types\Type;

class EdgesType extends ObjectType
{
    public function __construct(string $name)
    {
        parent::__construct([
            'name' => $name,
            'description' => 'A paginated list of ' . substr($name, 1, -5) . ' relations.',
            'fields' => fn() => [
                'paginatorInfo' => Type::get('_PaginatorInfo'),
                'edges' => Type::listOf(Type::nonNull(Type::get(substr($name, 0, -1)))),
            ],
        ]);
    }

}
