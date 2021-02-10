<?php


namespace Mrap\GraphCool\Types;


use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;

class EdgesType extends ObjectType
{
    public function __construct(string $key, ModelType $parentType, TypeLoader $typeLoader)
    {
        $config = [
            'name' => '_' . $parentType->name . '_' . $key . 'Edges',
            'description' => 'A paginated list of ' . $key . ' items.',
            'fields' => [
                'paginatorInfo' => $typeLoader->load('_PaginatorInfo'),
                'edges' => new ListOfType(new NonNull($typeLoader->load('_' . $parentType->name . '_' . $key . 'Edge', null, $parentType))),
            ],
        ];
        parent::__construct($config);
    }

}