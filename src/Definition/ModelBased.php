<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Definition;

use Mrap\GraphCool\Types\Type;
use function Mrap\GraphCool\model;

trait ModelBased
{
    protected function exportArgs(string $name): array
    {
        $model = model($name);
        $args = [
            'type' => Type::nonNull(Type::sheetFileEnum()),
            'where' => Type::whereConditions($name),
            'orderBy' => Type::listOf(Type::nonNull(Type::orderByClause($name ))),
            'search' => Type::string(),
            'searchLoosely' => Type::string(),
            'columns' => Type::nonNull(Type::listOf(Type::nonNull(Type::columnMapping($name)))),
        ];
        foreach ($model->relations([Relation::BELONGS_TO, Relation::HAS_ONE]) as $key => $relation) {
            $args[$key] = Type::listOf(Type::nonNull(Type::columnMapping($relation)));
        }
        foreach ($model->relations([Relation::BELONGS_TO_MANY]) as $key => $relation) {
            $args[$key] = Type::listOf(Type::nonNull(Type::edgeSelector($relation)));
        }
        foreach ($model->relations() as $key => $relation) {
            $args['where' . ucfirst($key)] = Type::whereConditions($relation->name);
        }
        $args['result'] = Type::result();
        $args['_timezone'] = Type::timezoneOffset();
        return $args;
    }

    protected function importArgs(string $name): array
    {
        $args = [
            'file' => Type::upload(),
            'data_base64' => Type::string(),
            'columns' => Type::nonNull(Type::listOf(Type::nonNull(Type::columnMapping($name)))),
            '_timezone' => Type::timezoneOffset(),
        ];
        $model = model($name);
        foreach ($model->relations([Relation::BELONGS_TO_MANY]) as $key => $relation) {
            $args[$key] = Type::listOf(Type::nonNull(Type::edgeSelector($relation, true)));
        }
        return $args;
    }

}
