<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Enums;

use GraphQL\Type\Definition\EnumType;
use Mrap\GraphCool\Types\StaticTypeTrait;

class Result extends EnumType
{
    use StaticTypeTrait;

    public const DEFAULT = 'DEFAULT';
    public const WITH_TRASHED = 'WITH_TRASHED';
    public const ONLY_SOFT_DELETED = 'ONLY_SOFT_DELETED';

    public static function staticName(): string
    {
        return '_Result';
    }

    public function __construct()
    {
        $config = [
            'name' => static::getFullName(),
            'description' => 'Should soft-deleted records be included in the result?',
            'values' => [
                static::DEFAULT => [
                    'value' => static::DEFAULT,
                    'description' => 'Get only non-deleted records. This is the default behavior.'
                ],
                static::WITH_TRASHED => [
                    'value' => static::WITH_TRASHED,
                    'description' => 'Get all records, including soft-deleted and non-deleted records.'
                ],
                static::ONLY_SOFT_DELETED => [
                    'value' => static::ONLY_SOFT_DELETED,
                    'description' => 'Get only soft-deleted records but no non-deleted records.'
                ]
            ]
        ];
        parent::__construct($config);
    }
}
