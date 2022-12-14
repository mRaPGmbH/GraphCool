<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Queries;

use GraphQL\Type\Definition\ResolveInfo;
use Mrap\GraphCool\Definition\Query;
use Mrap\GraphCool\Types\Type;
use Mrap\GraphCool\Utils\Authorization;
use Mrap\GraphCool\Utils\JwtAuthentication;

class Token extends Query
{

    public function __construct(?string $model = null)
    {
        $this->name = '_Token';
        $this->config = [
            'type' => Type::nonNull(Type::string()),
            'description' => 'Get a single use JWT for a specific endpoint of this service.',
            'args' => [
                'endpoint' => Type::entity(),
                'operation' => Type::permissionEnum(),
            ]
        ];
    }

    public function resolve(array $rootValue, array $args, mixed $context, ResolveInfo $info): mixed
    {
        $name = strtolower($args['endpoint']);
        $operation = $args['operation'];
        Authorization::authorize($operation, $name);
        return JwtAuthentication::createLocalToken([$name => [$operation]], JwtAuthentication::tenantId());
    }
}
