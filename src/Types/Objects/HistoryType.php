<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Objects;

use GraphQL\Type\Definition\ObjectType;
use Mrap\GraphCool\Types\Type;

class HistoryType extends ObjectType
{

    public function __construct()
    {
        $config = [
            'name' => '_History',
            'fields' => [
                'id' => Type::nonNull(Type::string()),
                'number' => Type::nonNull(Type::int()),
                'node_id' => Type::nonNull(Type::string()),
                'model' => Type::nonNull(Type::string()),
                'sub' => Type::string(),
                'ip' => Type::string(),
                'user_agent' => Type::string(),
                'change_type' => Type::get('_History_ChangeType'),
                'changes' => Type::nonNull(Type::string()),
                'preceding_hash' => Type::string(),
                'hash' => Type::nonNull(Type::string()),
                'created_at' => Type::nonNull(Type::get('_DateTime')),
            ],
        ];
        ksort($config['fields']);
        parent::__construct($config);
    }

}
