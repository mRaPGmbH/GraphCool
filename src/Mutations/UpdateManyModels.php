<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Mutations;

use GraphQL\Type\Definition\ResolveInfo;
use Mrap\GraphCool\DataSource\DB;
use Mrap\GraphCool\Definition\ModelBased;
use Mrap\GraphCool\Definition\Mutation;
use Mrap\GraphCool\Definition\Relation;
use Mrap\GraphCool\Types\Type;
use Mrap\GraphCool\Utils\Authorization;
use Mrap\GraphCool\Utils\JwtAuthentication;
use RuntimeException;
use function Mrap\GraphCool\model;
use function Mrap\GraphCool\pluralize;

class UpdateManyModels extends Mutation
{
    use ModelBased;

    public function __construct(?string $model = null)
    {
        if ($model === null) {
            throw new RuntimeException(__METHOD__.': parameter $model may not be null for ModelBased mutations.');
        }
        $this->name = 'updateMany'.pluralize($model);
        $this->model = $model;

        $args = [
            'where' => Type::whereConditions($model),
            'whereMode' => Type::whereMode(),
            '_timezone' => Type::timezoneOffset(),
            'data' => Type::nonNull(Type::input($model)),
        ];
        foreach (get_object_vars(model($model)) as $key => $relation) {
            if (!$relation instanceof Relation) {
                continue;
            }
            $args['where' . ucfirst($key)] = Type::whereConditions($relation->name);
        }

        $this->config = [
            'type' => Type::updateManyResult(),
            'description' => 'Modify multiple existing ' . $model . ' entries, using where.',
            'args' => $args,
        ];
    }

    public function resolve(array $rootValue, array $args, mixed $context, ResolveInfo $info): mixed
    {
        Authorization::authorize('updateMany', $this->model);
        return DB::updateAll(JwtAuthentication::tenantId(), $this->model, $args);
    }
}
