<?php

declare(strict_types=1);

namespace Mrap\GraphCool\DataSource\Mysql;

use stdClass;

class Mysql
{

    protected static MysqlConnector $connector;

    public static function setConnector(MysqlConnector $connector): void
    {
        static::$connector = $connector;
    }

    public static function execute(string $sql, array $params): int
    {
        return static::get()->execute($sql, $params);
    }

    public static function executeRaw(string $sql): int|false
    {
        return static::get()->executeRaw($sql);
    }

    /**
     * @codeCoverageIgnore
     */
    protected static function get(): MysqlConnector
    {
        if (!isset(static::$connector)) {
            static::$connector = new MysqlConnector();
        }
        return static::$connector;
    }

    public static function fetch(string $sql, array $params): ?stdClass
    {
        return static::get()->fetch($sql, $params);
    }

    public static function fetchAll(string $sql, array $params): array
    {
        return static::get()->fetchAll($sql, $params);
    }

    public static function fetchColumn(string $sql, array $params, int $column = 0): string
    {
        return static::get()->fetchColumn($sql, $params, $column);
    }

    public static function waitForConnection(int $retries = 5): void
    {
        static::get()->waitForConnection($retries);
    }

}