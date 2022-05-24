<?php

namespace Mrap\GraphCool\Tests\DataSource\Mysql;

use Mrap\GraphCool\DataSource\Mysql\Mysql;
use Mrap\GraphCool\DataSource\Mysql\MysqlConnector;
use Mrap\GraphCool\DataSource\Mysql\MysqlFullTextIndexProvider;
use Mrap\GraphCool\Tests\TestCase;
use PDO;
use PDOStatement;

class MysqlFullTextIndexProviderTest extends TestCase
{
    public function testIndex(): void
    {
        $mockStatement = $this->createMock(PDOStatement::class);
        $mockStatement->expects($this->atLeastOnce())
            ->method('execute')
            ->withAnyParameters();

        $mockPdo = $this->createMock(PDO::class);
        $mockPdo->expects($this->atLeastOnce())
            ->method('exec');
        $mockPdo->expects($this->atLeastOnce())
            ->method('prepare')
            ->willReturn($mockStatement);

        $mockConnector = $this->createMock(MysqlConnector::class);
        $mockConnector->expects($this->atLeastOnce())
            ->method('pdo')
            ->willReturn($mockPdo);
        Mysql::setConnector($mockConnector);

        $provider = new MysqlFullTextIndexProvider();
        $provider->index('1', 'DummyModel', 'id-5');
        $provider->shutdown();
    }

    public function testDelete(): void
    {
        $mockPdo = $this->createMock(PDO::class);
        $mockPdo->expects($this->atLeastOnce())
            ->method('exec');

        $mockConnector = $this->createMock(MysqlConnector::class);
        $mockConnector->expects($this->atLeastOnce())
            ->method('pdo')
            ->willReturn($mockPdo);
        Mysql::setConnector($mockConnector);

        $provider = new MysqlFullTextIndexProvider();
        $provider->delete('1', 'DummyModel', 'id-5');
        $provider->shutdown();
    }

    public function testSearch(): void
    {
        $mockStatement = $this->createMock(PDOStatement::class);
        $mockStatement->expects($this->atLeastOnce())
            ->method('fetchAll')
            ->withAnyParameters()
            ->willReturn([(object)['node_id' => 'found-id']]);

        $mockPdo = $this->createMock(PDO::class);
        $mockPdo->expects($this->atLeastOnce())
            ->method('query')
            ->willReturn($mockStatement);

        $mockConnector = $this->createMock(MysqlConnector::class);
        $mockConnector->expects($this->atLeastOnce())
            ->method('pdo')
            ->willReturn($mockPdo);
        Mysql::setConnector($mockConnector);

        $provider = new MysqlFullTextIndexProvider();
        $result = $provider->search('1', 'search words');

        self::assertSame(['found-id'], $result);
    }

}