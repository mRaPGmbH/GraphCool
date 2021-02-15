<?php


namespace Mrap\GraphCool\Types;


use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

class OrderByClauseType extends InputObjectType
{

    public function __construct(ModelType $type, TypeLoader $typeLoader)
    {
        $fields = [
            'field' => $typeLoader->load('_' . $type->name . 'Column')(),
            'order' => $typeLoader->load('_SortOrder')(),
        ];
        $config = [
            'name' => '_' . $type->name . 'OrderByClause',
            'fields' => $fields,
        ];
        parent::__construct($config);
    }

}