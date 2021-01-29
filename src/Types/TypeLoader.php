<?php

namespace Mrap\GraphCool\Types;

use GraphQL\Type\Definition\Type;
use Mrap\GraphCool\Model\Field;
use Mrap\GraphCool\Types\Enums\CurrencyEnumType;
use Mrap\GraphCool\Types\Enums\DynamicEnumType;
use Mrap\GraphCool\Types\Enums\CountryCodeEnumType;
use Mrap\GraphCool\Types\Enums\LanguageEnumType;
use Mrap\GraphCool\Types\Enums\LocaleEnumType;

class TypeLoader
{
    protected array $types = [];
    protected static array $registry = [];

    public function __construct()
    {
        self::register('paginatorInfo', PaginatorInfoType::class);
        self::register('SQLOperator', SQLOperatorType::class);
        self::register('CountryCode', CountryCodeEnumType::class);
        self::register('LanguageCode', LanguageEnumType::class);
        self::register('CurrencyCode', CurrencyEnumType::class);
        self::register('LocaleCode', LocaleEnumType::class);
    }

    public function load(string $name, ?ModelType $subType = null): callable
    {
        return function() use ($name, $subType) {
            if (!isset($this->types[$name])) {
                $this->types[$name] = $this->create($name, $subType);
            }
            return $this->types[$name];
        };
    }

    public function loadForField(Field $field, string $name = null): Type
    {
        switch ($field->type) {
            case Type::STRING:
            case Field::CREATED_AT:
            case Field::UPDATED_AT:
                return Type::string();
            case Type::BOOLEAN:
                return Type::boolean();
            case Type::FLOAT:
                return Type::float();
            case Type::ID:
                return Type::id();
            case Type::INT:
                return Type::int();
            case Field::COUNTRY_CODE:
                return $this->load('CountryCode')();
            case Field::CURRENCY_CODE:
                return $this->load('CurrencyCode')();
            case Field::LANGUAGE_CODE:
                return $this->load('LanguageCode')();
            case Field::LOCALE_CODE:
                return $this->load('LocaleCode')();
            case Field::ENUM:
                $key = $name . 'Enum';
                if (!isset($this->types[$key])) {
                    $this->types[$key] = new DynamicEnumType($key, $field);
                }
                return $this->types[$key];
        }
    }

    public static function register($name, $classname): void
    {
        static::$registry[$name] = $classname;
    }


    protected function create(string $name, ?ModelType $subType = null): Type
    {
        if (substr($name, -9) === 'Paginator') {
            return new PaginatorType($name, $this, $subType);
        }
        if (substr($name, -4) === 'Enum') {
            throw new \Exception('TODO!');
        }
        if (isset(static::$registry[$name])) {
            $classname = static::$registry[$name];
            return new $classname();
        }
        return new ModelType($name, $this);
    }

}