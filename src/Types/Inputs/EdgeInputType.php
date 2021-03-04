<?php
declare(strict_types=1);

namespace Mrap\GraphCool\Types\Inputs;


use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\Type;
use Mrap\GraphCool\Model\Field;
use Mrap\GraphCool\Model\Model;
use Mrap\GraphCool\Types\Objects\ModelType;
use Mrap\GraphCool\Types\TypeLoader;

class EdgeInputType extends InputObjectType
{

    public function __construct(string $key, ModelType $parentType, TypeLoader $typeLoader)
    {
        $classname = 'App\\Models\\' . $parentType->name;
        $model = new $classname();
        $relation = $model->$key;
        $fields = [
            'id' => new NonNull(Type::id())
        ];
        foreach ($relation as $fieldKey => $field)
        {
            if ($field instanceof Field) {
                $fieldType = $typeLoader->loadForField($field, $fieldKey);
                if ($field->null === false) {
                    $fieldType = new NonNull($fieldType);
                }
                $fields[$fieldKey] = [
                    'type' => $fieldType
                ];
            }
        }
        $config = [
            'name' => '_' . $parentType->name . '_' . $key . 'Relation',
            'description' => 'Input for one related ' . $key . ' item.',
            'fields' => $fields,
        ];
        parent::__construct($config);
    }


}