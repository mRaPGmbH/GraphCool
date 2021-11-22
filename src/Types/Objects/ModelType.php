<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Objects;

use GraphQL\Error\Error;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Mrap\GraphCool\Definition\Field;
use Mrap\GraphCool\Definition\Model;
use Mrap\GraphCool\Definition\Relation;
use Mrap\GraphCool\Types\TypeLoader;
use stdClass;

class ModelType extends ObjectType
{
    protected Model $model;

    public function __construct(string $name, TypeLoader $typeLoader)
    {
        $classname = 'App\\Models\\' . $name;
        if (!class_exists($classname)) {
            throw new Error('Unknown type "' . $name . '"');
        }
        $this->model = new $classname();
        $config = [
            'name' => $name,
            'fields' => [],
            'resolveField' => function ($rootValue, $args, $context, $info) {
                return $this->resolve($rootValue, $args, $context, $info);
            }
        ];
        foreach ($this->model as $key => $field) {
            $args = null;
            if ($field instanceof Relation) {
                if ($field->type === Relation::BELONGS_TO || $field->type === Relation::HAS_ONE) {
                    $type = $typeLoader->load('_' . $name . '__' . $key . 'Edge');
                } elseif ($field->type === Relation::HAS_MANY || $field->type === Relation::BELONGS_TO_MANY) {
                    $type = $typeLoader->load('_' . $name . '__' . $key . 'Edges', null, $this);
                    $args = [
                        'first' => Type::int(),
                        'page' => Type::int(),
                        'where' => $typeLoader->load('_' . $name . '__' . $key . 'EdgeWhereConditions'),
                        'orderBy' => new ListOfType(
                            new NonNull($typeLoader->load('_' . $name . '__' . $key . 'EdgeOrderByClause', null, $this))
                        ),
                        'search' => Type::string(),
                        'searchLoosely' => Type::string(),
                        'result' => $typeLoader->load('_Result'),
                    ];
                } else {
                    continue;
                }
            } else {
                if (!$field instanceof Field) {
                    continue;
                }
                $type = $typeLoader->loadForField($field, $name . '__' . $key);
                if ($field->null === false) {
                    $type = new NonNull($type);
                }
            }
            $typeConfig = [
                'type' => $type
            ];
            if (isset($field->description)) {
                $typeConfig['description'] = $field->description;
            }
            if ($args !== null) {
                $typeConfig['args'] = $args;
            }
            $config['fields'][$key] = $typeConfig;
        }
        ksort($config['fields']);
        parent::__construct($config);
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