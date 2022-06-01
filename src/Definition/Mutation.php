<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Definition;

use GraphQL\Type\Definition\ResolveInfo;
use Mrap\GraphCool\Types\TypeLoader;
use Mrap\GraphCool\Utils\JwtAuthentication;

abstract class Mutation extends \stdClass
{
    public string $fieldName;
    /** @var mixed[] */
    public array $config;

    abstract public function __construct(TypeLoader $typeLoader);

    /**
     * @param mixed[] $rootValue
     * @param mixed[] $args
     * @param mixed $context
     * @param ResolveInfo $info
     * @return mixed
     */
    abstract public function resolve(array $rootValue, array $args, mixed $context, ResolveInfo $info);

    abstract public function authorize(): void;

}