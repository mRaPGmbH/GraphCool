<?php

namespace Mrap\GraphCool\Tests\DataSource\Mysql;

use Mrap\GraphCool\DataSource\Mysql\MysqlConnector;
use Mrap\GraphCool\Tests\TestCase;
use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

class MysqlConnectorTest extends TestCase
{
    public function testExecute(): void
    {
        $mock = $this->createMock(PDOStatement::class);
        $mock->expects($this->once())
            ->method('execute')
            ->with(['b'])
            ->willReturn(true);
        $mock->expects($this->once())
            ->method('rowCount')
            ->willReturn(2);

        $mock2 = $this->createMock(PDO::class);
        $mock2->expects($this->once())
            ->method('prepare')
            ->with('a')
            ->willReturn($mock);

        $connector = new MysqlConnector();
        $connector->setPdo($mock2);

        $result = $connector->execute('a', ['b']);
        self::assertSame(2, $result);
    }

    public function testExecuteRaw(): void
    {
        $mock = $this->createMock(PDO::class);
        $mock->expects($this->once())
            ->method('exec')
            ->with('a')
            ->willReturn(3);

        $connector = new MysqlConnector();
        $connector->setPdo($mock);

        $result = $connector->executeRaw('a');
        self::assertSame(3, $result);
    }

    public function testFetch(): void
    {
        $mock = $this->createMock(PDOStatement::class);
        $expected = (object)['c'];
        $mock->expects($this->once())
            ->method('execute')
            ->with(['b']);
        $mock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_OBJ)
            ->willReturn($expected);

        $mock2 = $this->createMock(PDO::class);
        $mock2->expects($this->once())
            ->method('prepare')
            ->with('a')
            ->willReturn($mock);

        $connector = new MysqlConnector();
        $connector->setPdo($mock2);

        $result = $connector->fetch('a', ['b']);
        self::assertSame($expected, $result);
    }

    public function testFetchNotFound(): void
    {
        $mock = $this->createMock(PDOStatement::class);
        $mock->expects($this->once())
            ->method('execute')
            ->with(['b']);
        $mock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_OBJ)
            ->willReturn(false);

        $mock2 = $this->createMock(PDO::class);
        $mock2->expects($this->once())
            ->method('prepare')
            ->with('a')
            ->willReturn($mock);

        $connector = new MysqlConnector();
        $connector->setPdo($mock2);

        $result = $connector->fetch('a', ['b']);
        self::assertNull($result);
    }

    public function testFetchAll(): void
    {
        $mock = $this->createMock(PDOStatement::class);
        $mock->expects($this->once())
            ->method('execute')
            ->with(['b']);
        $mock->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_OBJ)
            ->willReturn([]);

        $mock2 = $this->createMock(PDO::class);
        $mock2->expects($this->once())
            ->method('prepare')
            ->with('a')
            ->willReturn($mock);

        $connector = new MysqlConnector();
        $connector->setPdo($mock2);

        $result = $connector->fetchAll('a', ['b']);
        self::assertSame([], $result);
    }

    public function testFetchColumn(): void
    {
        $mock = $this->createMock(PDOStatement::class);
        $mock->expects($this->once())
            ->method('execute')
            ->with(['b']);
        $mock->expects($this->once())
            ->method('fetchColumn')
            ->with(3)
            ->willReturn('c');

        $mock2 = $this->createMock(PDO::class);
        $mock2->expects($this->once())
            ->method('prepare')
            ->with('a')
            ->willReturn($mock);

        $connector = new MysqlConnector();
        $connector->setPdo($mock2);

        $result = $connector->fetchColumn('a', ['b'], 3);
        self::assertSame('c', $result);
    }

    public function testIncrement(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->expects($this->once())
            ->method('fetchColumn')
            ->willReturn(5);

        $mock = $this->createMock(PDO::class);
        $mock->expects($this->once())
            ->method('beginTransaction');
        $mock->expects($this->once())
            ->method('query')
            ->withAnyParameters()
            ->willReturn($statement);
        $mock->expects($this->once())
            ->method('exec')
            ->withAnyParameters();
        $mock->expects($this->once())
            ->method('commit');

        $connector = new MysqlConnector();
        $connector->setPdo($mock);

        $result = $connector->increment('1', 'key', 0);
        self::assertSame(6, $result);
    }

    public function testIncrementMin(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->expects($this->once())
            ->method('fetchColumn')
            ->willReturn(1);

        $mock = $this->createMock(PDO::class);
        $mock->expects($this->once())
            ->method('beginTransaction');
        $mock->expects($this->once())
            ->method('query')
            ->withAnyParameters()
            ->willReturn($statement);
        $mock->expects($this->once())
            ->method('exec')
            ->withAnyParameters();
        $mock->expects($this->once())
            ->method('commit');

        $connector = new MysqlConnector();
        $connector->setPdo($mock);

        $result = $connector->increment('1', 'key', 7);
        self::assertSame(8, $result);
    }

    public function testIncrementNew(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->expects($this->once())
            ->method('fetchColumn')
            ->willReturn(false);

        $mock = $this->createMock(PDO::class);
        $mock->expects($this->once())
            ->method('beginTransaction');
        $mock->expects($this->once())
            ->method('query')
            ->withAnyParameters()
            ->willReturn($statement);
        $mock->expects($this->once())
            ->method('exec')
            ->withAnyParameters();
        $mock->expects($this->once())
            ->method('commit');

        $connector = new MysqlConnector();
        $connector->setPdo($mock);

        $result = $connector->increment('1', 'key');
        self::assertSame(1, $result);
    }

    public function testIncrementError(): void
    {
        $this->expectException(RuntimeException::class);

        $mock = $this->createMock(PDO::class);
        $mock->expects($this->once())
            ->method('beginTransaction');
        $mock->expects($this->once())
            ->method('query')
            ->willThrowException(new PDOException('test'));
        $mock->expects($this->once())
            ->method('rollback');

        $connector = new MysqlConnector();
        $connector->setPdo($mock);

        $connector->increment('1', 'key');
    }

}