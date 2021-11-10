<?php

declare(strict_types=1);

namespace Mrap\GraphCool\DataSource\Mysql;

use Mrap\GraphCool\Utils\Env;
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
            . ';dbname=' . Env::get('DB_DATABASE');
        try {
            $this->pdo = new PDO(
                $connection,
                Env::get('DB_USERNAME'),
                Env::get('DB_PASSWORD'),
                [PDO::ATTR_PERSISTENT => true]
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
    protected function pdo(): PDO
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
     * @return stdClass|null
     */
    public function fetch(string $sql, array $params): ?stdClass
    {
        $statement = $this->statement($sql);
        $statement->execute($params);
        $return = $statement->fetch(PDO::FETCH_OBJ);
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
            throw new RuntimeException('PDOStatement::FetchAll failed.');
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


}
