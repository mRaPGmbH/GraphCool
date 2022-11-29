<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use Mrap\GraphCool\Definition\ModelBased;
use Mrap\GraphCool\Definition\Mutation;
use Mrap\GraphCool\Utils\ClassFinder;
use Mrap\GraphCool\Utils\TimeZone;
use RuntimeException;

class MutationType extends ObjectType
{

    /** @var Mutation[] */
    protected array $mutations = [];

    public function __construct()
    {
        parent::__construct([
            'name' => 'Mutation',
            'fields' => fn() => $this->fieldConfig(),
            'resolveField' => function (array $rootValue, array $args, $context, ResolveInfo $info) {
                if (isset($args['_timezone'])) {
                    TimeZone::set($args['_timezone']);
                }
                if (isset($this->mutations[$info->fieldName])) {
                    return $this->mutations[$info->fieldName]->resolve($rootValue, $args, $context, $info);
                }
                throw new RuntimeException('No resolver found for: ' . $info->fieldName);
            }
        ]);
    }

    protected function fieldConfig(): array
    {
        $fields = [];
        foreach (ClassFinder::mutations() as $classname) {
            if (in_array(ModelBased::class, (new \ReflectionClass($classname))->getTraitNames())) {
                foreach (ClassFinder::models() as $model => $tmp) {
                    $mutation = new $classname($model);
                    $this->mutations[$mutation->name] = $mutation;
                    $fields[$mutation->name] = $mutation->config;
                }
            } else {
                $mutation = new $classname();
                $this->mutations[$mutation->name] = $mutation;
                $fields[$mutation->name] = $mutation->config;
            }
        }
        ksort($fields);
        return $fields;
    }
}
