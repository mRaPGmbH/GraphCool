<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Definition;

use GraphQL\Type\Definition\ResolveInfo;
use stdClass;

abstract class ModelQuery extends stdClass
{
    public string $name;
    public array $config;
    protected string $model;

    abstract public function __construct(string $model);

    /**
     * @param mixed[] $rootValue
     * @param mixed[] $args
     * @param mixed $context
     * @param ResolveInfo $info
     * @return mixed
     */
    abstract public function resolve(array $rootValue, array $args, mixed $context, ResolveInfo $info): mixed;

}