<?php


namespace Mrap\GraphCool\Types;

use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Mrap\GraphCool\DataSource\DB;
use Mrap\GraphCool\Utils\ModelFinder;

class QueryType extends ObjectType
{

    public function __construct(TypeLoader $typeLoader)
    {
        $fields = [];
        foreach (ModelFinder::all() as $name) {
            $type = $typeLoader->load($name)();
            $fields[lcfirst($type->name)] = $this->read($type);
            $fields[lcfirst($type->name) . 's'] = $this->list($type, $typeLoader);
        }
        $config = [
            'name'   => 'Query',
            'fields' => $fields,
            'resolveField' => function($rootValue, $args, $context, $info) {
                return $this->resolve($rootValue, $args, $context, $info);
            }
        ];
        parent::__construct($config);
    }

    protected function read($type): array
    {
        return [
            'type' => $type,
            'description' => 'Get a single ' .  $type->name . ' by it\'s ID',
            'args' => [
                'id' => new NonNull(Type::id())
            ]
        ];
    }

    protected function list($type, TypeLoader $typeLoader): array
    {
        return [
            'type' => $typeLoader->load('_' . $type->name.'Paginator', $type),
            'args' => [
                'first'=> Type::int(),
                'page' => Type::int(),
                'where' => $typeLoader->load('_' . $type->name . 'WhereConditions', $type),
            ]
        ];
    }

    protected function resolve(array $rootValue, array $args, $context, ResolveInfo $info): ?\stdClass
    {
        if (is_object($info->returnType) && strpos($info->returnType->name, 'Paginator') > 0) {
            return DB::findAll(substr($info->returnType->name, 1,-9), $args);
        }
        // TODO: how to get the nested args from $info???
        return DB::load($info->returnType, $args['id']);
    }

}