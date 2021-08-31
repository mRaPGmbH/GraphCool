<?php

namespace Mrap\GraphCool\Tests\DataSource\Mysql;

use Mrap\GraphCool\DataSource\Mysql\Mysql;
use Mrap\GraphCool\DataSource\Mysql\MysqlConnector;
use Mrap\GraphCool\DataSource\Mysql\MysqlNodeReader;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Enums\ResultType;
use RuntimeException;
use stdClass;

class MysqlNodeReaderTest extends TestCase
{

    public function testLoad(): void
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
        $property->property = 'last_name';
        $property->value_string = 'Huber';

        $property2 = new stdClass();
        $property2->property = 'does not exist';
        $property2->value_string = 'asdf';

        $property3 = new stdClass();
        $property3->property = 'belongs_to';
        $property3->value_string = 'asdf';


        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('fetch')
            ->withAnyParameters()
            ->willReturn($node);
        $mock->expects($this->once())
            ->method('fetchAll')
            ->withAnyParameters()
            ->willReturn([$property, $property2, $property3]);
        Mysql::setConnector($mock);

        $reader = new MysqlNodeReader();
        $result = $reader->load('hc123', 'DummyModel', 'asdf123123');

        self::assertEquals('Huber', $result->last_name);
    }

    public function testLoadSoftDeleted(): void
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
        $property->property = 'last_name';
        $property->value_string = 'Huber';

        $property2 = new stdClass();
        $property2->property = 'does not exist';
        $property2->value_string = 'asdf';

        $property3 = new stdClass();
        $property3->property = 'belongs_to';
        $property3->value_string = 'asdf';


        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('fetch')
            ->withAnyParameters()
            ->willReturn($node);
        $mock->expects($this->once())
            ->method('fetchAll')
            ->withAnyParameters()
            ->willReturn([$property, $property2, $property3]);
        Mysql::setConnector($mock);

        $reader = new MysqlNodeReader();
        $result = $reader->load('hc123', 'DummyModel', 'asdf123123', ResultType::ONLY_SOFT_DELETED);

        self::assertEquals('Huber', $result->last_name);
    }

    public function testLoadWithTrashed(): void
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
        $property->property = 'last_name';
        $property->value_string = 'Huber';

        $property2 = new stdClass();
        $property2->property = 'does not exist';
        $property2->value_string = 'asdf';

        $property3 = new stdClass();
        $property3->property = 'belongs_to';
        $property3->value_string = 'asdf';


        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('fetch')
            ->withAnyParameters()
            ->willReturn($node);
        $mock->expects($this->once())
            ->method('fetchAll')
            ->withAnyParameters()
            ->willReturn([$property, $property2, $property3]);
        Mysql::setConnector($mock);

        $reader = new MysqlNodeReader();
        $result = $reader->load('hc123', 'DummyModel', 'asdf123123', ResultType::WITH_TRASHED);

        self::assertEquals('Huber', $result->last_name);
    }

    public function testLoadError(): void
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