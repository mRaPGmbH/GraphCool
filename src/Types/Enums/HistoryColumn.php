<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Enums;

use GraphQL\Type\Definition\EnumType;
use Mrap\GraphCool\Types\StaticTypeTrait;

class HistoryColumn extends EnumType
{

    use StaticTypeTrait;

    public static function staticName(): string
    {
        return '_History_Column';
    }

    public function __construct()
    {
        $config = [
            'name' => static::getFullName(),
            'description' => 'List of column names of `History` type.',
            'values' => [
                'ID' => ['value' => 'id', 'description' => null],
                'NUMBER' => ['value' => 'number', 'description' => 'incrementing change number'],
                'NODE_ID' => ['value' => 'node_id', 'description' => 'id of the database entity that has been changed'],
                'MODEL' => ['value' => 'model', 'description' => 'database entity that has been changed'],
                'SUB' => ['value' => 'sub', 'description' => 'identifier of the user (subject) as found in JWT'],
                'IP' => ['value' => 'ip', 'description' => 'ip address of the device initiating the change'],
                'USER_AGENT' => ['value' => 'user_agent', 'description' => 'browser string of the device initiating the change'],
                'CHANGE_TYPE' => ['value' => 'change_type', 'description' => 'type of change: create, update, massUpdate, delete, restore'],
                'CHANGES' => ['value' => 'changes', 'description' => 'JSON detailing the changes'],
                'PRECEDING_HASH' => ['value' => 'preceding_hash', 'description' => 'hash of previous log entry'],
                'HASH' => ['value' => 'hash', 'description' => 'cryptographic hash of all fields except `hash`'],
                'CREATED_AT' => ['value' => 'created_at', 'description' => 'creation time of history log entry'],
            ]
        ];
        parent::__construct($config);
    }
}
