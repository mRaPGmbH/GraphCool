<?php

namespace Mrap\GraphCool\Utils;

class Config
{
    protected static ConfigProvider $provider;

    public static function setProvider(ConfigProvider $provider): void
    {
        static::$provider = $provider;
    }

    protected static function provider(): ConfigProvider
    {
        if (!isset(static::$provider)) {
           // @codeCoverageIgnoreStart
           static::$provider = new ConfigProvider();
           // @codeCoverageIgnoreEnd
        }
        return static::$provider;
    }


    public static function get(string $config, string $key = null): mixed
    {
        return self::provider()->get($config, $key);
    }

}
