<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Queries;

use GraphQL\Type\Definition\ResolveInfo;
use Mrap\GraphCool\DataSource\DB;
use Mrap\GraphCool\Definition\ModelQuery;
use Mrap\GraphCool\Types\Type;
use Mrap\GraphCool\Utils\Authorization;
use Mrap\GraphCool\Utils\JwtAuthentication;

class ReadModel extends ModelQuery
{

    public function __construct(string $model)
    {
        $this->name = lcfirst($model);
        $this->model = $model;
        $this->config = [
            'type' => Type::get($model),
            'description' => 'Get a single ' . $model . ' by it\'s ID',
            'args' => [
                'id' => Type::nonNull(Type::id()),
                '_timezone' => Type::get('_TimezoneOffset'),
            ]
        ];
    }

    public function resolve(array $rootValue, array $args, mixed $context, ResolveInfo $info): mixed
    {
        Authorization::authorize('read', $this->model);
        return DB::load(JwtAuthentication::tenantId(), $this->model, $args['id']);
    }
}