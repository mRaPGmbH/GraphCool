<?php

namespace Mrap\GraphCool\Utils;

use Mrap\GraphCool\DataSource\FullTextIndexProvider;
use Mrap\GraphCool\DataSource\Mysql\MysqlFullTextIndexProvider;

class FullTextIndex
{
    protected static FullTextIndexProvider $provider;

    public static function setProvider(FullTextIndexProvider $provider): void
    {
        static::$provider = $provider;
    }

    /**
     * @codeCoverageIgnore
     */
    protected static function get(): FullTextIndexProvider
    {
        if (!isset(static::$provider)) {
            $classname = MysqlFullTextIndexProvider::class;
            static::$provider = new $classname();
        }
        return static::$provider;
    }

    public static function index(string $model, \stdClass $data): void
    {
        static::get()->index($model, $data);
    }

    public static function shutdown(): void
    {
        static::get()->shutdown();
    }


}