<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types;

use GraphQL\Type\Definition\ResolveInfo;
use Mrap\GraphCool\Definition\ModelBased;
use Mrap\GraphCool\Utils\ClassFinder;
use Mrap\GraphCool\Utils\TimeZone;
use RuntimeException;

class MutationType extends BaseType
{

    /** @var callable[] */
    protected array $customResolvers = [];

    protected array $mutations = [];

    public function __construct()
    {
        foreach (ClassFinder::mutations() as $classname) {
            if (in_array(ModelBased::class, (new \ReflectionClass($classname))->getTraitNames())) {
                foreach (ClassFinder::models() as $model => $tmp) {
                    $mutation = new $classname($model);
                    $this->mutations[$mutation->name] = $mutation;
                }
            } else {
                $mutation = new $classname();
                $this->mutations[$mutation->name] = $mutation;
            }

        }
        foreach ($this->mutations as $name => $mutation) {
            $fields[$name] = $mutation->config;
        }

        ksort($fields);
        $config = [
            'name' => 'Mutation',
            'fields' => $fields,
            'resolveField' => function (array $rootValue, array $args, $context, ResolveInfo $info) {
                return $this->resolve($rootValue, $args, $context, $info);
            }
        ];
        parent::__construct($config);
    }


    /**
     * @param mixed[] $rootValue
     * @param mixed[] $args
     * @param mixed $context
     * @param ResolveInfo $info
     * @return mixed
     * @throws \GraphQL\Error\Error
     */
    protected function resolve(array $rootValue, array $args, $context, ResolveInfo $info): mixed
    {
        if (isset($args['_timezone'])) {
            TimeZone::set($args['_timezone']);
        }

        if (isset($this->mutations[$info->fieldName])) {
            return $this->mutations[$info->fieldName]->resolve($rootValue, $args, $context, $info);
        }

        throw new RuntimeException(print_r($info->fieldName, true));
    }


}
