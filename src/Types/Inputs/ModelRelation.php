<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Inputs;

use GraphQL\Type\Definition\InputObjectType;
use Mrap\GraphCool\Definition\Model;
use Mrap\GraphCool\Definition\Relation;
use Mrap\GraphCool\Types\DynamicTypeTrait;
use Mrap\GraphCool\Types\Type;

class ModelRelation extends InputObjectType
{

    use DynamicTypeTrait;

    public static function prefix(): string
    {
        return '_';
    }

    public static function postfix(): string
    {
        return 'Relation';
    }

    public function __construct(Relation $relation)
    {
        parent::__construct([
            'name' => static::getFullName($relation->namekey),
            'description' => 'Input for ' . $relation->namekey . ' relations.',
            'fields' => fn() => $this->fieldConfig($relation),
        ]);
    }

    protected function fieldConfig(Relation $relation): array
    {
        $fields = [
            'id' => Type::nonNull(Type::id())
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
