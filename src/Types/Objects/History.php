<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Objects;

use GraphQL\Type\Definition\ObjectType;
use Mrap\GraphCool\Types\StaticTypeTrait;
use Mrap\GraphCool\Types\Type;

class History extends ObjectType
{

    use StaticTypeTrait;

    public static function staticName(): string
    {
        return '_History';
    }

    public function __construct()
    {
        parent::__construct([
            'name' => static::getFullName(),
            'fields' => fn() => [
                'change_type' => Type::historyChangeType(),
                'changes' => Type::nonNull(Type::string()),
                'created_at' => Type::nonNull(Type::dateTime()),
                'hash' => Type::nonNull(Type::string()),
                'id' => Type::nonNull(Type::string()),
                'ip' => Type::string(),
                'model' => Type::nonNull(Type::string()),
                'node_id' => Type::nonNull(Type::string()),
                'number' => Type::nonNull(Type::int()),
                'preceding_hash' => Type::string(),
                'sub' => Type::string(),
                'user_agent' => Type::string(),
            ],
        ]);
    }

}

