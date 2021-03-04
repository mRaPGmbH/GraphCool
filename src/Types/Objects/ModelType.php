<?php
declare(strict_types=1);

namespace Mrap\GraphCool\Types\Objects;

use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Mrap\GraphCool\Model\Field;
use Mrap\GraphCool\Model\Model;
use Mrap\GraphCool\Model\Relation;
use Mrap\GraphCool\Types\TypeLoader;
use stdClass;

class ModelType extends ObjectType
{
    protected Model $model;

    public function __construct(string $name, TypeLoader $typeLoader)
    {
        $classname = 'App\\Models\\' . $name;
        $this->model = new $classname();
        $config = [
            'name' => $name,
            'fields' => [],
            'resolveField' => function($rootValue, $args, $context, $info) {
                return $this->resolve($rootValue, $args, $context, $info);
            }
        ];
        /**
         * @var string $key
         * @var Field $field
         */
        foreach ($this->model as $key => $field)
        {
            $args = null;
            if ($field instanceof Relation) {
                if ($field->type === Relation::BELONGS_TO || $field->type === Relation::HAS_ONE) {
                    $type = $typeLoader->load('_' . $name . '_' . $key . 'Edge');
                } elseif ($field->type === Relation::HAS_MANY || $field->type === Relation::BELONGS_TO_MANY) {
                    $type = $typeLoader->load('_' . $name . '_' . $key . 'Edges', null, $this);
                    $args = [
                        'first'=> Type::int(),
                        'page' => Type::int(),
                        'where' => $typeLoader->load('_' . $field->name . 'WhereConditions'),
//                        'orderBy' => $typeLoader->load('_' . $name . '_' . $key . 'EdgeOrderByClause', null, $this),
//                        'search' => Type::string(),
                    ];
                } else {
                    continue;
                }
            } else {
                if (!$field instanceof Field) {
                    continue;
                }
                $type = $typeLoader->loadForField($field, $key);
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

    protected function resolve(stdClass $modelData, array $args, $context, ResolveInfo $info)
    {
        $field = $this->model->{$info->fieldName} ?? null;
        if ($field instanceof Relation) {
            $closure = $modelData->{$info->fieldName};
            return $closure($args);
        }
        return $modelData->{$info->fieldName} ?? null;
    }


}