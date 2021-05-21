<?php
declare(strict_types=1);

namespace Mrap\GraphCool\Model;

use GraphQL\Type\Definition\ResolveInfo;
use Mrap\GraphCool\Types\TypeLoader;
use Mrap\GraphCool\Utils\JwtAuthentication;

abstract class Mutation
{
    public string $fieldName;
    public array $config;
    private bool $authenticate = true;

    abstract public function __construct(TypeLoader $typeLoader);
    abstract public function resolve(array $rootValue, array $args, $context, ResolveInfo $info);

    protected function noAuthentication(): void
    {
        $this->authenticate = false;
    }

    public function authenticate(): void
    {
        if ($this->authenticate) {
            JwtAuthentication::authenticate();
        }
    }

}