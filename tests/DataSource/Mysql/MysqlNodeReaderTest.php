<?php

namespace Mrap\GraphCool\Tests\DataSource\Mysql;

use Mrap\GraphCool\DataSource\Mysql\Mysql;
use Mrap\GraphCool\DataSource\Mysql\MysqlConnector;
use Mrap\GraphCool\DataSource\Mysql\MysqlNodeReader;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Enums\Result;
use RuntimeException;
use stdClass;

class MysqlNodeReaderTest extends TestCase
{

    public function testLoad(): void
    {
        $this->mockLoad();

        $reader = new MysqlNodeReader();
        $result = $reader->load('hc123', 'asdf123123');

        self::assertEquals('Huber', $result->last_name);
    }

    public function testLoadSoftDeleted(): void
    {
        $this->mockLoad();

        $reader = new MysqlNodeReader();
        $result = $reader->load('hc123', 'asdf123123', Result::ONLY_SOFT_DELETED);

        self::assertEquals('Huber', $result->last_name);
    }

    public function testLoadWithTrashed(): void
    {
        $this->mockLoad();

        $reader = new MysqlNodeReader();
        $result = $reader->load('hc123', 'asdf123123', Result::WITH_TRASHED);

        self::assertEquals('Huber', $result->last_name);
    }

    protected function mockLoad(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $node = new stdClass();
        $node->id = 'asdf123123';
        $node->tenant_id = 'hc123';
        $node->created_at = '2021-08-30 00:00:00';
        $node->updated_at = null;
        $node->deleted_at = null;
        $node->model = 'DummyModel';

        $property = new stdClass();
        $property->node_id = 'asdf123123';
        $property->property = 'last_name';
        $property->value_string = 'Huber';
        $property->updated_at = null;
        $property->deleted_at = null;
        $property->created_at = '2021-08-30 00:00:00';
        $property->model = 'DummyModel';

        $property2 = new stdClass();
        $property2->node_id = 'asdf123123';
        $property2->property = 'does not exist';
        $property2->value_string = 'asdf';
        $property2->updated_at = null;
        $property2->deleted_at = null;
        $property2->created_at = '2021-08-30 00:00:00';
        $property2->model = 'DummyModel';

        $property3 = new stdClass();
        $property3->node_id = 'asdf123123';
        $property3->property = 'belongs_to';
        $property3->value_string = 'asdf';
        $property3->updated_at = null;
        $property3->deleted_at = null;
        $property3->created_at = '2021-08-30 00:00:00';
        $property3->model = 'DummyModel';

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->exactly(2))
            ->method('fetchAll')
            ->withAnyParameters()
            ->willReturnOnConsecutiveCalls([$node], [$property, $property2, $property3]);
        Mysql::setConnector($mock);
    }

    public function xtestLoadError(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $node = new stdClass();
        $node->id = 'asdf123123';
        $node->tenant_id = 'hc123';
        $node->created_at = '2021-08-30 00:00:00';
        $node->updated_at = null;
        $node->deleted_at = null;
        $node->model = 'someOtherModel';

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('fetch')
            ->withAnyParameters()
            ->willReturn($node);
        Mysql::setConnector($mock);

        $reader = new MysqlNodeReader();
        $this->expectException(RuntimeException::class);
        $reader->load('hc123', 'DummyModel', 'asdf123123');
    }


}