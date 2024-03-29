<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types\Enums;

use GraphQL\Type\Definition\EnumType;
use Mrap\GraphCool\Types\StaticTypeTrait;

class LocaleCode extends EnumType
{

    use StaticTypeTrait;

    public static function staticName(): string
    {
        return '_LocaleCode';
    }

    public function __construct()
    {
        $config = [
            'name' => static::getFullName(),
            'description' => 'Locales TODO',
            'values' => [
                'de_AT' => ['value' => 'de_AT', 'description' => 'German (Austria)'],
                'de_DE' => ['value' => 'de_DE', 'description' => 'German (Germany)'],
                'en_US' => ['value' => 'en_US', 'description' => 'English (American)'],
                'en_GB' => ['value' => 'en_GB', 'description' => 'English (British)'],
            ]
        ];
        parent::__construct($config);
    }
}
