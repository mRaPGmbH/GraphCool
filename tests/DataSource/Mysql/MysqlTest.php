<?php

namespace Mrap\GraphCool\Tests\DataSource\Mysql;

use Mrap\GraphCool\DataSource\Mysql\Mysql;
use Mrap\GraphCool\DataSource\Mysql\MysqlConnector;
use Mrap\GraphCool\DataSource\Mysql\MysqlEdgeReader;
use Mrap\GraphCool\DataSource\Mysql\MysqlEdgeWriter;
use Mrap\GraphCool\DataSource\Mysql\MysqlNodeReader;
use Mrap\GraphCool\DataSource\Mysql\MysqlNodeWriter;
use Mrap\GraphCool\Tests\TestCase;
use PDO;

class MysqlTest extends TestCase
{
    public function testExecute(): void
    {
        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('execute')
            ->with('a',['b'])
            ->willReturn(2);
        Mysql::setConnector($mock);
        $result = Mysql::execute('a', ['b']);
        self::assertSame(2, $result);
    }

    public function testExecuteRaw(): void
    {
        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('executeRaw')
            ->with('a')
            ->willReturn(5);
        Mysql::setConnector($mock);
        $result = Mysql::executeRaw('a');
        self::assertSame(5, $result);
    }

    public function testFetch(): void
    {
        $mock = $this->createMock(MysqlConnector::class);
        $expected = (object)['a'];
        $mock->expects($this->once())
            ->method('fetch')
            ->with('b',[])
            ->willReturn($expected);
        Mysql::setConnector($mock);
        $result = Mysql::fetch('b',[]);
        self::assertSame($expected, $result);
    }

    public function testFetchAll(): void
    {
        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('fetchAll')
            ->with('a',[])
            ->willReturn([]);
        Mysql::setConnector($mock);
        $result = Mysql::fetchAll('a',[]);
        self::assertSame([], $result);
    }

    public function testFetchColumn(): void
    {
        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('fetchColumn')
            ->with('a',[],3)
            ->willReturn('b');
        Mysql::setConnector($mock);
        $result = Mysql::fetchColumn('a', [], 3);
        self::assertSame('b', $result);
    }

    public function testIncrement(): void
    {
        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('increment')
            ->with('1','key', 0)
            ->willReturn(123);
        Mysql::setConnector($mock);
        $result = Mysql::increment('1','key', 0);
        self::assertSame(123, $result);
    }

    public function testgetPdo(): void
    {
        $pdo = $this->createMock(PDO::class);
        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('pdo')
            ->willReturn($pdo);
        Mysql::setConnector($mock);
        $result = Mysql::getPdo();
        self::assertSame($pdo, $result);
    }


    public function testWaitForConnection(): void
    {
        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('waitForConnection')
            ->with(7);
        Mysql::setConnector($mock);
        Mysql::waitForConnection(7);
    }

    public function testNodeReader(): void
    {
        self::assertInstanceOf(MysqlNodeReader::class, Mysql::nodeReader());
    }

    public function testNodeWriter(): void
    {
        self::assertInstanceOf(MysqlNodeWriter::class, Mysql::nodeWriter());
    }
    public function testEdgeReader(): void
    {
        self::assertInstanceOf(MysqlEdgeReader::class, Mysql::edgeReader());
    }

    public function testEdgeWriter(): void
    {
        self::assertInstanceOf(MysqlEdgeWriter::class, Mysql::edgeWriter());
    }

    public function testSetNodeReader(): void
    {
        $expected = $this->createMock(MysqlNodeReader::class);
        Mysql::setNodeReader($expected);
        $result = Mysql::nodeReader();
        self::assertEquals($expected, $result);
    }

    public function testSetNodeWriter(): void
    {
        $expected = $this->createMock(MysqlNodeWriter::class);
        Mysql::setNodeWriter($expected);
        $result = Mysql::nodeWriter();
        self::assertEquals($expected, $result);
    }

    public function testSetEdgeReader(): void
    {
        $expected = $this->createMock(MysqlEdgeReader::class);
        Mysql::setEdgeReader($expected);
        $result = Mysql::edgeReader();
        self::assertEquals($expected, $result);
    }

    public function testSetEdgeWriter(): void
    {
        $expected = $this->createMock(MysqlEdgeWriter::class);
        Mysql::setEdgeWriter($expected);
        $result = Mysql::edgeWriter();
        self::assertEquals($expected, $result);
    }

}