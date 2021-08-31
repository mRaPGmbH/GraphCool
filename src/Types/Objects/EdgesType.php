<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Objects;

use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use Mrap\GraphCool\Types\TypeLoader;

class EdgesType extends ObjectType
{
    public function __construct(string $name, TypeLoader $typeLoader)
    {
        $config = [
            'name' => $name,
            'description' => 'A paginated list of ' . substr($name, 1, -5) . ' relations.',
            'fields' => [
                'paginatorInfo' => $typeLoader->load('_PaginatorInfo'),
                'edges' => new ListOfType(new NonNull($typeLoader->load(substr($name, 0, -1)))),
            ],
        ];
        parent::__construct($config);
    }

}