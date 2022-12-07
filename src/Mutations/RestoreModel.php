<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Mutations;

use GraphQL\Type\Definition\ResolveInfo;
use Mrap\GraphCool\DataSource\DB;
use Mrap\GraphCool\Definition\ModelBased;
use Mrap\GraphCool\Definition\Mutation;
use Mrap\GraphCool\Types\Type;
use Mrap\GraphCool\Utils\Authorization;
use Mrap\GraphCool\Utils\JwtAuthentication;
use RuntimeException;

class RestoreModel extends Mutation
{
    use ModelBased;

    public function __construct(?string $model = null)
    {
        if ($model === null) {
            throw new RuntimeException(__METHOD__.': parameter $model may not be null for ModelBased mutations.');
        }
        $this->name = 'restore'.$model;
        $this->model = $model;

        $this->config = [
            'type' => Type::model($model),
            'description' => 'Restore a previously soft-deleted ' . $model . ' record by ID',
            'args' => [
                'id' => Type::nonNull(Type::id()),
                '_timezone' => Type::get('_TimezoneOffset'),
            ]
        ];
    }

    public function resolve(array $rootValue, array $args, mixed $context, ResolveInfo $info): mixed
    {
        Authorization::authorize('restore', $this->model);
        return DB::restore(JwtAuthentication::tenantId(), $this->model, $args['id']);
    }
}
