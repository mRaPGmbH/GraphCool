<?php

namespace Mrap\GraphCool\Types;

trait DynamicTypeTrait
{
    abstract public static function prefix(): string;
    abstract public static function postfix(): string;

    public static function getFullName(string $name): string
    {
        return static::prefix() . $name . static::postfix();
    }

    public static function getStrippedName(string $name): string
    {
        return substr($name, strlen(static::prefix()), -strlen(static::postfix()));
    }

    public static function nameMatches(string $name): bool
    {
        return str_starts_with($name, static::$prefix) && str_ends_with($name, static::$postfix);
    }

}