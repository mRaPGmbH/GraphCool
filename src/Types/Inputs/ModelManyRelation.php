<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Inputs;

use GraphQL\Type\Definition\InputObjectType;
use Mrap\GraphCool\Definition\Model;
use Mrap\GraphCool\Definition\Relation;
use Mrap\GraphCool\Types\Type;
use function Mrap\GraphCool\model;

class ModelManyRelation extends InputObjectType
{

    public function __construct(Relation $relation)
    {
        parent::__construct([
            'name' => '_' . $relation->namekey . 'ManyRelation',
            'description' => 'Input for many ' . $relation->namekey . ' relations using where.',
            'fields' => fn() => $this->fieldConfig($relation),
        ]);
    }

    protected function fieldConfig(Relation $relation): array
    {
        $fields = [
            'where' => Type::nonNull(Type::get('_' . $relation->name . 'WhereConditions')),
            'search' => Type::string(),
            'searchLoosely' => Type::string(),
            'mode' => Type::relationUpdateModeEnum(),
        ];
        foreach (Model::relationFieldsForRelation($relation) as $fieldKey => $field) {
            if ($field->readonly === false) {
                $fields[$fieldKey] = [
                    'type' => Type::getForField($field, true),
                ];
            }
        }
        return $fields;
    }


}
