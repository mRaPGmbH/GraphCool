<?php

namespace Mrap\GraphCool\Tests\DataSource;

use Mrap\GraphCool\DataSource\DB;
use Mrap\GraphCool\DataSource\Mysql\MysqlDataProvider;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Tests\Types\Enums\ResultTypeTest;
use Mrap\GraphCool\Types\Enums\Result;

class DBTest extends TestCase
{
    public function testLoad(): void
    {
        $mock = $this->createMock(MysqlDataProvider::class);
        $expected = (object)['d'];
        $mock->expects($this->once())
            ->method('load')
            ->with('a','c', Result::DEFAULT)
            ->willReturn($expected);
        DB::setProvider($mock);
        $result = DB::load('a','b','c');
        self::assertSame($expected, $result);
    }

    public function testGetMax(): void
    {
        $mock = $this->createMock(MysqlDataProvider::class);
        $mock->expects($this->once())
            ->method('getMax')
            ->with('a','b','c')
            ->willReturn(2);
        DB::setProvider($mock);
        $result = DB::getMax('a','b','c');
        self::assertSame(2, $result);
    }

    public function testFindAll(): void
    {
        $mock = $this->createMock(MysqlDataProvider::class);
        $expected = (object)['d'];
        $mock->expects($this->once())
            ->method('findNodes')
            ->with('a','b',['c'])
            ->willReturn($expected);
        DB::setProvider($mock);
        $result = DB::findNodes('a','b',['c']);
        self::assertSame($expected, $result);
    }

    public function testInsert(): void
    {
        $mock = $this->createMock(MysqlDataProvider::class);
        $expected = (object)['id' => '123'];
        $mock->expects($this->once())
            ->method('insert')
            ->with('a','b',['c'])
            ->willReturn($expected);
        DB::setProvider($mock);
        $result = DB::insert('a','b',['c']);
        self::assertSame($expected, $result);
    }

    public function testUpdate(): void
    {
        $mock = $this->createMock(MysqlDataProvider::class);
        $expected = (object)['id' => '123'];
        $mock->expects($this->once())
            ->method('update')
            ->with('a','b',['c'])
            ->willReturn($expected);
        DB::setProvider($mock);
        $result = DB::update('a','b',['c']);
        self::assertSame($expected, $result);
    }

    public function testUpdateAll(): void
    {
        $mock = $this->createMock(MysqlDataProvider::class);
        $expected = (object)['ids' => ['123']];
        $mock->expects($this->once())
            ->method('updateMany')
            ->with('a','b',['c'])
            ->willReturn($expected);
        DB::setProvider($mock);
        $result = DB::updateAll('a','b',['c']);
        self::assertSame($expected, $result);
    }

    public function testDelete(): void
    {
        $mock = $this->createMock(MysqlDataProvider::class);
        $expected = (object)['id' => '123'];
        $mock->expects($this->once())
            ->method('delete')
            ->with('a','b','c')
            ->willReturn($expected);
        DB::setProvider($mock);
        $result = DB::delete('a','b','c');
        self::assertSame($expected, $result);
    }

    public function testRestore(): void
    {
        $mock = $this->createMock(MysqlDataProvider::class);
        $expected = (object)['id' => '123'];
        $mock->expects($this->once())
            ->method('restore')
            ->with('a','b','c')
            ->willReturn($expected);
        DB::setProvider($mock);
        $result = DB::restore('a','b','c');
        self::assertSame($expected, $result);
    }

    public function testMigrate(): void
    {
        $mock = $this->createMock(MysqlDataProvider::class);
        $mock->expects($this->once())
            ->method('migrate');
        DB::setProvider($mock);
        DB::migrate();
    }

    public function testGetSum(): void
    {
        $mock = $this->createMock(MysqlDataProvider::class);
        $mock->expects($this->once())
            ->method('getSum')
            ->with('1', 'DummyModel', 'key')
            ->willReturn(123);
        DB::setProvider($mock);
        $result = DB::getSum('1', 'DummyModel', 'key');
        self::assertSame(123, $result);
    }

    public function testGetCount(): void
    {
        $mock = $this->createMock(MysqlDataProvider::class);
        $mock->expects($this->once())
            ->method('getCount')
            ->with('1', 'DummyModel')
            ->willReturn(123);
        DB::setProvider($mock);
        $result = DB::getCount('1', 'DummyModel');
        self::assertSame(123, $result);
    }

    public function testIncrement(): void
    {
        $mock = $this->createMock(MysqlDataProvider::class);
        $mock->expects($this->once())
            ->method('increment')
            ->with('1', 'key', 0)
            ->willReturn(123);
        DB::setProvider($mock);
        $result = DB::increment('1', 'key', 0);
        self::assertSame(123, $result);
    }

}