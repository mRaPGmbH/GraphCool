<?php

declare(strict_types=1);

namespace Mrap\GraphCool\DataSource\Mysql;

use stdClass;

class Mysql
{

    protected static MysqlConnector $connector;
    protected static MysqlNodeReader $nodeReader;
    protected static MysqlNodeWriter $nodeWriter;
    protected static MysqlEdgeReader $edgeReader;
    protected static MysqlEdgeWriter $edgeWriter;


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

    /**
     * @codeCoverageIgnore
     */
    public static function nodeReader(): MysqlNodeReader
    {
        if (!isset(static::$nodeReader)) {
            static::$nodeReader = new MysqlNodeReader();
        }
        return static::$nodeReader;
    }

    public static function setNodeReader(MysqlNodeReader $nodeReader): void
    {
        static::$nodeReader = $nodeReader;
    }

    /**
     * @codeCoverageIgnore
     */
    public static function nodeWriter(): MysqlNodeWriter
    {
        if (!isset(static::$nodeWriter)) {
            static::$nodeWriter = new MysqlNodeWriter();
        }
        return static::$nodeWriter;
    }

    public static function setNodeWriter(MysqlNodeWriter $nodeWriter): void
    {
        static::$nodeWriter = $nodeWriter;
    }

    /**
     * @codeCoverageIgnore
     */
    public static function edgeReader(): MysqlEdgeReader
    {
        if (!isset(static::$edgeReader)) {
            static::$edgeReader = new MysqlEdgeReader();
        }
        return static::$edgeReader;
    }

    public static function setEdgeReader(MysqlEdgeReader $edgeReader): void
    {
        static::$edgeReader = $edgeReader;
    }

    /**
     * @codeCoverageIgnore
     */
    public static function edgeWriter(): MysqlEdgeWriter
    {
        if (!isset(static::$edgeWriter)) {
            static::$edgeWriter = new MysqlEdgeWriter();
        }
        return static::$edgeWriter;
    }

    public static function setEdgeWriter(MysqlEdgeWriter $edgeWriter): void
    {
        static::$edgeWriter = $edgeWriter;
    }


}