<?php

namespace Mrap\GraphCool\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Mrap\GraphCool\Definition\Model;
use Mrap\GraphCool\Definition\Relation;
use function Mrap\GraphCool\model;

class BaseType extends ObjectType
{

    protected function exportArgs(string $name, Model $model, TypeLoader $typeLoader): array
    {
        $args = [
            'type' => Type::nonNull($typeLoader->load('_ExportFile')),
            'where' => $typeLoader->load('_' . $name . 'WhereConditions'),
            'orderBy' => Type::listOf(Type::nonNull($typeLoader->load('_' . $name . 'OrderByClause'))),
            'search' => Type::string(),
            'searchLoosely' => Type::string(),
            'columns' => Type::nonNull(Type::listOf(Type::nonNull($typeLoader->load('_' . $name . 'ColumnMapping')))),
        ];

        foreach (get_object_vars($model) as $key => $relation) {
            if (!$relation instanceof Relation) {
                continue;
            }
            if ($relation->type === Relation::BELONGS_TO || $relation->type === Relation::HAS_ONE) {
                $args[$key] = Type::listOf(
                    Type::nonNull($typeLoader->load('_' . $name . '__' . $key . 'EdgeColumnMapping'))
                );
            }
            if ($relation->type === Relation::BELONGS_TO_MANY) {
                $args[$key] = Type::listOf(
                    Type::nonNull($typeLoader->load('_' . $name . '__' . $key . 'EdgeSelector'))
                );
            }
            $args['where' . ucfirst($key)] = $typeLoader->load('_' . $relation->name . 'WhereConditions');
        }
        $args['result'] = $typeLoader->load('_Result');
        $args['_timezone'] = $typeLoader->load('_TimezoneOffset');
        return $args;
    }

    protected function importArgs(string $name, TypeLoader $typeLoader): array
    {
        $args = [
            'file' => $typeLoader->load('_Upload'),
            'data_base64' => Type::string(),
            'columns' => Type::nonNull(Type::listOf(Type::nonNull($typeLoader->load('_' . $name . 'ColumnMapping')))),
            '_timezone' => $typeLoader->load('_TimezoneOffset'),
        ];
        $model = model($name);
        foreach ($model as $key => $relation) {
            if (!$relation instanceof Relation) {
                continue;
            }
            if ($relation->type === Relation::BELONGS_TO_MANY) {
                $args[$key] = Type::listOf(
                    type::nonNull($typeLoader->load('_' . $name . '__' . $key . 'EdgeReducedSelector'))
                );
            }
        }
        return $args;
    }


}