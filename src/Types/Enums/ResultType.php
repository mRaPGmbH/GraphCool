<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Enums;

use GraphQL\Type\Definition\EnumType;

class ResultType extends EnumType
{
    public const DEFAULT = 'DEFAULT';
    public const WITH_TRASHED = 'WITH_TRASHED';
    public const ONLY_SOFT_DELETED = 'ONLY_SOFT_DELETED';


    public function __construct()
    {
        $config = [
            'name' => '_Result',
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