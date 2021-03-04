<?php
declare(strict_types=1);

namespace Mrap\GraphCool\Types\Objects;


use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use Mrap\GraphCool\Types\TypeLoader;

class PaginatorType extends ObjectType
{
    public function __construct(string $name, TypeLoader $typeLoader)
    {
        $typeName = substr($name, 1,-9);
        $config = [
            'name' => $name,
            'description' => 'A paginated list of ' . $typeName . ' items.',
            'fields' => [
                'paginatorInfo' => $typeLoader->load('_PaginatorInfo'),
                'data' => new ListOfType(new NonNull($typeLoader->load($typeName)))
            ],
        ];
        parent::__construct($config);
    }
}