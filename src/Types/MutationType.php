<?php


namespace Mrap\GraphCool\Types;

use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Mrap\GraphCool\DataSource\DB;
use Mrap\GraphCool\Model\Field;
use Mrap\GraphCool\Model\Model;
use Mrap\GraphCool\Model\Relation;
use Mrap\GraphCool\Utils\ModelFinder;
use Mrap\GraphCool\Utils\TypeFinder;
use stdClass;

class MutationType extends ObjectType
{

    public function __construct(TypeLoader $typeLoader)
    {
        $fields = [];
        foreach (ModelFinder::all() as $name) {
            $type = $typeLoader->load($name)();
            $classname = 'App\\Models\\' . $name;
            $model = new $classname();
            $fields['create' . $type->name] = $this->create($type, $model, $typeLoader);
            $fields['update' . $type->name] = $this->update($type, $model, $typeLoader);
            $fields['delete' . $type->name] = $this->delete($type);
        }
        ksort($fields);
        $config = [
            'name'   => 'Mutation',
            'fields' => $fields,
            'resolveField' => function(array $rootValue, array $args, $context, ResolveInfo $info) {
                return $this->resolve($rootValue, $args, $context, $info);
            }
        ];
        parent::__construct($config);
    }

    protected function create(ModelType $type, Model $model, TypeLoader $typeLoader): array
    {
        $args = [];
        /**
         * @var string $name
         * @var Field $field
         */
        foreach ($model as $name => $field) {
            if ($field instanceof Relation) {
                $relation = $field;
                if ($relation->type === Relation::BELONGS_TO) {
                    $args[$name] = new NonNull($typeLoader->load('_' . $type->name . '_' . $name . 'Relation'));
                }
                if ($relation->type === Relation::BELONGS_TO_MANY) {
                    $args[$name.'s'] = new ListOfType(new NonNull($typeLoader->load('_' . $type->name . '_' . $name . 'Relation')));
                }
            }

            if (!$field instanceof Field) {
                continue;
            }
            if ($field->readonly === false) {
                if ($field->null === true || ($field->default ?? null) !== null) {
                    $args[$name] = $typeLoader->loadForField($field, $name);
                } else {
                    $args[$name] = new NonNull($typeLoader->loadForField($field, $name));
                }
            }
        }
        $ret = [
            'type' => $type,
            'description' => 'Create a single new ' .  $type->name . ' entry',
        ];
        if (count($args) > 0) {
            ksort($args);
            $ret['args'] = $args;
        }
        return $ret;
    }

    protected function update(ModelType $type, Model $model, TypeLoader $typeLoader): array
    {
        $args = [
            'id' => new nonNull(Type::id())
        ];
        /**
         * @var string $name
         * @var Field $field
         */
        foreach ($model as $name => $field) {
            if ($field instanceof Relation) {
                $relation = $field;
                if ($relation->type === Relation::BELONGS_TO) {
                    $args[$name] = $typeLoader->load('_' . $type->name . '_' . $name . 'Relation');
                }
                if ($relation->type === Relation::BELONGS_TO_MANY) {
                    $args[$name] = new ListOfType(new NonNull($typeLoader->load('_' . $type->name . '_' . $name . 'Relation')));
                }
            }

            if (!$field instanceof Field) {
                continue;
            }
            if ($field->readonly === false) {
                $args[$name] = $typeLoader->loadForField($field, $name);
            }
        }
        $ret = [
            'type' => $type,
            'description' => 'Modify an existing ' .  $type->name . ' entry',
        ];
        if (count($args) > 0) {
            ksort($args);
            $ret['args'] = $args;
        }
        return $ret;
    }

    protected function delete($type): array
    {
        return [
            'type' => $type,
            'description' => 'Delete a ' .  $type->name . ' entry by ID',
            'args' => [
                'id' => new NonNull(Type::id())
            ]
        ];
    }

    protected function resolve(array $rootValue, array $args, $context, ResolveInfo $info): ?stdClass
    {
        if (str_starts_with($info->fieldName, 'create')) {
            return DB::insert($info->returnType, $args);
        }
        if (str_starts_with($info->fieldName, 'update')) {
            return DB::update($info->returnType, $args);
        }
        if (str_starts_with($info->fieldName, 'delete')) {
            return DB::delete($info->returnType, $args['id']);
        }
        throw new \Exception(print_r($info->fieldName, true));
    }


}