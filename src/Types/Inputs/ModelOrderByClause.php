<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Inputs;

use GraphQL\Type\Definition\InputObjectType;
use Mrap\GraphCool\Types\DynamicTypeTrait;
use Mrap\GraphCool\Types\Type;

class ModelOrderByClause extends InputObjectType
{

    use DynamicTypeTrait;

    public static function prefix(): string
    {
        return '_';
    }

    public static function postfix(): string
    {
        return 'OrderByClause';
    }

    public function __construct(string $name)
    {
        parent::__construct([
            'name' => static::getFullName($name),
            'fields' => fn() => [
                'field' => Type::column($name),
                'order' => Type::sortOrderEnum(),
            ],
        ]);
    }

}
