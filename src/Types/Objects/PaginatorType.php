<?php


namespace Mrap\GraphCool\Types\Objects;


use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use Mrap\GraphCool\Types\TypeLoader;

class PaginatorType extends ObjectType
{
    public function __construct(ModelType $type, TypeLoader $typeLoader)
    {
        $config = [
            'name' => '_' . $type->name . 'Paginator',
            'description' => 'A paginated list of ' . $type->name . ' items.',
            'fields' => [
                'paginatorInfo' => $typeLoader->load('_PaginatorInfo'),
                'data' => new ListOfType(new NonNull($type))
            ],
        ];
        parent::__construct($config);
    }
}