<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Objects;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use Mrap\GraphCool\Definition\Model;
use Mrap\GraphCool\Definition\Relation;
use Mrap\GraphCool\Types\Type;
use stdClass;
use function Mrap\GraphCool\model;

class ModelType extends ObjectType
{
    protected Model $model;

    public function __construct(string $name)
    {
        $this->model = model($name);
        $config = [
            'name' => $name,
            'fields' => fn() => $this->fieldConfig($name),
            'resolveField' => function ($rootValue, $args, $context, $info) {
                return $this->resolve($rootValue, $args, $context, $info);
            }
        ];
        parent::__construct($config);
    }

    protected function fieldConfig(string $name): array
    {
        $fields = [];
        foreach ($this->model->relations() as $key => $relation) {
            if ($relation->type === Relation::BELONGS_TO || $relation->type === Relation::HAS_ONE) {
                $fields[$key] = [
                    'type' => Type::get('_' . $name . '__' . $key . 'Edge'),
                    'description' => $relation->description ?? null,
                ];
            } elseif ($relation->type === Relation::HAS_MANY || $relation->type === Relation::BELONGS_TO_MANY) {
                $fields[$key] = [
                    'type' => Type::get('_' . $name . '__' . $key . 'Edges'),
                    'description' => $relation->description ?? null,
                    'args' => [
                        'first' => Type::int(),
                        'page' => Type::int(),
                        'where' => Type::get('_' . $name . '__' . $key . 'EdgeWhereConditions'),
                        'orderBy' => Type::listOf(
                            Type::nonNull(Type::get('_' . $name . '__' . $key . 'EdgeOrderByClause'))
                        ),
                        'search' => Type::string(),
                        'searchLoosely' => Type::string(),
                        'result' => Type::get('_Result'),
                    ]
                ];
            }
        }
        foreach ($this->model->fields() as $key => $field) {
            $fields[$key] = [
                'type' => Type::getForField($field),
                'description' => $field->description ?? null,
            ];
        }
        ksort($fields);
        return $fields;
    }

    /**
     * @param stdClass $modelData
     * @param mixed[] $args
     * @param mixed $context
     * @param ResolveInfo $info
     * @return mixed
     */
    protected function resolve(stdClass $modelData, array $args, mixed $context, ResolveInfo $info): mixed
    {
        $field = $this->model->{$info->fieldName} ?? null;
        if ($field instanceof Relation) {
            $closure = $modelData->{$info->fieldName};
            return $closure($args);
        }
        return $modelData->{$info->fieldName} ?? null;
    }


}