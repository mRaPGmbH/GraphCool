<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Objects;

use GraphQL\Type\Definition\ObjectType;
use Mrap\GraphCool\Definition\Model;
use Mrap\GraphCool\Definition\Relation;
use Mrap\GraphCool\Types\DynamicTypeTrait;
use Mrap\GraphCool\Types\Type;

class ModelEdge extends ObjectType
{

    use DynamicTypeTrait;

    public static function prefix(): string
    {
        return '_';
    }

    public static function postfix(): string
    {
        return 'Edge';
    }

    public function __construct(Relation $relation)
    {
        parent::__construct([
            'name' => static::getFullName($relation->namekey),
            'description' => 'A single ' . $relation->namekey . ' relation.',
            'fields' => fn() => $this->fieldConfig($relation),
        ]);
    }

    protected function fieldConfig(Relation $relation): array
    {
        $type = Type::model($relation->name);
        $fields = [];
        foreach (Model::relationFieldsForRelation($relation) as $fieldKey => $field) {
            $fields[$fieldKey] = [
                'type' => Type::getForField($field),
            ];
        }
        $fields['_node'] = $type;
        return $fields;
    }

}
