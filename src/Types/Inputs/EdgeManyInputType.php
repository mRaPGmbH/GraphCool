<?php
declare(strict_types=1);

namespace Mrap\GraphCool\Types\Inputs;


use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\Type;
use Mrap\GraphCool\Model\Field;
use Mrap\GraphCool\Model\Relation;
use Mrap\GraphCool\Types\TypeLoader;

class EdgeManyInputType extends InputObjectType
{

    public function __construct(string $name, TypeLoader $typeLoader)
    {
        $names = explode('__', substr($name, 1, -12), 2);
        $key = $names[1];

        $classname = 'App\\Models\\' . $names[0];
        $model = new $classname();
        /** @var Relation $relation */
        $relation = $model->$key;

        $fields = [
            'where' => new NonNull($typeLoader->load('_' . $relation->name . 'WhereConditions')),
            'search' => Type::string(),
            'mode' => $typeLoader->load('_RelationUpdateMode'),
        ];
        foreach ($relation as $fieldKey => $field)
        {
            if ($field instanceof Field && $field->readonly === false) {
                $fieldType = $typeLoader->loadForField($field, $names[0] . '__' . $key . '__' . $fieldKey);
                if ($field->null === false) {
                    $fieldType = new NonNull($fieldType);
                }
                $fields[$fieldKey] = [
                    'type' => $fieldType
                ];
            }
        }
        $config = [
            'name' => $name,
            'description' => 'Input for many ' . $names[0] . '.' . $key . ' relations using where.',
            'fields' => $fields,
        ];
        parent::__construct($config);
    }


}