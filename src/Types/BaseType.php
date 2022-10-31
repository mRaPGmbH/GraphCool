<?php

namespace Mrap\GraphCool\Types;

use GraphQL\Type\Definition\ObjectType;
use Mrap\GraphCool\Definition\Model;
use Mrap\GraphCool\Definition\Relation;
use function Mrap\GraphCool\model;

class BaseType extends ObjectType
{

    protected function exportArgs(string $name, Model $model): array
    {
        $args = [
            'type' => Type::nonNull(Type::get('_ExportFile')),
            'where' => Type::get('_' . $name . 'WhereConditions'),
            'orderBy' => Type::listOf(Type::nonNull(Type::get('_' . $name . 'OrderByClause'))),
            'search' => Type::string(),
            'searchLoosely' => Type::string(),
            'columns' => Type::nonNull(Type::listOf(Type::nonNull(Type::get('_' . $name . 'ColumnMapping')))),
        ];

        foreach (get_object_vars($model) as $key => $relation) {
            if (!$relation instanceof Relation) {
                continue;
            }
            if ($relation->type === Relation::BELONGS_TO || $relation->type === Relation::HAS_ONE) {
                $args[$key] = Type::listOf(
                    Type::nonNull(Type::get('_' . $name . '__' . $key . 'EdgeColumnMapping'))
                );
            }
            if ($relation->type === Relation::BELONGS_TO_MANY) {
                $args[$key] = Type::listOf(
                    Type::nonNull(Type::get('_' . $name . '__' . $key . 'EdgeSelector'))
                );
            }
            $args['where' . ucfirst($key)] = Type::get('_' . $relation->name . 'WhereConditions');
        }
        $args['result'] = Type::get('_Result');
        $args['_timezone'] = Type::get('_TimezoneOffset');
        return $args;
    }

    protected function importArgs(string $name): array
    {
        $args = [
            'file' => Type::get('_Upload'),
            'data_base64' => Type::string(),
            'columns' => Type::nonNull(Type::listOf(Type::nonNull(Type::get('_' . $name . 'ColumnMapping')))),
            '_timezone' => Type::get('_TimezoneOffset'),
        ];
        $model = model($name);
        foreach ($model as $key => $relation) {
            if (!$relation instanceof Relation) {
                continue;
            }
            if ($relation->type === Relation::BELONGS_TO_MANY) {
                $args[$key] = Type::listOf(
                    Type::nonNull(Type::get('_' . $name . '__' . $key . 'EdgeReducedSelector'))
                );
            }
        }
        return $args;
    }


}