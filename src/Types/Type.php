<?php

namespace Mrap\GraphCool\Types;

use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\NullableType;
use GraphQL\Type\Definition\Type as BaseType;
use Mrap\GraphCool\Definition\Field;

abstract class Type extends BaseType implements NullableType
{
    protected static array $types = [];

    protected static TypeLoader $typeLoader;

    // TODO: change return type to self?
    public static function get(string $name): NullableType
    {
        if (!isset(static::$types[$name])) {
            static::$types[$name] = static::create($name);
        }
        return static::$types[$name];
    }

    protected static function create(string $name): NullableType
    {
        // TODO: stop using TypeLoader, implement more dynamic/generic type loading in here
        return static::typeLoader()->create($name);
    }

    /**
     * @deprecated
     * @return TypeLoader
     */
    protected static function typeLoader(): TypeLoader
    {
        if (!isset(static::$typeLoader)) {
            static::$typeLoader = new TypeLoader();
        }
        return static::$typeLoader;
    }

    public static function getForField(Field $field, bool $input = false, bool $optional = false): NullableType|NonNull
    {
        $type = match ($field->type) {
            default => Type::string(),
            static::BOOLEAN => Type::boolean(),
            static::FLOAT, Field::DECIMAL => Type::float(),
            static::ID => Type::id(),
            static::INT, Field::AUTO_INCREMENT => Type::int(),
            Field::COUNTRY_CODE => self::get('_CountryCode'),
            Field::CURRENCY_CODE => self::get('_CurrencyCode'),
            Field::LANGUAGE_CODE => self::get('_LanguageCode'),
            Field::LOCALE_CODE => self::get('_LocaleCode'),
            Field::ENUM => self::get('_' . $field->namekey . 'Enum'),
            Field::DATE_TIME, Field::CREATED_AT, Field::DELETED_AT, Field::UPDATED_AT => self::get('_DateTime'),
            Field::DATE => self::get('_Date'),
            Field::TIME => self::get('_Time'),
            Field::TIMEZONE_OFFSET => self::get('_TimezoneOffset'),
            Field::FILE => $input ? self::get('_File') : self::get('_FileExport'),
        };
        if (
            $field->null === true || $optional === true
            || ($input === true && ($field->default ?? null) !== null)
        ) {
            return $type;
        }
        return Type::nonNull($type);
    }

}
