<?php

namespace App\Queries;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Mrap\GraphCool\Definition\Query;

class InvalidJsonQuery extends Query
{

    public function __construct(?string $model = null)
    {
        $this->name = 'InvalidJsonQuery';
        $this->config = [
            'type' => Type::string()
        ];
    }

    public function resolve(array $rootValue, array $args, mixed $context, ResolveInfo $info): mixed
    {
        return "\xB1\x31";
    }
}