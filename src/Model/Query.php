<?php
declare(strict_types=1);

namespace Mrap\GraphCool\Model;

use GraphQL\Type\Definition\ResolveInfo;
use Mrap\GraphCool\Types\TypeLoader;

abstract class Query
{
    public string $fieldName;
    public array $config;

    abstract function __construct(TypeLoader $typeLoader);
    abstract function resolve(array $rootValue, array $args, $context, ResolveInfo $info);
}