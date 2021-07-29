<?php


namespace Mrap\GraphCool\Tests\DataSource\Mysql;


use GraphQL\Error\Error;
use Mrap\GraphCool\DataSource\Mysql\Mysql;
use Mrap\GraphCool\DataSource\Mysql\MysqlConnector;
use Mrap\GraphCool\DataSource\Mysql\MysqlDataProvider;
use Mrap\GraphCool\Tests\TestCase;
use RuntimeException;

class MysqlDataProviderTest // extends TestCase
{
    public function testFindAll(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('fetchAll')
            ->withAnyParameters()
            ->willReturn([(object)['id'=>'y5']]);
        $mock->expects($this->once())
            ->method('fetchColumn')
            ->withAnyParameters()
            ->willReturn('1');

        Mysql::setConnector($mock);

        $provider = new MysqlDataProvider();
        $result = $provider->findAll('a12f', 'DummyModel', []);
        $expected = (object)[
            'count' => 1,
            'currentPage' => 1,
            'firstItem' => 1,
            'hasMorePages' => false,
            'lastItem' => 1,
            'lastPage' => 1,
            'perPage' => 10,
            'total' => 1
        ];
        self::assertSame($expected, $result->paginatorInfo);
    }

    public function testFindAllError(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $this->expectException(Error::class);
        $provider = new MysqlDataProvider();
        $provider->findAll('a12f', 'DummyModel', ['page' => 0]);
    }

    public function testFindAllWhere(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('fetchAll')
            ->withAnyParameters()
            ->willReturn([(object)['id'=>'y5']]);
        Mysql::setConnector($mock);

        $provider = new MysqlDataProvider();
        $result = $provider->findAll('a12f', 'DummyModel', ['where' => ['column' => 'last_name', 'operator' => '=', 'value' => 'b']]);
        $expected = (object)[
            'count' => 1,
            'currentPage' => 1,
            'firstItem' => 0,
            'hasMorePages' => false,
            'lastItem' => 0,
            'lastPage' => 1,
            'perPage' => 10,
            'total' => 0
        ];
        self::assertSame($expected, $result->paginatorInfo);
    }

    public function testFindAllDeleted(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('fetchAll')
            ->withAnyParameters()
            ->willReturn([(object)['id'=>'y5']]);
        Mysql::setConnector($mock);

        $provider = new MysqlDataProvider();
        $result = $provider->findAll('a12f', 'DummyModel', ['result' => 'ONLY_SOFT_DELETED']);
        $expected = (object)[
            'count' => 1,
            'currentPage' => 1,
            'firstItem' => 0,
            'hasMorePages' => false,
            'lastItem' => 0,
            'lastPage' => 1,
            'perPage' => 10,
            'total' => 0
        ];
        self::assertSame($expected, $result->paginatorInfo);
    }

    public function testFindAllWithTrashed(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('fetchAll')
            ->withAnyParameters()
            ->willReturn([(object)['id'=>'y5']]);
        Mysql::setConnector($mock);

        $provider = new MysqlDataProvider();
        $result = $provider->findAll('a12f', 'DummyModel', ['result' => 'WITH_TRASHED']);
        $expected = (object)[
            'count' => 1,
            'currentPage' => 1,
            'firstItem' => 0,
            'hasMorePages' => false,
            'lastItem' => 0,
            'lastPage' => 1,
            'perPage' => 10,
            'total' => 0
        ];
        self::assertSame($expected, $result->paginatorInfo);
    }

    public function testGetMax(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('fetch')
            ->withAnyParameters()
            ->willReturn((object)['max' => 'Zander']);
        Mysql::setConnector($mock);

        $provider = new MysqlDataProvider();
        $result = $provider->getMax('a12f', 'DummyModel', 'last_name');

        self::assertSame('Zander', $result);
    }

    public function testGetMaxInt(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('fetch')
            ->withAnyParameters()
            ->willReturn((object)['max' => 5]);
        Mysql::setConnector($mock);

        $provider = new MysqlDataProvider();
        $result = $provider->getMax('a12f', 'DummyModel', 'date');

        self::assertSame(5, $result);
    }

    public function testGetMaxFloat(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('fetch')
            ->withAnyParameters()
            ->willReturn((object)['max' => 3.76]);
        Mysql::setConnector($mock);

        $provider = new MysqlDataProvider();
        $result = $provider->getMax('a12f', 'DummyModel', 'float');

        self::assertSame(3.76, $result);
    }

    public function testLoad(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $node = (object)[
            'id' => 'a1',
            'tenant_id' => 'a12f',
            'created_at' => '2021-07-27 13:43:56',
            'updated_at' => '2021-07-27 13:43:57',
            'deleted_at' => null,
            'model' => 'DummyModel'
        ];
        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('fetch')
            ->with('SELECT * FROM `node` WHERE `id` = :id AND `node`.`tenant_id` = :tenant_id AND `deleted_at` IS NULL ', [':id' => 'a1', ':tenant_id' => 'a12f'])
            ->willReturn($node);
        $mock->expects($this->once())
            ->method('fetchAll')
            ->with('SELECT * FROM `node_property` WHERE `node_id` = :node_id AND `deleted_at` IS NULL', [':node_id' => 'a1'])
            ->willReturn([
                (object)['property' => 'last_name', 'value_int' => null, 'value_string' => 'Huber', 'value_float' => null],
                (object)['property' => 'float', 'value_int' => null, 'value_string' => null, 'value_float' => 1.234],
                (object)['property' => 'decimal', 'value_int' => 123, 'value_string' => null, 'value_float' => null],
                (object)['property' => 'bool', 'value_int' => 1, 'value_string' => null, 'value_float' => null],
            ]);
        Mysql::setConnector($mock);

        $provider = new MysqlDataProvider();
        $result = $provider->load('a12f', 'DummyModel', 'a1');
        self::assertSame('Huber', $result->last_name);
        self::assertSame(1.234, $result->float);
        self::assertSame(1.23, $result->decimal);
        self::assertTrue($result->bool);
    }

    public function testLoadError(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $node = (object)[
            'id' => 'a1',
            'tenant_id' => 'a12f',
            'created_at' => '2021-07-27 13:43:56',
            'updated_at' => '2021-07-27 13:43:57',
            'deleted_at' => null,
            'model' => 'NOT-DummyModel'
        ];
        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('fetch')
            ->with('SELECT * FROM `node` WHERE `id` = :id AND `node`.`tenant_id` = :tenant_id AND `deleted_at` IS NULL ', [':id' => 'a1', ':tenant_id' => 'a12f'])
            ->willReturn($node);
        Mysql::setConnector($mock);

        $provider = new MysqlDataProvider();

        $this->expectException(RuntimeException::class);
        $provider->load('a12f', 'DummyModel', 'a1');
    }

    public function testLoadNonExistentFields(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $node = (object)[
            'id' => 'a1',
            'tenant_id' => 'a12f',
            'created_at' => '2021-07-27 13:43:56',
            'updated_at' => '2021-07-27 13:43:57',
            'deleted_at' => null,
            'model' => 'DummyModel'
        ];
        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('fetch')
            ->with('SELECT * FROM `node` WHERE `id` = :id AND `node`.`tenant_id` = :tenant_id AND `deleted_at` IS NULL ', [':id' => 'a1', ':tenant_id' => 'a12f'])
            ->willReturn($node);
        $mock->expects($this->once())
            ->method('fetchAll')
            ->with('SELECT * FROM `node_property` WHERE `node_id` = :node_id AND `deleted_at` IS NULL', [':node_id' => 'a1'])
            ->willReturn([
                (object)['property' => 'does_not_exist', 'value_int' => null, 'value_string' => 'Huber', 'value_float' => null],
                (object)['property' => 'belongs_to_many', 'value_int' => null, 'value_string' => 'Huber', 'value_float' => null],
            ]);
        Mysql::setConnector($mock);

        $provider = new MysqlDataProvider();
        $result = $provider->load('a12f', 'DummyModel', 'a1');
        self::assertObjectNotHasAttribute('does_not_exist', $result);
        self::assertIsCallable($result->belongs_to_many);
    }

    public function testLoadRelationClosures(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $node = (object)[
            'id' => 'a1',
            'tenant_id' => 'a12f',
            'created_at' => '2021-07-27 13:43:56',
            'updated_at' => '2021-07-27 13:43:57',
            'deleted_at' => null,
            'model' => 'DummyModel'
        ];
        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('fetch')
            ->with('SELECT * FROM `node` WHERE `id` = :id AND `node`.`tenant_id` = :tenant_id AND `deleted_at` IS NULL ', [':id' => 'a1', ':tenant_id' => 'a12f'])
            ->willReturn($node);
        $mock->expects($this->exactly(3))
            ->method('fetchAll')
            ->withConsecutive(
                ['SELECT * FROM `node_property` WHERE `node_id` = :node_id AND `deleted_at` IS NULL', [':node_id' => 'a1']],
                ['SELECT `edge`.`child_id`, `edge`.`parent_id` FROM `edge` LEFT JOIN `node` ON `node`.`id` = `edge`.`parent_id` WHERE `edge`.`child_id` IN (:p0) AND `edge`.`parent` = :p1 AND `edge`.`tenant_id` = :p3 AND `node`.`tenant_id` = :p2 AND `edge`.`deleted_at` IS NULL AND `node`.`deleted_at` IS NULL  LIMIT 0, 10', [':p0' => 'a1',':p1' => 'DummyModel',':p2' => 'a12f',':p3' => 'a12f']],
                ['SELECT `edge`.`child_id`, `edge`.`parent_id` FROM `edge` LEFT JOIN `node` ON `node`.`id` = `edge`.`parent_id` WHERE `edge`.`child_id` IN (:p0) AND `edge`.`parent` = :p1 AND `edge`.`tenant_id` = :p3 AND `node`.`tenant_id` = :p2 AND `edge`.`deleted_at` IS NULL AND `node`.`deleted_at` IS NULL  LIMIT 0, 10', [':p0' => 'a1',':p1' => 'DummyModel',':p2' => 'a12f',':p3' => 'a12f']]
            )
            ->willReturn([]);
        Mysql::setConnector($mock);

        $provider = new MysqlDataProvider();
        $dummy = $provider->load('a12f', 'DummyModel', 'a1');
        $closure = $dummy->belongs_to_many;
        $result = $closure([]);
        self::assertSame([], $result['edges']);

        $closure2 = $dummy->belongs_to;
        $result2 = $closure2([]);
        self::assertNull($result2);
    }

    public function testLoadRelationClosuresError(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $node = (object)[
            'id' => 'a1',
            'tenant_id' => 'a12f',
            'created_at' => '2021-07-27 13:43:56',
            'updated_at' => '2021-07-27 13:43:57',
            'deleted_at' => null,
            'model' => 'DummyModel'
        ];
        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('fetch')
            ->with('SELECT * FROM `node` WHERE `id` = :id AND `node`.`tenant_id` = :tenant_id AND `deleted_at` IS NULL ', [':id' => 'a1', ':tenant_id' => 'a12f'])
            ->willReturn($node);
        $mock->expects($this->once())
            ->method('fetchAll')
            ->withConsecutive(
                ['SELECT * FROM `node_property` WHERE `node_id` = :node_id AND `deleted_at` IS NULL', [':node_id' => 'a1']],
            )
            ->willReturn([]);
        Mysql::setConnector($mock);

        $provider = new MysqlDataProvider();
        $dummy = $provider->load('a12f', 'DummyModel', 'a1');
        $closure = $dummy->belongs_to_many;

        $this->expectException(Error::class);
        $closure(['page' => 0]);
    }

    public function testLoadRelationClosuresDeleted(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $node = (object)[
            'id' => 'a1',
            'tenant_id' => 'a12f',
            'created_at' => '2021-07-27 13:43:56',
            'updated_at' => '2021-07-27 13:43:57',
            'deleted_at' => null,
            'model' => 'DummyModel'
        ];
        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('fetch')
            ->with('SELECT * FROM `node` WHERE `id` = :id AND `node`.`tenant_id` = :tenant_id AND `deleted_at` IS NULL ', [':id' => 'a1', ':tenant_id' => 'a12f'])
            ->willReturn($node);
        $mock->expects($this->exactly(2))
            ->method('fetchAll')
            ->withConsecutive(
                ['SELECT * FROM `node_property` WHERE `node_id` = :node_id AND `deleted_at` IS NULL', [':node_id' => 'a1']],
                ['SELECT `edge`.`child_id`, `edge`.`parent_id` FROM `edge` LEFT JOIN `node` ON `node`.`id` = `edge`.`parent_id` WHERE `edge`.`child_id` IN (:p0) AND `edge`.`parent` = :p1 AND `edge`.`tenant_id` = :p3 AND `node`.`tenant_id` = :p2 AND `node`.`deleted_at` IS NOT NULL AND `edge`.`deleted_at` IS NOT NULL  LIMIT 0, 10', [':p0' => 'a1',':p1' => 'DummyModel',':p2' => 'a12f',':p3' => 'a12f']]
            )
            ->willReturn([]);
        Mysql::setConnector($mock);

        $provider = new MysqlDataProvider();
        $dummy = $provider->load('a12f', 'DummyModel', 'a1');
        $closure = $dummy->belongs_to_many;
        $result = $closure(['result' => 'ONLY_SOFT_DELETED']);
        self::assertSame([], $result['edges']);
    }

    public function testLoadRelationClosuresWithTrashed(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $node = (object)[
            'id' => 'a1',
            'tenant_id' => 'a12f',
            'created_at' => '2021-07-27 13:43:56',
            'updated_at' => '2021-07-27 13:43:57',
            'deleted_at' => null,
            'model' => 'DummyModel'
        ];
        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('fetch')
            ->with('SELECT * FROM `node` WHERE `id` = :id AND `node`.`tenant_id` = :tenant_id AND `deleted_at` IS NULL ', [':id' => 'a1', ':tenant_id' => 'a12f'])
            ->willReturn($node);
        $mock->expects($this->exactly(2))
            ->method('fetchAll')
            ->withConsecutive(
                ['SELECT * FROM `node_property` WHERE `node_id` = :node_id AND `deleted_at` IS NULL', [':node_id' => 'a1']],
                ['SELECT `edge`.`child_id`, `edge`.`parent_id` FROM `edge` LEFT JOIN `node` ON `node`.`id` = `edge`.`parent_id` WHERE `edge`.`child_id` IN (:p0) AND `edge`.`parent` = :p1 AND `edge`.`tenant_id` = :p3 AND `node`.`tenant_id` = :p2  LIMIT 0, 10', [':p0' => 'a1',':p1' => 'DummyModel',':p2' => 'a12f',':p3' => 'a12f']]
            )
            ->willReturn([]);
        Mysql::setConnector($mock);

        $provider = new MysqlDataProvider();
        $dummy = $provider->load('a12f', 'DummyModel', 'a1');
        $closure = $dummy->belongs_to_many;
        $result = $closure(['result' => 'WITH_TRASHED']);
        self::assertSame([], $result['edges']);
    }

    public function testLoadRelationClosuresEdgeProperties(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $node = (object)[
            'id' => 'a1',
            'tenant_id' => 'a12f',
            'created_at' => '2021-07-27 13:43:56',
            'updated_at' => '2021-07-27 13:43:57',
            'deleted_at' => null,
            'model' => 'DummyModel'
        ];
        $edge = (object)[
            'parent_id' => 'p1',
            'child_id' => 'c1',
            'created_at' => '2021-07-27 13:43:56',
            'updated_at' => '2021-07-27 13:43:57',
            'deleted_at' => null,
            'parent' => 'DummyModel',
            'child' => 'DummyModel'
        ];
        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->exactly(3))
            ->method('fetch')
            ->withConsecutive(
                ['SELECT * FROM `node` WHERE `id` = :id AND `node`.`tenant_id` = :tenant_id AND `deleted_at` IS NULL ', [':id' => 'a1', ':tenant_id' => 'a12f']],
                ['SELECT * FROM `edge` WHERE `parent_id` = :parent_id AND `child_id` = :child_id AND `tenant_id` = :tenant_id AND `deleted_at` IS NULL ', [':parent_id' => 'p1', ':child_id' => 'c1', ':tenant_id' => 'a12f']],
                ['SELECT * FROM `node` WHERE `id` = :id AND `node`.`tenant_id` = :tenant_id AND `deleted_at` IS NULL ', [':id' => 'p1', ':tenant_id' => 'a12f']]
            )
            ->willReturnOnConsecutiveCalls(
                $node,
                $edge
            );
        $mock->expects($this->exactly(3))
            ->method('fetchAll')
            ->withConsecutive(
                ['SELECT * FROM `node_property` WHERE `node_id` = :node_id AND `deleted_at` IS NULL', [':node_id' => 'a1']],
                ['SELECT `edge`.`child_id`, `edge`.`parent_id` FROM `edge` LEFT JOIN `node` ON `node`.`id` = `edge`.`parent_id` WHERE `edge`.`child_id` IN (:p0) AND `edge`.`parent` = :p1 AND `edge`.`tenant_id` = :p3 AND `node`.`tenant_id` = :p2 AND `edge`.`deleted_at` IS NULL AND `node`.`deleted_at` IS NULL  LIMIT 0, 10', [':p0' => 'a1',':p1' => 'DummyModel',':p2' => 'a12f',':p3' => 'a12f']],
                ['SELECT * FROM `edge_property` WHERE `parent_id` = :parent_id AND `child_id` = :child_id AND `deleted_at` IS NULL', [':parent_id' => 'p1', ':child_id' => 'c1']]
            )
            ->willReturnOnConsecutiveCalls(
                [],
                [(object)['parent_id' => 'p1', 'child_id' => 'c1']],
                [(object)['property' => 'pivot_property', 'value_int' => null, 'value_string' => 'lala', 'value_float' => null],(object)['property' => 'does_not_exist', 'value_int' => null, 'value_string' => 'lala', 'value_float' => null]],
            );
        Mysql::setConnector($mock);

        $provider = new MysqlDataProvider();
        $dummy = $provider->load('a12f', 'DummyModel', 'a1');
        $closure = $dummy->belongs_to_many;
        $result = $closure([]);
        self::assertSame([$edge], $result['edges']);
    }

    public function testLoadRelationClosuresEdgeNull(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $node = (object)[
            'id' => 'a1',
            'tenant_id' => 'a12f',
            'created_at' => '2021-07-27 13:43:56',
            'updated_at' => '2021-07-27 13:43:57',
            'deleted_at' => null,
            'model' => 'DummyModel'
        ];
        $edge = null;
        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->exactly(2))
            ->method('fetch')
            ->withConsecutive(
                ['SELECT * FROM `node` WHERE `id` = :id AND `node`.`tenant_id` = :tenant_id AND `deleted_at` IS NULL ', [':id' => 'a1', ':tenant_id' => 'a12f']],
                ['SELECT * FROM `edge` WHERE `parent_id` = :parent_id AND `child_id` = :child_id AND `tenant_id` = :tenant_id AND `deleted_at` IS NULL ', [':parent_id' => 'p1', ':child_id' => 'c1', ':tenant_id' => 'a12f']]
            )
            ->willReturnOnConsecutiveCalls(
                $node,
                $edge
            );
        $mock->expects($this->exactly(2))
            ->method('fetchAll')
            ->withConsecutive(
                ['SELECT * FROM `node_property` WHERE `node_id` = :node_id AND `deleted_at` IS NULL', [':node_id' => 'a1']],
                ['SELECT `edge`.`child_id`, `edge`.`parent_id` FROM `edge` LEFT JOIN `node` ON `node`.`id` = `edge`.`parent_id` WHERE `edge`.`child_id` IN (:p0) AND `edge`.`parent` = :p1 AND `edge`.`tenant_id` = :p3 AND `node`.`tenant_id` = :p2 AND `edge`.`deleted_at` IS NULL AND `node`.`deleted_at` IS NULL  LIMIT 0, 10', [':p0' => 'a1',':p1' => 'DummyModel',':p2' => 'a12f',':p3' => 'a12f']]
            )
            ->willReturnOnConsecutiveCalls(
                [],
                [(object)['parent_id' => 'p1', 'child_id' => 'c1']]
            );
        Mysql::setConnector($mock);

        $provider = new MysqlDataProvider();
        $dummy = $provider->load('a12f', 'DummyModel', 'a1');
        $closure = $dummy->belongs_to_many;

        $this->expectException(RuntimeException::class);
        $closure([]);
    }

    public function testLoadRelationClosuresEdgePropertiesDeleted(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $node = (object)[
            'id' => 'a1',
            'tenant_id' => 'a12f',
            'created_at' => '2021-07-27 13:43:56',
            'updated_at' => '2021-07-27 13:43:57',
            'deleted_at' => null,
            'model' => 'DummyModel'
        ];
        $edge = (object)[
            'parent_id' => 'p1',
            'child_id' => 'c1',
            'created_at' => '2021-07-27 13:43:56',
            'updated_at' => '2021-07-27 13:43:57',
            'deleted_at' => null,
            'parent' => 'DummyModel',
            'child' => 'DummyModel'
        ];
        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->exactly(3))
            ->method('fetch')
            ->withConsecutive(
                ['SELECT * FROM `node` WHERE `id` = :id AND `node`.`tenant_id` = :tenant_id AND `deleted_at` IS NULL ', [':id' => 'a1', ':tenant_id' => 'a12f']],
                ['SELECT * FROM `edge` WHERE `parent_id` = :parent_id AND `child_id` = :child_id AND `tenant_id` = :tenant_id AND `deleted_at` IS NOT NULL ', [':parent_id' => 'p1', ':child_id' => 'c1', ':tenant_id' => 'a12f']],
                ['SELECT * FROM `node` WHERE `id` = :id AND `node`.`tenant_id` = :tenant_id AND `deleted_at` IS NULL ', [':id' => 'p1', ':tenant_id' => 'a12f']]
            )
            ->willReturnOnConsecutiveCalls(
                $node,
                $edge
            );
        $mock->expects($this->exactly(3))
            ->method('fetchAll')
            ->withConsecutive(
                ['SELECT * FROM `node_property` WHERE `node_id` = :node_id AND `deleted_at` IS NULL', [':node_id' => 'a1']],
                ['SELECT `edge`.`child_id`, `edge`.`parent_id` FROM `edge` LEFT JOIN `node` ON `node`.`id` = `edge`.`parent_id` WHERE `edge`.`child_id` IN (:p0) AND `edge`.`parent` = :p1 AND `edge`.`tenant_id` = :p3 AND `node`.`tenant_id` = :p2 AND `node`.`deleted_at` IS NOT NULL AND `edge`.`deleted_at` IS NOT NULL  LIMIT 0, 10', [':p0' => 'a1',':p1' => 'DummyModel',':p2' => 'a12f',':p3' => 'a12f']],
                ['SELECT * FROM `edge_property` WHERE `parent_id` = :parent_id AND `child_id` = :child_id AND `deleted_at` IS NULL', [':parent_id' => 'p1', ':child_id' => 'c1']]
            )
            ->willReturnOnConsecutiveCalls(
                [],
                [(object)['parent_id' => 'p1', 'child_id' => 'c1']],
                [(object)['property' => 'pivot_property', 'value_int' => null, 'value_string' => 'lala', 'value_float' => null]],
            );
        Mysql::setConnector($mock);

        $provider = new MysqlDataProvider();
        $dummy = $provider->load('a12f', 'DummyModel', 'a1');
        $closure = $dummy->belongs_to_many;
        $result = $closure(['result' => 'ONLY_SOFT_DELETED']);
        self::assertSame([$edge], $result['edges']);
    }

    public function testLoadRelationClosuresEdgePropertiesWithTrashed(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $node = (object)[
            'id' => 'a1',
            'tenant_id' => 'a12f',
            'created_at' => '2021-07-27 13:43:56',
            'updated_at' => '2021-07-27 13:43:57',
            'deleted_at' => null,
            'model' => 'DummyModel'
        ];
        $edge = (object)[
            'parent_id' => 'p1',
            'child_id' => 'c1',
            'created_at' => '2021-07-27 13:43:56',
            'updated_at' => '2021-07-27 13:43:57',
            'deleted_at' => null,
            'parent' => 'DummyModel',
            'child' => 'DummyModel'
        ];
        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->exactly(3))
            ->method('fetch')
            ->withConsecutive(
                ['SELECT * FROM `node` WHERE `id` = :id AND `node`.`tenant_id` = :tenant_id AND `deleted_at` IS NULL ', [':id' => 'a1', ':tenant_id' => 'a12f']],
                ['SELECT * FROM `edge` WHERE `parent_id` = :parent_id AND `child_id` = :child_id AND `tenant_id` = :tenant_id ', [':parent_id' => 'p1', ':child_id' => 'c1', ':tenant_id' => 'a12f']],
                ['SELECT * FROM `node` WHERE `id` = :id AND `node`.`tenant_id` = :tenant_id AND `deleted_at` IS NULL ', [':id' => 'p1', ':tenant_id' => 'a12f']]
            )
            ->willReturnOnConsecutiveCalls(
                $node,
                $edge
            );
        $mock->expects($this->exactly(3))
            ->method('fetchAll')
            ->withConsecutive(
                ['SELECT * FROM `node_property` WHERE `node_id` = :node_id AND `deleted_at` IS NULL', [':node_id' => 'a1']],
                ['SELECT `edge`.`child_id`, `edge`.`parent_id` FROM `edge` LEFT JOIN `node` ON `node`.`id` = `edge`.`parent_id` WHERE `edge`.`child_id` IN (:p0) AND `edge`.`parent` = :p1 AND `edge`.`tenant_id` = :p3 AND `node`.`tenant_id` = :p2  LIMIT 0, 10', [':p0' => 'a1',':p1' => 'DummyModel',':p2' => 'a12f',':p3' => 'a12f']],
                ['SELECT * FROM `edge_property` WHERE `parent_id` = :parent_id AND `child_id` = :child_id AND `deleted_at` IS NULL', [':parent_id' => 'p1', ':child_id' => 'c1']]
            )
            ->willReturnOnConsecutiveCalls(
                [],
                [(object)['parent_id' => 'p1', 'child_id' => 'c1']],
                [(object)['property' => 'pivot_property', 'value_int' => null, 'value_string' => 'lala', 'value_float' => null]],
            );
        Mysql::setConnector($mock);

        $provider = new MysqlDataProvider();
        $dummy = $provider->load('a12f', 'DummyModel', 'a1');
        $closure = $dummy->belongs_to_many;
        $result = $closure(['result' => 'WITH_TRASHED']);
        self::assertSame([$edge], $result['edges']);
    }

    public function testLoadRelationClosuresEdgeProperties2(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $node = (object)[
            'id' => 'a1',
            'tenant_id' => 'a12f',
            'created_at' => '2021-07-27 13:43:56',
            'updated_at' => '2021-07-27 13:43:57',
            'deleted_at' => null,
            'model' => 'DummyModel'
        ];
        $edge = (object)[
            'parent_id' => 'p1',
            'child_id' => 'c1',
            'created_at' => '2021-07-27 13:43:56',
            'updated_at' => '2021-07-27 13:43:57',
            'deleted_at' => null,
            'parent' => 'DummyModel',
            'child' => 'DummyModel'
        ];
        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->exactly(3))
            ->method('fetch')
            ->withConsecutive(
                ['SELECT * FROM `node` WHERE `id` = :id AND `node`.`tenant_id` = :tenant_id AND `deleted_at` IS NULL ', [':id' => 'a1', ':tenant_id' => 'a12f']],
                ['SELECT * FROM `edge` WHERE `parent_id` = :parent_id AND `child_id` = :child_id AND `tenant_id` = :tenant_id AND `deleted_at` IS NULL ', [':parent_id' => 'p1', ':child_id' => 'c1', ':tenant_id' => 'a12f']],
                ['SELECT * FROM `node` WHERE `id` = :id AND `node`.`tenant_id` = :tenant_id AND `deleted_at` IS NULL ', [':id' => 'c1', ':tenant_id' => 'a12f']]
            )
            ->willReturnOnConsecutiveCalls(
                $node,
                $edge
            );
        $mock->expects($this->exactly(3))
            ->method('fetchAll')
            ->withConsecutive(
                ['SELECT * FROM `node_property` WHERE `node_id` = :node_id AND `deleted_at` IS NULL', [':node_id' => 'a1']],
                ['SELECT `edge`.`child_id`, `edge`.`parent_id` FROM `edge` LEFT JOIN `node` ON `node`.`id` = `edge`.`child_id` WHERE `edge`.`child` = :p1 AND `edge`.`parent_id` IN (:p0) AND `edge`.`tenant_id` = :p3 AND `node`.`tenant_id` = :p2 AND `edge`.`deleted_at` IS NULL AND `node`.`deleted_at` IS NULL  LIMIT 0, 10', [':p0' => 'a1',':p1' => 'DummyModel',':p2' => 'a12f',':p3' => 'a12f']],
                ['SELECT * FROM `edge_property` WHERE `parent_id` = :parent_id AND `child_id` = :child_id AND `deleted_at` IS NULL', [':parent_id' => 'p1', ':child_id' => 'c1']]
            )
            ->willReturnOnConsecutiveCalls(
                [],
                [(object)['parent_id' => 'p1', 'child_id' => 'c1']],
                [],
            );
        Mysql::setConnector($mock);

        $provider = new MysqlDataProvider();
        $dummy = $provider->load('a12f', 'DummyModel', 'a1');
        $closure = $dummy->has_one;
        $result = $closure([]);
        self::assertSame($edge, $result);
    }

    public function testInsert(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $data = [
            'enum' => 'A'

        ];
        $node = (object)[
            'id' => 'a1',
            'tenant_id' => 'a12f',
            'created_at' => '2021-07-27 13:43:56',
            'updated_at' => '2021-07-27 13:43:57',
            'deleted_at' => null,
            'model' => 'DummyModel'
        ];

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('fetch')
            ->withAnyParameters()
            ->willReturn($node);
        $mock->expects($this->once())
            ->method('fetchAll')
            ->withAnyParameters()
            ->willReturn([
                (object)['property' => 'last_name', 'value_int' => null, 'value_string' => 'Huber', 'value_float' => null],
                (object)['property' => 'float', 'value_int' => null, 'value_string' => null, 'value_float' => 1.234],
                (object)['property' => 'decimal', 'value_int' => 123, 'value_string' => null, 'value_float' => null],
                (object)['property' => 'bool', 'value_int' => 1, 'value_string' => null, 'value_float' => null],
            ]);
        $mock->expects($this->exactly(2))
            ->method('execute')
            ->withAnyParameters()
            ->willReturn(1);

        Mysql::setConnector($mock);
        $provider = new MysqlDataProvider();
        $result = $provider->insert('a12f', 'DummyModel', $data);

        self::assertSame('a1', $result->id);
        self::assertNull($result->deleted_at);
        self::assertSame('DummyModel', $result->model);
    }

    public function testInsertError(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $data = [];
        $node = (object)[
            'id' => 'a1',
            'tenant_id' => 'a12f',
            'created_at' => '2021-07-27 13:43:56',
            'updated_at' => '2021-07-27 13:43:57',
            'deleted_at' => null,
            'model' => 'DummyModel'
        ];

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('execute')
            ->withAnyParameters()
            ->willReturn(1);

        Mysql::setConnector($mock);
        $provider = new MysqlDataProvider();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('may not be null');
        $provider->insert('a12f', 'DummyModel', $data);
    }

    public function xtestInsertRelation(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $data = [
            'enum' => 'A',
            'belongs_to' => [
                'id' => 'p1',
                'pivot_property' => null,
                'pivot_property2' => null,
                'pivot_property3' => 'asdf'
            ],
            'belongs_to_many' => [[
                'where' => [
                    'column' => 'id',
                    'operator' => '=',
                    'value' => 'p2'
                ],
            ]]
        ];
        $node = (object)[
            'id' => 'a1',
            'tenant_id' => 'a12f',
            'created_at' => '2021-07-27 13:43:56',
            'updated_at' => '2021-07-27 13:43:57',
            'deleted_at' => null,
            'model' => 'DummyModel'
        ];

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('fetch')
            ->withAnyParameters()
            ->willReturn($node);
        $mock->expects($this->once())
            ->method('fetchAll')
            ->withAnyParameters()
            ->willReturn([
                (object)['property' => 'last_name', 'value_int' => null, 'value_string' => 'Huber', 'value_float' => null],
                (object)['property' => 'float', 'value_int' => null, 'value_string' => null, 'value_float' => 1.234],
                (object)['property' => 'decimal', 'value_int' => 123, 'value_string' => null, 'value_float' => null],
                (object)['property' => 'bool', 'value_int' => 1, 'value_string' => null, 'value_float' => null],
            ]);
        $mock->expects($this->exactly(6))
            ->method('execute')
            ->withAnyParameters()
            ->willReturn(1);

        Mysql::setConnector($mock);
        $provider = new MysqlDataProvider();
        $result = $provider->insert('a12f', 'DummyModel', $data);

        self::assertSame('a1', $result->id);
        self::assertNull($result->deleted_at);
        self::assertSame('DummyModel', $result->model);
    }

}