<?php

namespace App\Queries;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Mrap\GraphCool\Definition\Query;
use Mrap\GraphCool\Types\TypeLoader;

class InvalidJsonQuery extends Query
{

    public function __construct(TypeLoader $typeLoader)
    {
        $this->name = 'InvalidJsonQuery';
        $this->config = [
            'type' => Type::string()
        ];
    }

    public function authorize(): void
    {
    }

    public function resolve(array $rootValue, array $args, mixed $context, ResolveInfo $info): mixed
    {
        return "\xB1\x31";
    }
}