<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Objects;

use GraphQL\Type\Definition\ObjectType;
use Mrap\GraphCool\Types\Type;

class HistoryType extends ObjectType
{

    public function __construct()
    {
        parent::__construct([
            'name' => '_History',
            'fields' => fn() => [
                'change_type' => Type::get('_History_ChangeType'),
                'changes' => Type::nonNull(Type::string()),
                'created_at' => Type::nonNull(Type::get('_DateTime')),
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

