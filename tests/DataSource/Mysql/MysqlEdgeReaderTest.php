<?php

namespace Mrap\GraphCool\Tests\DataSource\Mysql;

use Closure;
use GraphQL\Error\Error;
use Mrap\GraphCool\DataSource\Mysql\Mysql;
use Mrap\GraphCool\DataSource\Mysql\MysqlConnector;
use Mrap\GraphCool\DataSource\Mysql\MysqlEdgeReader;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Enums\ResultType;
use stdClass;

class MysqlEdgeReaderTest extends TestCase
{

    public function testLoadEdgesBare(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $reader = new MysqlEdgeReader();
        $node = new stdClass();
        $node->id = 'asdf123123';
        $node->tenant_id = 'hc123';

        $result = $reader->loadEdges($node, 'DummyModel');

        self::assertInstanceOf(Closure::class, $result->belongs_to);
        self::assertInstanceOf(Closure::class, $result->belongs_to2);
        self::assertInstanceOf(Closure::class, $result->belongs_to_many);
        self::assertInstanceOf(Closure::class, $result->has_one);
    }

    public function testBelongsTo()
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $tmp = new stdClass();
        $tmp->parent_id = 'parent';
        $tmp->child_id = 'child';

        $expected = new stdClass();
        $expected->id = 'id123123';
        $expected->created_at = '2021-08-30';
        $expected->updated_at = null;
        $expected->deleted_at = null;
        $expected->parent_id = 'parent';
        $expected->child_id = 'child';

        $property = new stdClass();
        $property->property = 'pivot_property';
        $property->value_string = 'pivot value!';

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->exactly(2))
            ->method('fetchAll')
            ->withAnyParameters()
            ->willReturnOnConsecutiveCalls([$tmp],[$property]);
        $mock->expects($this->exactly(1))
            ->method('fetch')
            ->withAnyParameters()
            ->willReturn($expected);
        Mysql::setConnector($mock);

        $reader = new MysqlEdgeReader();
        $node = new stdClass();
        $node->id = 'asdf123123';
        $node->tenant_id = 'hc123';

        $node = $reader->loadEdges($node, 'DummyModel');
        $closure = $node->belongs_to;

        $args = [];
        $result = $closure($args);

        self::assertEquals($expected, $result);
    }

    public function testBelongsToError()
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $reader = new MysqlEdgeReader();
        $node = new stdClass();
        $node->id = 'asdf123123';
        $node->tenant_id = 'hc123';

        $node = $reader->loadEdges($node, 'DummyModel');
        $closure = $node->belongs_to;

        $args = ['page' => 0];
        $this->expectException(Error::class);
        $closure($args);
    }

    public function testBelongsToSoftDeleted()
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $tmp = new stdClass();
        $tmp->parent_id = 'parent';
        $tmp->child_id = 'child';

        $expected = new stdClass();
        $expected->id = 'id123123';
        $expected->created_at = '2021-08-30';
        $expected->updated_at = null;
        $expected->deleted_at = null;
        $expected->parent_id = 'parent';
        $expected->child_id = 'child';

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->exactly(2))
            ->method('fetchAll')
            ->withAnyParameters()
            ->willReturnOnConsecutiveCalls([$tmp],[]);
        $mock->expects($this->exactly(1))
            ->method('fetch')
            ->withAnyParameters()
            ->willReturn($expected);
        Mysql::setConnector($mock);

        $reader = new MysqlEdgeReader();
        $node = new stdClass();
        $node->id = 'asdf123123';
        $node->tenant_id = 'hc123';

        $node = $reader->loadEdges($node, 'DummyModel');
        $closure = $node->belongs_to;

        $args['result'] = ResultType::ONLY_SOFT_DELETED;
        $result = $closure($args);

        self::assertEquals($expected, $result);
    }

    public function testBelongsToWithTrashed()
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $tmp = new stdClass();
        $tmp->parent_id = 'parent';
        $tmp->child_id = 'child';

        $expected = new stdClass();
        $expected->id = 'id123123';
        $expected->created_at = '2021-08-30';
        $expected->updated_at = null;
        $expected->deleted_at = null;
        $expected->parent_id = 'parent';
        $expected->child_id = 'child';

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->exactly(2))
            ->method('fetchAll')
            ->withAnyParameters()
            ->willReturnOnConsecutiveCalls([$tmp],[]);
        $mock->expects($this->exactly(1))
            ->method('fetch')
            ->withAnyParameters()
            ->willReturn($expected);
        Mysql::setConnector($mock);

        $reader = new MysqlEdgeReader();
        $node = new stdClass();
        $node->id = 'asdf123123';
        $node->tenant_id = 'hc123';

        $node = $reader->loadEdges($node, 'DummyModel');
        $closure = $node->belongs_to;

        $args['result'] = ResultType::WITH_TRASHED;
        $result = $closure($args);

        self::assertEquals($expected, $result);
    }


    public function testBelongsToMany()
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $tmp = new stdClass();
        $tmp->parent_id = 'parent';
        $tmp->child_id = 'child';

        $edge = new stdClass();
        $edge->id = 'id123123';
        $edge->created_at = '2021-08-30';
        $edge->updated_at = null;
        $edge->deleted_at = null;
        $edge->parent_id = 'parent';
        $edge->child_id = 'child';

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->exactly(2))
            ->method('fetchAll')
            ->withAnyParameters()
            ->willReturnOnConsecutiveCalls([$tmp],[]);
        $mock->expects($this->exactly(1))
            ->method('fetch')
            ->withAnyParameters()
            ->willReturn($edge);
        Mysql::setConnector($mock);

        $reader = new MysqlEdgeReader();
        $node = new stdClass();
        $node->id = 'asdf123123';
        $node->tenant_id = 'hc123';

        $node = $reader->loadEdges($node, 'DummyModel');
        $closure = $node->belongs_to_many;

        $args = [];
        $result = $closure($args);

        $expected = [
            'paginatorInfo' => (object)[
                'count' => 1,
                'currentPage' => 1,
                'firstItem' => 0,
                'hasMorePages' => false,
                'lastItem' => 0,
                'lastPage' => 1,
                'perPage' => 10,
                'total' => 0
            ],
            'edges' => [$edge]
        ];

        self::assertEquals($expected, $result);
    }

    public function testHasOne()
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $tmp = new stdClass();
        $tmp->parent_id = 'parent';
        $tmp->child_id = 'child';

        $expected = new stdClass();
        $expected->id = 'id123123';
        $expected->created_at = '2021-08-30';
        $expected->updated_at = null;
        $expected->deleted_at = null;
        $expected->parent_id = 'parent';
        $expected->child_id = 'child';


        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->exactly(2))
            ->method('fetchAll')
            ->withAnyParameters()
            ->willReturnOnConsecutiveCalls([$tmp],[]);
        $mock->expects($this->exactly(1))
            ->method('fetch')
            ->withAnyParameters()
            ->willReturn($expected);
        Mysql::setConnector($mock);

        $reader = new MysqlEdgeReader();
        $node = new stdClass();
        $node->id = 'asdf123123';
        $node->tenant_id = 'hc123';

        $node = $reader->loadEdges($node, 'DummyModel');
        $closure = $node->has_one;

        $args = [];
        $result = $closure($args);

        self::assertEquals($expected, $result);
    }

    public function testHasOneError()
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $tmp = new stdClass();
        $tmp->parent_id = 'parent';
        $tmp->child_id = 'child';

        $expected = new stdClass();
        $expected->id = 'id123123';
        $expected->created_at = '2021-08-30';
        $expected->updated_at = null;
        $expected->deleted_at = null;
        $expected->parent_id = 'parent';
        $expected->child_id = 'child';


        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('fetchAll')
            ->withAnyParameters()
            ->willReturn([$tmp]);
        $mock->expects($this->once())
            ->method('fetch')
            ->withAnyParameters()
            ->willReturn(null);
        Mysql::setConnector($mock);

        $reader = new MysqlEdgeReader();
        $node = new stdClass();
        $node->id = 'asdf123123';
        $node->tenant_id = 'hc123';

        $node = $reader->loadEdges($node, 'DummyModel');
        $closure = $node->has_one;

        $args = [];
        $this->expectException(\RuntimeException::class);
        $closure($args);
    }


}