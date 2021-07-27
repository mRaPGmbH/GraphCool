<?php


namespace Mrap\GraphCool\Tests\DataSource\Mysql;


use Mrap\GraphCool\DataSource\Mysql\MysqlConnector;
use Mrap\GraphCool\Tests\TestCase;
use PDO;
use PDOStatement;

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
        self::assertEquals(2, $result);
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
        self::assertEquals(3, $result);
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
        self::assertEquals($expected, $result);
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
        self::assertEquals([], $result);
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
        self::assertEquals('c', $result);
    }

}