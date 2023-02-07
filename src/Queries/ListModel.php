<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Queries;

use GraphQL\Type\Definition\ResolveInfo;
use Mrap\GraphCool\DataSource\DB;
use Mrap\GraphCool\Definition\ModelBased;
use Mrap\GraphCool\Definition\NodeCaching;
use Mrap\GraphCool\Definition\Query;
use Mrap\GraphCool\Definition\Relation;
use Mrap\GraphCool\Types\Type;
use Mrap\GraphCool\Utils\Authorization;
use Mrap\GraphCool\Utils\JwtAuthentication;
use RuntimeException;
use function Mrap\GraphCool\pluralize;
use function Mrap\GraphCool\model;

class ListModel extends Query
{
    use ModelBased;
    use NodeCaching;

    public function __construct(?string $model = null)
    {
        if ($model === null) {
            throw new RuntimeException(__METHOD__.': parameter $model may not be null for ModelBased queries.');
        }

        $this->name = pluralize(lcfirst($model));
        $this->model = $model;

        $args = [
            'first' => Type::int(),
            'page' => Type::int(),
            'where' => Type::whereConditions($model),
            'whereMode' => Type::whereMode(),
        ];
        foreach (get_object_vars(model($model)) as $key => $relation) {
            if (!$relation instanceof Relation) {
                continue;
            }
            $args['where' . ucfirst($key)] = Type::whereConditions($relation->name);
        }
        $args['orderBy'] = Type::listOf(Type::nonNull(Type::orderByClause($model)));
        $args['search'] = Type::string();
        $args['searchLoosely'] = Type::string();
        $args['result'] = Type::result();
        $args['_timezone'] = Type::timezoneOffset();

        $this->config = [
            'type' => Type::paginatedList($model),
            'description' => 'Get a paginated list of ' . $model . 's filtered by given where clauses.',
            'args' => $args
        ];
    }

    public function resolve(array $rootValue, array $args, mixed $context, ResolveInfo $info): mixed
    {
        Authorization::authorize('find', $this->model);
        return DB::findAll(JwtAuthentication::tenantId(), $this->model , $args);
        //return $this->findAll(JwtAuthentication::tenantId(), $this->model , $args);
    }
}
