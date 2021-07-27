<?php


namespace Mrap\GraphCool\Tests\DataSource\Mysql;


use Mrap\GraphCool\DataSource\Mysql\Mysql;
use Mrap\GraphCool\DataSource\Mysql\MysqlConnector;
use Mrap\GraphCool\DataSource\Mysql\MysqlDataProvider;
use Mrap\GraphCool\Tests\TestCase;

class MysqlDataProviderTest extends TestCase
{
    public function xtestFindAll(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('fetchAll')
            ->withAnyParameters()
            ->willReturn([(object)['id'=>'y5']]);
        Mysql::setConnector($mock);

        $provider = new MysqlDataProvider();
        $result = $provider->findAll('a12f', 'DummyModel', []);
        var_dump($result);
        self::assertTrue(true);
    }
}