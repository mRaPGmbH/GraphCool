<?php


namespace Mrap\GraphCool\Types;


use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;

class PaginatorType extends ObjectType
{
    public function __construct(string $name, TypeLoader $typeLoader, ?ModelType $subType = null)
    {
        if ($subType === null) {
            $subType = $typeLoader->load(substr($name, 0, -9))();
        }
        $config = [
            'name' => $name,
            'description' => 'A paginated list of ' . $subType->name . ' items.',
            'fields' => [
                'paginatorInfo' => $typeLoader->load('paginatorInfo'),
                'data' => new ListOfType(new NonNull($subType))
            ],
        ];
        parent::__construct($config);
    }

}