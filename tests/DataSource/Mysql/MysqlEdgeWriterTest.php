<?php

namespace Mrap\GraphCool\Tests\DataSource\Mysql;

use Mrap\GraphCool\DataSource\Mysql\Mysql;
use Mrap\GraphCool\DataSource\Mysql\MysqlConnector;
use Mrap\GraphCool\DataSource\Mysql\MysqlEdgeWriter;
use Mrap\GraphCool\Tests\TestCase;

class MysqlEdgeWriterTest extends TestCase
{
    public function testWriteEdges(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->atLeast(1))
            ->method('execute')
            ->withAnyParameters();
        Mysql::setConnector($mock);

        $data = [
            'has_one' => null,
            'belongs_to' => [
                'id' => 'other123123',
                'pivot_property' => null,
                'pivot_property2' => 'pivot value!'
            ],
        ];
        $writer = new MysqlEdgeWriter();
        $writer->insertEdges('hc132', 'DummyModel', 'asdf123123', $data);
    }

    public function testWriteEdgesBelongsToMany(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->atLeast(1))
            ->method('execute')
            ->withAnyParameters();
        $mock->expects($this->once())
            ->method('fetchAll')
            ->withAnyParameters()
            ->willReturn([(object)['id' => 'other123123']]);
        Mysql::setConnector($mock);

        $data = [
            'belongs_to_many' => [
                [
                    'id' => 'other123123',
                    'pivot_property' => null,
                    'pivot_property2' => 'pivot value!',
                    'mode' => 'REPLACE',
                ]
            ],
        ];
        $writer = new MysqlEdgeWriter();
        $writer->insertEdges('hc132', 'DummyModel', 'asdf123123', $data);
    }

    public function testWriteEdgesBelongsToManyRemove(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->atLeast(1))
            ->method('execute')
            ->withAnyParameters();
        $mock->expects($this->once())
            ->method('fetchAll')
            ->withAnyParameters()
            ->willReturn([(object)['id' => 'other123123']]);
        Mysql::setConnector($mock);

        $data = [
            'belongs_to_many' => [
                [
                    'id' => 'other123123',
                    'pivot_property' => null,
                    'pivot_property2' => 'pivot value!',
                    'mode' => 'REMOVE',
                ]
            ],
        ];
        $writer = new MysqlEdgeWriter();
        $writer->insertEdges('hc132', 'DummyModel', 'asdf123123', $data);
    }

    public function testUpdateEdges(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->atLeast(1))
            ->method('execute')
            ->withAnyParameters();
        Mysql::setConnector($mock);

        $data = [
            'has_one' => null,
            'belongs_to' => [
                'id' => 'other123123',
                'pivot_property' => null,
                'pivot_property2' => 'pivot value!'
            ],
        ];
        $writer = new MysqlEdgeWriter();
        $writer->updateEdges('hc132', 'DummyModel', ['asdf123123'], $data);
    }

    public function testUpdateEdgesBelongsToMany(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->atLeast(1))
            ->method('execute')
            ->withAnyParameters();
        $mock->expects($this->once())
            ->method('fetchAll')
            ->withAnyParameters()
            ->willReturn([(object)['id' => 'other123123']]);
        Mysql::setConnector($mock);

        $data = [
            'belongs_to_many' => [
                [
                    'id' => 'other123123',
                    'pivot_property' => null,
                    'pivot_property2' => 'pivot value!',
                    'mode' => 'REPLACE',
                ]
            ],
        ];
        $writer = new MysqlEdgeWriter();
        $writer->UpdateEdges('hc132', 'DummyModel', ['asdf123123'], $data);
    }

    public function testUpdateEdgesEmpty(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->never())
            ->method('execute')
            ->withAnyParameters();
        Mysql::setConnector($mock);

        $writer = new MysqlEdgeWriter();
        $writer->updateEdges('hc132', 'DummyModel', [], []);
    }

    public function testWriteEdgesEmpty(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->never())
            ->method('execute')
            ->withAnyParameters();
        Mysql::setConnector($mock);

        $writer = new MysqlEdgeWriter();
        $writer->insertEdges('hc132', 'DummyModel', '', []);
    }

    public function testUpdateEdgesBelongsToManyRemoveEmpty(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->never())
            ->method('execute')
            ->withAnyParameters();
        $mock->expects($this->once())
            ->method('fetchAll')
            ->withAnyParameters()
            ->willReturn([]);
        Mysql::setConnector($mock);

        $data = [
            'belongs_to_many' => [
                [
                    'id' => 'other123123',
                    'pivot_property' => null,
                    'pivot_property2' => 'pivot value!',
                    'mode' => 'REMOVE',
                ]
            ],
        ];
        $writer = new MysqlEdgeWriter();
        $writer->UpdateEdges('hc132', 'DummyModel', ['asdf123123'], $data);
    }

}