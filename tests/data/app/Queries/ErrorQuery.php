<?php

namespace App\Queries;

use GraphQL\Error\Error;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Mrap\GraphCool\Definition\Query;
use Mrap\GraphCool\Types\TypeLoader;

class ErrorQuery extends Query
{

    public function __construct(TypeLoader $typeLoader)
    {
        $this->name = 'ErrorQuery';
        $this->config = [
            'type' => Type::string()
        ];
    }

    public function resolve(array $rootValue, array $args, mixed $context, ResolveInfo $info): mixed
    {
        throw new Error('nada');
    }
}