<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Queries;

use GraphQL\Type\Definition\ResolveInfo;
use Mrap\GraphCool\Definition\ModelBased;
use Mrap\GraphCool\Definition\DeferredBatching;
use Mrap\GraphCool\Definition\Query;
use Mrap\GraphCool\Types\Type;
use Mrap\GraphCool\Utils\Authorization;
use Mrap\GraphCool\Utils\JwtAuthentication;
use RuntimeException;

class ReadModel extends Query
{
    use ModelBased;
    use DeferredBatching;

    public function __construct(?string $model = null)
    {
        if ($model === null) {
            throw new RuntimeException(__METHOD__.': parameter $model may not be null for ModelBased queries.');
        }

        $this->name = lcfirst($model);
        $this->model = $model;
        $this->config = [
            'type' => Type::model($model),
            'description' => 'Get a single ' . $model . ' by it\'s ID',
            'args' => [
                'id' => Type::nonNull(Type::id()),
                '_timezone' => Type::timezoneOffset(),
            ]
        ];
    }

    public function resolve(array $rootValue, array $args, mixed $context, ResolveInfo $info): mixed
    {
        Authorization::authorize('read', $this->model);
        return $this->loadNodeDeferred(JwtAuthentication::tenantId(), $this->model, $args['id']);
    }
}
