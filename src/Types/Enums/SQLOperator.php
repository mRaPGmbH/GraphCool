<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Enums;

use GraphQL\Type\Definition\EnumType;
use Mrap\GraphCool\Types\StaticTypeTrait;

class SQLOperator extends EnumType
{

    use StaticTypeTrait;

    public static function staticName(): string
    {
        return '_SQLOperator';
    }

    public function __construct()
    {
        $config = [
            'name' => static::getFullName(),
            'description' => 'The available SQL operators that are used to filter query results',
            'values' => [
                'EQ' => [
                    'value' => '=',
                    'description' => 'Equal operator (`=`)'
                ],
                'NEQ' => [
                    'value' => '!=',
                    'description' => 'Not equal operator (`!=`)'
                ],
                'GT' => [
                    'value' => '>',
                    'description' => 'Greater than operator (`>`)'
                ],
                'GTE' => [
                    'value' => '>=',
                    'description' => 'Greater than or equal operator (`>=`)'
                ],
                'LT' => [
                    'value' => '<',
                    'description' => 'Less than operator (`<`)'
                ],
                'LTE' => [
                    'value' => '<=',
                    'description' => 'Less than or equal operator (`<=`)'
                ],
                'LIKE' => [
                    'value' => 'LIKE',
                    'description' => 'Simple pattern matching (`LIKE`)'
                ],
                'NOT_LIKE' => [
                    'value' => 'NOT LIKE',
                    'description' => 'Negation of simple pattern matching (`NOT LIKE`)'
                ],
                'IN' => [
                    'value' => 'IN',
                    'description' => 'Whether a value is within a set of values (`IN`)'
                ],
                'NOT_IN' => [
                    'value' => 'NOT IN',
                    'description' => 'Whether a value is not within a set of values (`NOT IN`)'
                ],
                'BETWEEN' => [
                    'value' => 'BETWEEN',
                    'description' => 'Whether a value is within a range of values (`BETWEEN`)'
                ],
                'NOT_BETWEEN' => [
                    'value' => 'NOT BETWEEN',
                    'description' => 'Whether a value is not within a range of values (`NOT BETWEEN`)'
                ],
                'IS_NULL' => [
                    'value' => 'IS NULL',
                    'description' => 'Whether a value is null (`IS NULL`)'
                ],
                'IS_NOT_NULL' => [
                    'value' => 'IS NOT NULL',
                    'description' => 'Whether a value is not null (`IS NOT NULL`)'
                ],
            ]
        ];
        parent::__construct($config);
    }

}
