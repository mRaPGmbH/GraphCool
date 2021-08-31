<?php

namespace App\Queries;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Mrap\GraphCool\Model\Query;
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

    public function resolve(array $rootValue, array $args, $context, ResolveInfo $info)
    {
        return "\xB1\x31";
    }
}