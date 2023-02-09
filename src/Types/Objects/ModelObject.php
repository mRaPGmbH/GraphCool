<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Objects;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use Mrap\GraphCool\Definition\Model;
use Mrap\GraphCool\Definition\NodeCaching;
use Mrap\GraphCool\Definition\Relation;
use Mrap\GraphCool\Types\DynamicTypeTrait;
use Mrap\GraphCool\Types\Type;
use Mrap\GraphCool\Utils\JwtAuthentication;
use Mrap\GraphCool\Utils\StopWatch;
use stdClass;
use function Mrap\GraphCool\model;

class ModelObject extends ObjectType
{

    use DynamicTypeTrait;
    use NodeCaching;

    protected Model $model;

    public static function prefix(): string
    {
        return '';
    }

    public static function postfix(): string
    {
        return '';
    }

    public function __construct(string $name)
    {
        $this->model = model($name);
        $config = [
            'name' => static::getFullName($name),
            'fields' => fn() => $this->fieldConfig(),
            'resolveField' => function ($rootValue, $args, $context, $info) {
                return $this->resolve($rootValue, $args, $context, $info);
            }
        ];
        parent::__construct($config);
    }

    protected function fieldConfig(): array
    {
        $fields = [];
        foreach ($this->model->relations() as $key => $relation) {
            $fields[$key] = [
                'type' => Type::edge($relation),
                'description' => $relation->description ?? null,
            ];
            if ($relation->type === Relation::HAS_MANY || $relation->type === Relation::BELONGS_TO_MANY) {
                $fields[$key]['args'] = [
                    'first' => Type::int(),
                    'page' => Type::int(),
                    'where' => Type::whereConditions($relation),
                    'orderBy' => Type::listOf(
                        Type::nonNull(Type::orderByClause($relation))
                    ),
                    'search' => Type::string(),
                    'searchLoosely' => Type::string(),
                    'result' => Type::result(),
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
            StopWatch::start('RESOLVE RELATION');
            StopWatch::stop('RESOLVE RELATION');
            return $this->findEdgesDeferred(JwtAuthentication::tenantId(), $modelData, $field, $args);
            //$closure = $modelData->{$info->fieldName};
            //return $closure($args);
        }
        return $modelData->{$info->fieldName} ?? null;
    }


}
