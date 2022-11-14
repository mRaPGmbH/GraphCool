<?php

namespace Mrap\GraphCool\DataSource;

use Mrap\GraphCool\DataSource\Mysql\MysqlFullTextIndexProvider;

class FullTextIndex
{
    protected static FullTextIndexProvider $provider;

    public static function setProvider(FullTextIndexProvider $provider): void
    {
        static::$provider = $provider;
    }

    protected static function get(): FullTextIndexProvider
    {
        if (!isset(static::$provider)) {
            // @codeCoverageIgnoreStart
            $classname = MysqlFullTextIndexProvider::class;
            static::$provider = new $classname();
            // @codeCoverageIgnoreEnd
        }
        return static::$provider;
    }

    public static function index(string $tenantId, string $model, string $id): void
    {
        static::get()->index($tenantId, $model, $id);
    }

    public static function delete(string $tenantId, string $model, string $id): void
    {
        static::get()->delete($tenantId, $model, $id);
    }

    public static function search(string $tenantId, string $searchString): array
    {
        return static::get()->search($tenantId, $searchString);
    }

    public static function shutdown(): void
    {
        static::get()->shutdown();
    }

    public static function rebuildIndex(): void
    {
        static::get()->rebuildIndex();
    }


}
