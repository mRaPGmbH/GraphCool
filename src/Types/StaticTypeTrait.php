<?php

namespace Mrap\GraphCool\Types;

trait StaticTypeTrait
{
    abstract public static function staticName(): string;

    public static function getFullName(): string
    {
        return static::staticName();
    }

    public static function nameMatches(string $name): bool
    {
        return $name === static::staticName();
    }

}