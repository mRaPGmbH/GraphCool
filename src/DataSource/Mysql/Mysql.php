<?php

declare(strict_types=1);

namespace Mrap\GraphCool\DataSource\Mysql;

use Mrap\GraphCool\Utils\StopWatch;
use RuntimeException;
use stdClass;

class Mysql
{

    protected static ?MysqlConnector $connector;
    protected static ?MysqlNodeReader $nodeReader;
    protected static ?MysqlNodeWriter $nodeWriter;
    protected static ?MysqlEdgeReader $edgeReader;
    protected static ?MysqlEdgeWriter $edgeWriter;
    protected static ?MysqlHistory $history;
    protected static ?string $tenantId = null;

    public static function reset(bool $partial = false): void
    {
        if ($partial === false) {
            static::$connector = null;
        }
        static::$nodeReader = null;
        static::$nodeWriter = null;
        static::$edgeReader = null;
        static::$edgeWriter = null;
        static::$tenantId = null;
    }

    public static function checkTenantId(string $tenantId): void
    {
        if (static::$tenantId !== null && static::$tenantId !== $tenantId) {
            throw new RuntimeException('TenantId changed from: ' . static::$tenantId . ' to: ' . $tenantId);
        }
        static::$tenantId = $tenantId;
    }

    public static function tenantId(): ?string
    {
        return static::$tenantId;
    }

    public static function setConnector(MysqlConnector $connector): void
    {
        static::$connector = $connector;
    }

    /**
     * @param string $sql
     * @param mixed[] $params
     * @return int
     */
    public static function execute(string $sql, array $params): int
    {
        StopWatch::start(__METHOD__ . $sql);
        $return = static::get()->execute($sql, $params);
        StopWatch::stop(__METHOD__ . $sql);
        return $return;
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

    public static function executeRaw(string $sql): int|false
    {
        return static::get()->executeRaw($sql);
    }

    /**
     * @param string $sql
     * @param mixed[] $params
     * @return stdClass|null
     */
    public static function fetch(string $sql, array $params): ?stdClass
    {
        return static::get()->fetch($sql, $params);
    }

    /**
     * @param string $sql
     * @param mixed[] $params
     * @return stdClass[]
     */
    public static function fetchAll(string $sql, array $params): array
    {
        return static::get()->fetchAll($sql, $params);
    }

    /**
     * @param string $sql
     * @param mixed[] $params
     * @param int $column
     * @return mixed
     */
    public static function fetchColumn(string $sql, array $params, int $column = 0): mixed
    {
        return static::get()->fetchColumn($sql, $params, $column);
    }

    public static function waitForConnection(int $retries = 5): void
    {
        static::get()->waitForConnection($retries);
    }

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

    public static function history(): MysqlHistory
    {
        if (!isset(static::$history)) {
            static::$history = new MysqlHistory();
        }
        return static::$history;
    }

    public static function setHistory(MysqlHistory $history): void
    {
        static::$history = $history;
    }

    public static function increment(string $tenantId, string $key, int $min = 0, bool $transaction = true): int
    {
        return static::get()->increment($tenantId, $key, $min, $transaction);
    }

    public static function getPdo(): \PDO
    {
        return static::get()->pdo();
    }

    public static function beginTransaction(): void
    {
        static::get()->pdo()->beginTransaction();
    }

    public static function commit(): void
    {
        static::get()->pdo()->commit();
    }

    public static function rollBack(): void
    {
        static::get()->pdo()->rollBack();
    }

}
