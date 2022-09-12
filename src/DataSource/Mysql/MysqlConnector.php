<?php

declare(strict_types=1);

namespace Mrap\GraphCool\DataSource\Mysql;

use Mrap\GraphCool\Definition\Entity;
use Mrap\GraphCool\Utils\Env;
use Mrap\GraphCool\Utils\ErrorHandler;
use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;
use stdClass;

class MysqlConnector
{
    protected PDO $pdo;
    /**
     * @var PDOStatement[]
     */
    protected array $statements = [];

    public function setPdo(PDO $pdo): void
    {
        $this->pdo = $pdo;
    }

    /**
     * @codeCoverageIgnore
     */
    public function waitForConnection(int $retries = 15): void
    {
        for ($i = 0; $i < $retries; $i++) {
            try {
                $this->connect();
                return;
            } catch (RuntimeException $e) {
                sleep(2); // ignore and retry
            }
        }
        $this->connect(); // final try - don't catch exception
    }

    /**
     * @codeCoverageIgnore
     */
    protected function connect(): void
    {
        $connection = Env::get('DB_CONNECTION') . ':host=' . Env::get('DB_HOST') . ';port=' . Env::get('DB_PORT')
            . ';dbname=' . Env::get('DB_DATABASE') . ';charset=utf8';
        try {
            $this->pdo = new PDO(
                $connection,
                Env::get('DB_USERNAME'),
                Env::get('DB_PASSWORD'),
                [PDO::ATTR_PERSISTENT => true, PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"]
            );
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch (PDOException $e) {
            throw new RuntimeException(
                'Could not connect to database: ' . $connection . ' - user: ' . Env::get('DB_USERNAME') . ' ' . getenv(
                    'DB_USERNAME'
                )
            );
        }
    }

    /**
     * @param string $sql
     * @param mixed[] $params
     * @return int
     */
    public function execute(string $sql, array $params): int
    {
        $statement = $this->statement($sql);
        $statement->execute($params);
        return $statement->rowCount();
    }

    protected function statement(string $sql): PDOStatement
    {
        if (!isset($this->statements[$sql])) {
            $this->statements[$sql] = $this->pdo()->prepare($sql);
        }
        return $this->statements[$sql];
    }

    /**
     * @codeCoverageIgnore
     */
    public function pdo(): PDO
    {
        if (!isset($this->pdo)) {
            $this->connect();
        }
        return $this->pdo;
    }

    public function executeRaw(string $sql): int|false
    {
        return $this->pdo()->exec($sql);
    }

    /**
     * @param string $sql
     * @param mixed[] $params
     * @param string $name
     * @return stdClass|null
     */
    public function fetch(string $sql, array $params, ?string $name = null): stdClass|Entity|null
    {
        $statement = $this->statement($sql);
        $statement->execute($params);
        if ($name === null) {
            $statement->setFetchMode(PDO::FETCH_OBJ);
        } else {
            $statement->setFetchMode(PDO::FETCH_CLASS, Entity::class, [$name]);
        }
        $return = $statement->fetch();
        if ($return === false) {
            return null;
        }
        return $return;
    }

    /**
     * @param string $sql
     * @param mixed[] $params
     * @return stdClass[]
     */
    public function fetchAll(string $sql, array $params): array
    {
        $statement = $this->statement($sql);
        $statement->execute($params);
        $result = $statement->fetchAll(PDO::FETCH_OBJ);
        if ($result === false) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException('PDOStatement::FetchAll failed.');
            // @codeCoverageIgnoreEnd
        }
        return $result;
    }

    /**
     * @param string $sql
     * @param mixed[] $params
     * @param int $column
     * @return mixed
     */
    public function fetchColumn(string $sql, array $params, int $column = 0): mixed
    {
        $statement = $this->statement($sql);
        $statement->execute($params);
        return $statement->fetchColumn($column);
    }

    public function increment(string $tenantId, string $key, int $min = 0, bool $transaction = true): int
    {
        if ($transaction) {
            $this->pdo()->beginTransaction();
        }
        try {
            $quotedTenantId = $this->pdo()->quote($tenantId);
            $quotedKey = $this->pdo()->quote($key);
            $where = 'WHERE `tenant_id` = '.$quotedTenantId.' AND `key` = '.$quotedKey;
            $value = $this->pdo()->query('SELECT `value` FROM `increment` '.$where.' FOR UPDATE', PDO::FETCH_OBJ)->fetchColumn();
            if ($value === false) {
                $value = $min+1;
                $this->pdo()->exec('INSERT INTO `increment` VALUES  (' . $quotedTenantId . ', ' . $quotedKey . ', ' . $value . ' )');
            } else {
                if ($value < $min) {
                    $value = $min;
                }
                $value++;
                $this->pdo()->exec('UPDATE `increment` SET `value` = ' . $value . ' ' . $where);
            }
        } catch (PDOException $e) {
            ErrorHandler::sentryCapture($e);
            if ($transaction) {
                $this->pdo()->rollBack();
            }
            throw new RuntimeException('Increment for ' . $key . ' failed.');
        }
        if ($transaction) {
            $this->pdo()->commit();
        }
        return $value;
    }


}
