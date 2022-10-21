<?php

namespace Mrap\GraphCool\Types;

use GraphQL\Type\Definition\NullableType;
use GraphQL\Type\Definition\Type as BaseType;

abstract class Type extends BaseType implements NullableType
{
    protected static array $types = [];

    protected static TypeLoader $typeLoader;

    // TODO: change return type to self
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
        if (!isset(static::$typeLoader)) {
            static::$typeLoader = new TypeLoader();
        }
        return static::$typeLoader->create($name);
    }

}