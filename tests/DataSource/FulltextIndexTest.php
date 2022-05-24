<?php

namespace Mrap\GraphCool\Tests\DataSource;

use Mrap\GraphCool\DataSource\DB;
use Mrap\GraphCool\DataSource\FullTextIndex;
use Mrap\GraphCool\DataSource\FullTextIndexProvider;
use Mrap\GraphCool\DataSource\Mysql\MysqlDataProvider;
use Mrap\GraphCool\Tests\TestCase;

class FulltextIndexTest extends TestCase
{

    public function testIndex(): void
    {
        $mock = $this->createMock(FullTextIndexProvider::class);
        $mock->expects($this->once())
            ->method('index')
            ->with('1','DummyModel','some-id-123');
        FullTextIndex::setProvider($mock);
        FullTextIndex::index('1','DummyModel','some-id-123');
    }

    public function testDelete(): void
    {
        $mock = $this->createMock(FullTextIndexProvider::class);
        $mock->expects($this->once())
            ->method('delete')
            ->with('1','DummyModel','some-id-123');
        FullTextIndex::setProvider($mock);
        FullTextIndex::delete('1','DummyModel','some-id-123');
    }

    public function testSearch(): void
    {
        $mock = $this->createMock(FullTextIndexProvider::class);
        $mock->expects($this->once())
            ->method('search')
            ->with('1','searchword')
            ->willReturn(['array']);
        FullTextIndex::setProvider($mock);
        $result = FullTextIndex::search('1','searchword');
        self::assertEquals(['array'], $result);
    }

    public function testShutdown(): void
    {
        $mock = $this->createMock(FullTextIndexProvider::class);
        $mock->expects($this->once())
            ->method('shutdown');
        FullTextIndex::setProvider($mock);
        FullTextIndex::shutdown();
    }


}