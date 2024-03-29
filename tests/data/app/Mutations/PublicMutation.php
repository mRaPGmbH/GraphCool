<?php

namespace App\Mutations;

use GraphQL\Type\Definition\ResolveInfo;
use Mrap\GraphCool\Definition\Mutation;
use Mrap\GraphCool\Types\TypeLoader;

class PublicMutation extends Mutation
{

    public function __construct(?string $model = null)
    {
        $this->name = 'PublicMutation';
        $this->config = [];
    }

    public function resolve(array $rootValue, array $args, $context, ResolveInfo $info)
    {
        return 'dummy-mutation-resolve';
    }
}