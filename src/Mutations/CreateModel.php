<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Mutations;

use GraphQL\Type\Definition\ResolveInfo;
use Mrap\GraphCool\DataSource\DB;
use Mrap\GraphCool\Definition\ModelQuery;
use Mrap\GraphCool\Definition\Relation;
use Mrap\GraphCool\Types\Type;
use Mrap\GraphCool\Utils\Authorization;
use Mrap\GraphCool\Utils\JwtAuthentication;
use function Mrap\GraphCool\model;

class CreateModel extends ModelQuery
{

    public function __construct(string $model)
    {
        $this->name = 'create'.$model;
        $this->model = $model;
        $modelObj = model($model);

        $args = [];
        foreach ($modelObj->fields() as $key => $field) {
            if ($field->readonly === true) {
                continue;
            }
            $args[$key] = Type::getForField($field, true);
        }
        foreach ($modelObj->relations() as $key => $relation) {
            if ($relation->type === Relation::BELONGS_TO) {
                if ($relation->null) {
                    $args[$key] = Type::get('_' . $model . '__' . $key . 'Relation');
                } else {
                    $args[$key] = Type::nonNull(Type::get('_' . $model . '__' . $key . 'Relation'));
                }
            } elseif ($relation->type === Relation::BELONGS_TO_MANY) {
                $args[$key] = Type::listOf(
                    Type::nonNull(Type::get('_' . $model . '__' . $key . 'ManyRelation'))
                );
            }
        }
        $args['_timezone'] = Type::get('_TimezoneOffset');
        ksort($args);

        $this->config = [
            'type' => Type::get($model),
            'description' => 'Create a single new ' . $model . ' entry',
            'args' => $args,
        ];
    }

    public function resolve(array $rootValue, array $args, mixed $context, ResolveInfo $info): mixed
    {
        Authorization::authorize('create', $this->model);
        $model = model($this->model);
        $data = $model->udpateDerivedFields(JwtAuthentication::tenantId(), $args);
        $result = DB::insert(JwtAuthentication::tenantId(), $this->model, $data);
        if ($result !== null) {
            $model->onSave($result, $args);
            $model->onChange($result, $args); // deprecated!
        }
        return $result;
    }
}
