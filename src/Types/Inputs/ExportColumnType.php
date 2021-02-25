<?php


namespace Mrap\GraphCool\Types\Inputs;


use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\Type;
use Mrap\GraphCool\Types\Objects\ModelType;
use Mrap\GraphCool\Types\TypeLoader;

class ExportColumnType extends InputObjectType
{

    public function __construct(ModelType $type, TypeLoader $typeLoader)
    {
        $fields = [
            'column' => new NonNull($typeLoader->load('_' . $type->name . 'Column')()),
            'label' => Type::string(),
        ];
        $config = [
            'name' => '_' . $type->name . 'ExportColumn',
            'fields' => $fields,
        ];
        parent::__construct($config);
    }

}