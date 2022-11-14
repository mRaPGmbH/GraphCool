<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Objects;

use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class ImportErrorType extends ObjectType
{

    public function __construct()
    {
        parent::__construct([
            'name' => '_ImportError',
            'description' => 'Description of a problem encountered in import data.',
            'fields' => fn() => [
                'row' => new NonNull(Type::int()),
                'column' => new NonNull(Type::string()),
                'value' => new NonNull(Type::string()),
                'relation' => Type::string(),
                'field' => new NonNull(Type::string()),
                'ignored' => new NonNull(Type::boolean()),
                'message' => new NonNull(Type::string())
            ],
        ]);
    }

}
