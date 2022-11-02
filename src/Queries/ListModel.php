<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Queries;

use GraphQL\Type\Definition\ResolveInfo;
use Mrap\GraphCool\DataSource\DB;
use Mrap\GraphCool\Definition\ModelQuery;
use Mrap\GraphCool\Definition\Relation;
use Mrap\GraphCool\Types\Type;
use Mrap\GraphCool\Utils\Authorization;
use Mrap\GraphCool\Utils\JwtAuthentication;

use function Mrap\GraphCool\pluralize;
use function Mrap\GraphCool\model;

class ListModel extends ModelQuery
{

    public function __construct(string $model)
    {
        $this->name = pluralize(lcfirst($model));
        $this->model = $model;

        $args = [
            'first' => Type::int(),
            'page' => Type::int(),
            'where' => Type::get('_' . $model . 'WhereConditions'),
            'whereMode' => Type::get('_WhereMode'),
        ];
        foreach (get_object_vars(model($model)) as $key => $relation) {
            if (!$relation instanceof Relation) {
                continue;
            }
            $args['where' . ucfirst($key)] = Type::get('_' . $relation->name . 'WhereConditions');
        }
        $args['orderBy'] = Type::listOf(Type::nonNull(Type::get('_' . $model . 'OrderByClause')));
        $args['search'] = Type::string();
        $args['searchLoosely'] = Type::string();
        $args['result'] = Type::get('_Result');
        $args['_timezone'] = Type::get('_TimezoneOffset');

        $this->config = [
            'type' => Type::get('_' . $model . 'Paginator'),
            'description' => 'Get a paginated list of ' . $model . 's filtered by given where clauses.',
            'args' => $args
        ];
    }

    public function resolve(array $rootValue, array $args, mixed $context, ResolveInfo $info): mixed
    {
        Authorization::authorize('find', $this->model);
        return DB::findAll(JwtAuthentication::tenantId(), $this->model , $args);
    }
}