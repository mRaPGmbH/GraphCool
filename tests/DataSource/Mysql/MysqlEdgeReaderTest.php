<?php

namespace Mrap\GraphCool\Tests\DataSource\Mysql;

use Closure;
use GraphQL\Error\Error;
use Mrap\GraphCool\DataSource\DB;
use Mrap\GraphCool\DataSource\Mysql\Mysql;
use Mrap\GraphCool\DataSource\Mysql\MysqlConnector;
use Mrap\GraphCool\DataSource\Mysql\MysqlDataProvider;
use Mrap\GraphCool\DataSource\Mysql\MysqlEdgeReader;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Enums\Result;
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

        $result = $reader->injectEdgeClosures($node);

        self::assertInstanceOf(Closure::class, $result->belongs_to);
        self::assertInstanceOf(Closure::class, $result->belongs_to2);
        self::assertInstanceOf(Closure::class, $result->belongs_to_many);
        self::assertInstanceOf(Closure::class, $result->has_one);
    }

    public function testBelongsTo()
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $tmp = new stdClass();
        $tmp->_child_id = 'child-id';
        $tmp->_child = 'child-model';
        $tmp->_parent_id = 'parent-id';
        $tmp->_parent = 'parent-model';
        $tmp->id = 'node-id';
        $tmp->model = 'node-model';
        $tmp->created_at = '2021-08-30';
        $tmp->updated_at = null;
        $tmp->deleted_at = null;
        $tmp->_created_at = '2021-08-30';
        $tmp->_updated_at = null;
        $tmp->_deleted_at = null;

        $expected = new stdClass();
        $expected->created_at = '2021-08-30T00:00:00.000Z';
        $expected->parent_id = 'parent-id';
        $expected->child_id = 'child-id';
        $expected->child = 'child-model';
        $expected->parent = 'parent-model';
        $expected->pivot_property = 'pivot value!';
        $expected->_node = (object)['id' => 'node-id'];

        $property = new stdClass();
        $property->property = 'pivot_property';
        $property->value_string = 'pivot value!';
        $property->parent_id = 'parent-id';
        $property->child_id = 'child-id';

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->exactly(2))
            ->method('fetchAll')
            ->withAnyParameters()
            ->willReturnOnConsecutiveCalls([$tmp],[$property]);
        Mysql::setConnector($mock);

        $dummyNode = new stdClass();
        $dummyNode->id = 'node-id';
        $mock2 = $this->createMock(MysqlDataProvider::class);
        $mock2->expects($this->once())
            ->method('findAll')
            ->willReturn((object)['data' => function() use ($dummyNode){return ['node-id' => $dummyNode];}]);
        DB::setProvider($mock2);

        $reader = new MysqlEdgeReader();
        $node = new stdClass();
        $node->id = 'asdf123123';
        $node->tenant_id = 'hc123';

        $node = $reader->injectEdgeClosures($node);
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

        $node = $reader->injectEdgeClosures($node);
        $closure = $node->belongs_to;

        $args = ['page' => 0];
        $this->expectException(Error::class);
        $closure($args);
    }

    public function testBelongsToSoftDeleted()
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $tmp = new stdClass();
        $tmp->_child_id = 'child-id';
        $tmp->_child = 'child-model';
        $tmp->_parent_id = 'parent-id';
        $tmp->_parent = 'parent-model';
        $tmp->id = 'node-id';
        $tmp->model = 'node-model';
        $tmp->created_at = '2021-08-30';
        $tmp->updated_at = null;
        $tmp->deleted_at = null;
        $tmp->_created_at = '2021-08-30';
        $tmp->_updated_at = null;
        $tmp->_deleted_at = null;

        $expected = new stdClass();
        $expected->created_at = '2021-08-30T00:00:00.000Z';
        $expected->parent_id = 'parent-id';
        $expected->child_id = 'child-id';
        $expected->parent = 'parent-model';
        $expected->child = 'child-model';
        $expected->_node = (object)['id' => 'node-id'];

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->exactly(2))
            ->method('fetchAll')
            ->withAnyParameters()
            ->willReturnOnConsecutiveCalls([$tmp],[]);
        Mysql::setConnector($mock);

        $dummyNode = new stdClass();
        $dummyNode->id = 'node-id';
        $mock2 = $this->createMock(MysqlDataProvider::class);
        $mock2->expects($this->once())
            ->method('findAll')
            ->willReturn((object)['data' => function() use ($dummyNode){return ['node-id' => $dummyNode];}]);
        DB::setProvider($mock2);

        $reader = new MysqlEdgeReader();
        $node = new stdClass();
        $node->id = 'asdf123123';
        $node->tenant_id = 'hc123';

        $node = $reader->injectEdgeClosures($node);
        $closure = $node->belongs_to;

        $args['result'] = Result::ONLY_SOFT_DELETED;
        $result = $closure($args);

        self::assertEquals($expected, $result);
    }

    public function testBelongsToWithTrashed()
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $tmp = new stdClass();
        $tmp->_child_id = 'child-id';
        $tmp->_child = 'child-model';
        $tmp->_parent_id = 'parent-id';
        $tmp->_parent = 'parent-model';
        $tmp->id = 'node-id';
        $tmp->model = 'node-model';
        $tmp->created_at = '2021-08-30';
        $tmp->updated_at = null;
        $tmp->deleted_at = null;
        $tmp->_created_at = '2021-08-30';
        $tmp->_updated_at = null;
        $tmp->_deleted_at = null;

        $expected = new stdClass();
        $expected->created_at = '2021-08-30T00:00:00.000Z';
        $expected->parent_id = 'parent-id';
        $expected->child_id = 'child-id';
        $expected->parent = 'parent-model';
        $expected->child = 'child-model';
        $expected->_node = (object)['id' => 'node-id'];

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->exactly(2))
            ->method('fetchAll')
            ->withAnyParameters()
            ->willReturnOnConsecutiveCalls([$tmp],[]);
        Mysql::setConnector($mock);

        $dummyNode = new stdClass();
        $dummyNode->id = 'node-id';
        $mock2 = $this->createMock(MysqlDataProvider::class);
        $mock2->expects($this->once())
            ->method('findAll')
            ->willReturn((object)['data' => function() use ($dummyNode){return ['node-id' => $dummyNode];}]);
        DB::setProvider($mock2);


        $reader = new MysqlEdgeReader();
        $node = new stdClass();
        $node->id = 'asdf123123';
        $node->tenant_id = 'hc123';

        $node = $reader->injectEdgeClosures($node);
        $closure = $node->belongs_to;

        $args['result'] = Result::WITH_TRASHED;
        $result = $closure($args);

        self::assertEquals($expected, $result);
    }

    public function testBelongsToWithNonTrashedEdgesOfAnyNode()
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $tmp = new stdClass();
        $tmp->_child_id = 'child-id';
        $tmp->_child = 'child-model';
        $tmp->_parent_id = 'parent-id';
        $tmp->_parent = 'parent-model';
        $tmp->id = 'node-id';
        $tmp->model = 'node-model';
        $tmp->created_at = '2021-08-30';
        $tmp->updated_at = null;
        $tmp->deleted_at = null;
        $tmp->_created_at = '2021-08-30';
        $tmp->_updated_at = null;
        $tmp->_deleted_at = null;

        $expected = new stdClass();
        $expected->created_at = '2021-08-30T00:00:00.000Z';
        $expected->parent_id = 'parent-id';
        $expected->child_id = 'child-id';
        $expected->parent = 'parent-model';
        $expected->child = 'child-model';
        $expected->_node = (object)['id' => 'node-id'];

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->exactly(2))
            ->method('fetchAll')
            ->withAnyParameters()
            ->willReturnOnConsecutiveCalls([$tmp],[]);
        Mysql::setConnector($mock);

        $dummyNode = new stdClass();
        $dummyNode->id = 'node-id';
        $mock2 = $this->createMock(MysqlDataProvider::class);
        $mock2->expects($this->once())
            ->method('findAll')
            ->willReturn((object)['data' => function() use ($dummyNode){return ['node-id' => $dummyNode];}]);
        DB::setProvider($mock2);


        $reader = new MysqlEdgeReader();
        $node = new stdClass();
        $node->id = 'asdf123123';
        $node->tenant_id = 'hc123';

        $node = $reader->injectEdgeClosures($node);
        $closure = $node->belongs_to;

        $args['result'] = 'NONTRASHED_EDGES_OF_ANY_NODES';
        $result = $closure($args);

        self::assertEquals($expected, $result);
    }


    public function testBelongsToMany()
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $tmp = new stdClass();
        $tmp->_child_id = 'child-id';
        $tmp->_child = 'child-model';
        $tmp->_parent_id = 'parent-id';
        $tmp->_parent = 'parent-model';
        $tmp->id = 'node-id';
        $tmp->model = 'node-model';
        $tmp->created_at = '2021-08-30';
        $tmp->updated_at = null;
        $tmp->deleted_at = null;
        $tmp->_created_at = '2021-08-30';
        $tmp->_updated_at = null;
        $tmp->_deleted_at = null;

        $edge = new stdClass();
        $edge->created_at = '2021-08-30T00:00:00.000Z';
        $edge->parent_id = 'parent-id';
        $edge->child_id = 'child-id';
        $edge->parent = 'parent-model';
        $edge->child = 'child-model';
        $edge->_node = (object)['id' => 'node-id'];

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->exactly(2))
            ->method('fetchAll')
            ->withAnyParameters()
            ->willReturnOnConsecutiveCalls([$tmp],[]);
        Mysql::setConnector($mock);

        $dummyNode = new stdClass();
        $dummyNode->id = 'node-id';
        $mock2 = $this->createMock(MysqlDataProvider::class);
        $mock2->expects($this->once())
            ->method('findAll')
            ->willReturn((object)['data' => function() use ($dummyNode){return ['node-id' => $dummyNode];}]);
        DB::setProvider($mock2);

        $reader = new MysqlEdgeReader();
        $node = new stdClass();
        $node->id = 'asdf123123';
        $node->tenant_id = 'hc123';

        $node = $reader->injectEdgeClosures($node);
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
        $tmp->_child_id = 'child-id';
        $tmp->_child = 'child-model';
        $tmp->_parent_id = 'parent-id';
        $tmp->_parent = 'parent-model';
        $tmp->id = 'node-id';
        $tmp->model = 'node-model';
        $tmp->created_at = '2021-08-30';
        $tmp->updated_at = null;
        $tmp->deleted_at = null;
        $tmp->_created_at = '2021-08-30';
        $tmp->_updated_at = null;
        $tmp->_deleted_at = null;

        $expected = new stdClass();
        $expected->created_at = '2021-08-30T00:00:00.000Z';
        $expected->parent_id = 'parent-id';
        $expected->child_id = 'child-id';
        $expected->parent = 'parent-model';
        $expected->child = 'child-model';
        $expected->_node = (object)['id' => 'node-id'];

        $dummyNode = new stdClass();
        $dummyNode->id = 'node-id';
        $mock2 = $this->createMock(MysqlDataProvider::class);
        $mock2->expects($this->once())
            ->method('findAll')
            ->willReturn((object)['data' => function() use ($dummyNode){return ['node-id' => $dummyNode];}]);
        DB::setProvider($mock2);

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->exactly(2))
            ->method('fetchAll')
            ->withAnyParameters()
            ->willReturnOnConsecutiveCalls([$tmp],[]);
        Mysql::setConnector($mock);

        $reader = new MysqlEdgeReader();
        $node = new stdClass();
        $node->id = 'asdf123123';
        $node->tenant_id = 'hc123';

        $node = $reader->injectEdgeClosures($node);
        $closure = $node->has_one;

        $args = [];
        $result = $closure($args);

        self::assertEquals($expected, $result);
    }

    public function xtestHasOneError()
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $tmp = new stdClass();
        $tmp->_child_id = 'child-id';
        $tmp->_child = 'child-model';
        $tmp->_parent_id = 'parent-id';
        $tmp->_parent = 'parent-model';
        $tmp->property = 'property';
        $tmp->id = 'node-id';
        $tmp->model = 'node-model';
        $tmp->created_at = '2021-08-30';
        $tmp->updated_at = null;
        $tmp->deleted_at = null;
        $tmp->_created_at = '2021-08-30';
        $tmp->_updated_at = null;
        $tmp->_deleted_at = null;

        $expected = new stdClass();
        $expected->created_at = '2021-08-30T00:00:00.000Z';
        $expected->parent_id = 'parent-id';
        $expected->child_id = 'child-id';
        $expected->parent = 'parent-model';
        $expected->child = 'child-model';
        $expected->_node = (object)['id' => 'node-id'];

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->exactly(2))
            ->method('fetchAll')
            ->withAnyParameters()
            ->willReturnOnConsecutiveCalls([$tmp], []);
        Mysql::setConnector($mock);

        $dummyNode = new stdClass();
        $dummyNode->id = 'node-id';
        $mock2 = $this->createMock(MysqlDataProvider::class);
        $mock2->expects($this->once())
            ->method('findAll')
            ->willReturn((object)['data' => function() use ($dummyNode){return ['node-id' => $dummyNode];}]);
        DB::setProvider($mock2);

        $reader = new MysqlEdgeReader();
        $node = new stdClass();
        $node->id = 'asdf123123';
        $node->tenant_id = 'hc123';

        $node = $reader->injectEdgeClosures($node);
        $closure = $node->has_one;

        $args = [];
        $this->expectException(\RuntimeException::class);
        $closure($args);
    }


}