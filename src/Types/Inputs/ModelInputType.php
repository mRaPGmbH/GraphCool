<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Inputs;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use Mrap\GraphCool\Definition\Field;
use Mrap\GraphCool\Definition\Relation;
use Mrap\GraphCool\Types\TypeLoader;

class ModelInputType extends InputObjectType
{

    public function __construct(string $name, TypeLoader $typeLoader)
    {
        $shortname = substr($name, 1, -5);
        $classname = 'App\\Models\\' . $shortname;
        $model = new $classname();

        $fields = [];
        foreach ($model as $key => $field) {
            if ($field instanceof Relation) {
                $relation = $field;
                if ($relation->type === Relation::BELONGS_TO) {
                    $fields[$key] = $typeLoader->load('_' . $shortname . '__' . $key . 'Relation');
                }
                if ($relation->type === Relation::BELONGS_TO_MANY) {
                    $fields[$key] = new ListOfType(
                        new NonNull($typeLoader->load('_' . $shortname . '__' . $key . 'ManyRelation'))
                    );
                }
            }

            if (!$field instanceof Field) {
                continue;
            }
            if ($field->readonly === false) {
                $fields[$key] = $typeLoader->loadForField($field, $shortname . '__' . $key);
            }
        }
        ksort($fields);
        $config = [
            'name' => $name,
            'description' => 'Input for creating or updating a ' . $shortname . '.',
            'fields' => $fields,
        ];
        parent::__construct($config);
    }


}