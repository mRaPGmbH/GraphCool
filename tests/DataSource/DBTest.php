<?php

namespace Mrap\GraphCool\Tests\DataSource;

use Mrap\GraphCool\DataSource\DB;
use Mrap\GraphCool\DataSource\Mysql\MysqlDataProvider;
use Mrap\GraphCool\Tests\TestCase;

class DBTest extends TestCase
{
    public function testLoad(): void
    {
        $mock = $this->createMock(MysqlDataProvider::class);
        $expected = (object)['d'];
        $mock->expects($this->once())
            ->method('load')
            ->with('a','b','c')
            ->willReturn($expected);
        DB::setProvider($mock);
        $result = DB::load('a','b','c');
        self::assertEquals($expected, $result);
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
        self::assertEquals(2, $result);
    }

    public function testFindAll(): void
    {
        $mock = $this->createMock(MysqlDataProvider::class);
        $expected = (object)['d'];
        $mock->expects($this->once())
            ->method('findAll')
            ->with('a','b',['c'])
            ->willReturn($expected);
        DB::setProvider($mock);
        $result = DB::findAll('a','b',['c']);
        self::assertEquals($expected, $result);
    }

    public function testInsert(): void
    {
        $mock = $this->createMock(MysqlDataProvider::class);
        $expected = (object)['d'];
        $mock->expects($this->once())
            ->method('insert')
            ->with('a','b',['c'])
            ->willReturn($expected);
        DB::setProvider($mock);
        $result = DB::insert('a','b',['c']);
        self::assertEquals($expected, $result);
    }

    public function testUpdate(): void
    {
        $mock = $this->createMock(MysqlDataProvider::class);
        $expected = (object)['d'];
        $mock->expects($this->once())
            ->method('update')
            ->with('a','b',['c'])
            ->willReturn($expected);
        DB::setProvider($mock);
        $result = DB::update('a','b',['c']);
        self::assertEquals($expected, $result);
    }

    public function testUpdateAll(): void
    {
        $mock = $this->createMock(MysqlDataProvider::class);
        $expected = (object)['d'];
        $mock->expects($this->once())
            ->method('updateMany')
            ->with('a','b',['c'])
            ->willReturn($expected);
        DB::setProvider($mock);
        $result = DB::updateAll('a','b',['c']);
        self::assertEquals($expected, $result);
    }

    public function testDelete(): void
    {
        $mock = $this->createMock(MysqlDataProvider::class);
        $expected = (object)['d'];
        $mock->expects($this->once())
            ->method('delete')
            ->with('a','b','c')
            ->willReturn($expected);
        DB::setProvider($mock);
        $result = DB::delete('a','b','c');
        self::assertEquals($expected, $result);
    }

    public function testRestore(): void
    {
        $mock = $this->createMock(MysqlDataProvider::class);
        $expected = (object)['d'];
        $mock->expects($this->once())
            ->method('restore')
            ->with('a','b','c')
            ->willReturn($expected);
        DB::setProvider($mock);
        $result = DB::restore('a','b','c');
        self::assertEquals($expected, $result);
    }

    public function testMigrate(): void
    {
        $mock = $this->createMock(MysqlDataProvider::class);
        $mock->expects($this->once())
            ->method('migrate');
        DB::setProvider($mock);
        DB::migrate();
    }


}