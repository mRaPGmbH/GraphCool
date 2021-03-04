<?php
declare(strict_types=1);

namespace Mrap\GraphCool\Types\Inputs;


use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use Mrap\GraphCool\Types\Objects\ModelType;
use Mrap\GraphCool\Types\TypeLoader;

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