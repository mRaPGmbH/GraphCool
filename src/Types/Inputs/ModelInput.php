<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Inputs;

use GraphQL\Type\Definition\InputObjectType;
use Mrap\GraphCool\Definition\Relation;
use Mrap\GraphCool\Types\Type;
use function Mrap\GraphCool\model;

class ModelInput extends InputObjectType
{

    public function __construct(string $wrappedType)
    {
        parent::__construct([
            'name' => '_' . $wrappedType . 'Input',
            'description' => 'Input for creating or updating a ' . $wrappedType . '.',
            'fields' => fn() => $this->fieldConfig($wrappedType),
        ]);
    }

    protected function fieldConfig(string $name): array
    {
        $model = model($name);
        $fields = [];
        foreach ($model->fields() as $key => $field) {
            if ($field->readonly === false) {
                $fields[$key] = Type::getForField($field, true, true);
            }
        }
        foreach ($model->relations() as $key => $relation) {
            if ($relation->type === Relation::BELONGS_TO) {
                $fields[$key] = Type::get('_' . $name . '__' . $key . 'Relation');
            }
            if ($relation->type === Relation::BELONGS_TO_MANY) {
                $fields[$key] = Type::listOf(
                    Type::nonNull(Type::get('_' . $name . '__' . $key . 'ManyRelation'))
                );
            }
        }
        ksort($fields);
        return $fields;
    }


}
