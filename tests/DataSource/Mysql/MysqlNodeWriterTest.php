<?php

namespace Mrap\GraphCool\Tests\DataSource\Mysql;

use GraphQL\Error\Error;
use Mrap\GraphCool\DataSource\File;
use Mrap\GraphCool\DataSource\FileSystem\SystemFileProvider;
use Mrap\GraphCool\DataSource\Mysql\Mysql;
use Mrap\GraphCool\DataSource\Mysql\MysqlConnector;
use Mrap\GraphCool\DataSource\Mysql\MysqlNodeWriter;
use Mrap\GraphCool\Tests\TestCase;
use RuntimeException;

class MysqlNodeWriterTest extends TestCase
{
    public function testInsert(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->atLeast(1))
            ->method('execute')
            ->withAnyParameters();
        Mysql::setConnector($mock);

        $data = [
            'enum' => 'A',
        ];
        $writer = new MysqlNodeWriter();
        $writer->insert('hc132', 'DummyModel', 'asdf123123', $data);
    }

    public function testInsertError(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->atLeast(1))
            ->method('execute')
            ->withAnyParameters();
        Mysql::setConnector($mock);

        $data = [
            'enum' => '',
        ];
        $writer = new MysqlNodeWriter();

        $this->expectException(RuntimeException::class);
        $writer->insert('hc132', 'DummyModel', 'asdf123123', $data);
    }

    public function testUpdate(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->atLeast(1))
            ->method('execute')
            ->withAnyParameters();
        Mysql::setConnector($mock);

        $data = [
            'enum' => 'B',
            'last_name' => null,
        ];
        $writer = new MysqlNodeWriter();

        $writer->update('hc132', 'DummyModel', 'asdf123123', $data);
    }

    public function testUpdateMany(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->atLeast(1))
            ->method('execute')
            ->withAnyParameters();
        Mysql::setConnector($mock);

        $data = [
            'enum' => 'B',
            'last_name' => null,
            'ignoreMe' => 'asdf',
        ];
        $writer = new MysqlNodeWriter();
        $writer->updateMany('hc132', 'DummyModel', ['asdf123123'], $data);
    }

    public function testUpdateManyError(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $data = [
            'unique' => 'asdf',
        ];
        $writer = new MysqlNodeWriter();
        $this->expectException(Error::class);
        $writer->updateMany('hc132', 'DummyModel', ['asdf123123'], $data);
    }

    public function testDelete(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->atLeast(1))
            ->method('execute')
            ->withAnyParameters();
        Mysql::setConnector($mock);
        $writer = new MysqlNodeWriter();
        $writer->delete('hc132', 'asdf123123');
    }

    public function testRestore(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->atLeast(1))
            ->method('execute')
            ->withAnyParameters();
        Mysql::setConnector($mock);
        $writer = new MysqlNodeWriter();
        $writer->restore('hc132', 'asdf123123');
    }
}