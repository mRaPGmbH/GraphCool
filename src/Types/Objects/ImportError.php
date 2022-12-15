<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Objects;

use GraphQL\Type\Definition\ObjectType;
use Mrap\GraphCool\Types\StaticTypeTrait;
use Mrap\GraphCool\Types\Type;

class ImportError extends ObjectType
{

    use StaticTypeTrait;

    public static function staticName(): string
    {
        return '_ImportError';
    }

    public function __construct()
    {
        parent::__construct([
            'name' => static::getFullName(),
            'description' => 'Description of a problem encountered in import data.',
            'fields' => fn() => [
                'row' => Type::nonNull(Type::int()),
                'column' => Type::nonNull(Type::string()),
                'value' => Type::nonNull(Type::string()),
                'relation' => Type::string(),
                'field' => Type::nonNull(Type::string()),
                'ignored' => Type::nonNull(Type::boolean()),
                'message' => Type::nonNull(Type::string())
            ],
        ]);
    }

}
