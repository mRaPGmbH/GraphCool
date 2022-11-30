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
use function Mrap\GraphCool\model;

class UpdateModel extends Mutation
{
    use ModelBased;

    public function __construct(?string $model = null)
    {
        if ($model === null) {
            throw new RuntimeException(__METHOD__.': parameter $model may not be null for ModelBased mutations.');
        }
        $this->name = 'update'.$model;
        $this->model = $model;

        $this->config = [
            'type' => Type::get($model),
            'description' => 'Modify an existing ' . $model . ' entry',
            'args' => [
                'id' => Type::nonNull(Type::id()),
                '_timezone' => Type::get('_TimezoneOffset'),
                'data' => Type::nonNull(Type::input($model)),
            ]
        ];
    }

    public function resolve(array $rootValue, array $args, mixed $context, ResolveInfo $info): mixed
    {
        Authorization::authorize('update', $this->model);
        $model = model($this->model);
        $args['data'] = $model->udpateDerivedFields(JwtAuthentication::tenantId(), $args['data'], $args['id']);
        $result = DB::update(JwtAuthentication::tenantId(), $this->model, $args);
        if ($result !== null) {
            $model->onSave($result, $args);
            $model->onChange($result, $args);
        }
        return $result;
    }
}
