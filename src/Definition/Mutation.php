<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Definition;

use GraphQL\Type\Definition\ResolveInfo;
use stdClass;

abstract class Mutation extends stdClass
{
    public string $name;
    /** @var mixed[] */
    public array $config;

    abstract public function __construct(?string $model = null);

    /**
     * @param mixed[] $rootValue
     * @param mixed[] $args
     * @param mixed $context
     * @param ResolveInfo $info
     * @return mixed
     */
    abstract public function resolve(array $rootValue, array $args, mixed $context, ResolveInfo $info);

}
