<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Enums;

use GraphQL\Type\Definition\EnumType;
use Mrap\GraphCool\Types\StaticTypeTrait;

class LanguageCode extends EnumType
{

    use StaticTypeTrait;

    public static function staticName(): string
    {
        return '_LanguageCode';
    }

    public function __construct()
    {
        $config = [
            'name' => static::getFullName(),
            'description' => 'Languages TODO',
            'values' => [
                'de' => ['value' => 'de', 'description' => 'German'],
                'en' => ['value' => 'en', 'description' => 'English']
            ]
        ];
        parent::__construct($config);
    }
}
