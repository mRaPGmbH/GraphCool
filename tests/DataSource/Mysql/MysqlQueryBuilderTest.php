<?php

namespace Mrap\GraphCool\Tests\DataSource\Mysql;

use App\Models\DummyModel;
use GraphQL\Error\Error;
use Mrap\GraphCool\DataSource\Mysql\MysqlQueryBuilder;
use Mrap\GraphCool\Definition\Field;
use Mrap\GraphCool\Definition\Relation;
use Mrap\GraphCool\Tests\TestCase;
use RuntimeException;

class MysqlQueryBuilderTest extends TestCase
{
    public function testConstructForModel(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $model = new DummyModel();
        $builder = MysqlQueryBuilder::forModel($model, 'DummyModel');
        self::assertInstanceOf(MysqlQueryBuilder::class, $builder);
        $query = trim($builder->toSql());
        self::assertSame('SELECT * FROM `node`  WHERE `node`.`model` = :p0 AND `node`.`deleted_at` IS NULL', $query);
        $params = $builder->getParameters();
        self::assertSame([':p0' => 'DummyModel'], $params);
    }

    public function testConstructForRelation(): void
    {
        $relation = Relation::belongsToMany(DummyModel::class);
        $builder = MysqlQueryBuilder::forRelation($relation, ['a']);
        self::assertInstanceOf(MysqlQueryBuilder::class, $builder);
        $query = trim($builder->toSql());
        self::assertSame('SELECT * FROM `edge` LEFT JOIN `node` ON `node`.`id` = `edge`.`parent_id` WHERE `edge`.`child_id` IN (:p0) AND `edge`.`parent` = :p1 AND `edge`.`deleted_at` IS NULL AND `node`.`deleted_at` IS NULL', $query);
        $params = $builder->getParameters();
        self::assertSame([':p0' => 'a', ':p1' => 'DummyModel'], $params);
    }

    public function testConstructForRelation2(): void
    {
        $relation = Relation::hasMany(DummyModel::class);
        $builder = MysqlQueryBuilder::forRelation($relation, ['a']);
        self::assertInstanceOf(MysqlQueryBuilder::class, $builder);
        $query = trim($builder->toSql());
        self::assertSame('SELECT * FROM `edge` LEFT JOIN `node` ON `node`.`id` = `edge`.`child_id` WHERE `edge`.`child` = :p1 AND `edge`.`parent_id` IN (:p0) AND `edge`.`deleted_at` IS NULL AND `node`.`deleted_at` IS NULL', $query);
        $params = $builder->getParameters();
        self::assertSame([':p0' => 'a', ':p1' => 'DummyModel'], $params);
    }

    public function testConstructForRelationError(): void
    {
        $this->expectException(RuntimeException::class);
        $relation = Relation::hasMany(DummyModel::class);
        $relation->type = 'invalid-realation-type';
        MysqlQueryBuilder::forRelation($relation, ['a']);
    }

    public function testTenant(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $model = new DummyModel();
        $builder = MysqlQueryBuilder::forModel($model, 'DummyModel');
        $builder->tenant('a');
        $query = trim($builder->toSql());
        self::assertSame('SELECT * FROM `node`  WHERE `node`.`model` = :p0 AND `node`.`tenant_id` = :p1 AND `node`.`deleted_at` IS NULL', $query);
        $params = $builder->getParameters();
        self::assertSame([':p0' => 'DummyModel',':p1' => 'a'], $params);
    }

    public function testTenantRelation(): void
    {
        $relation = Relation::hasMany(DummyModel::class);
        $builder = MysqlQueryBuilder::forRelation($relation, ['a']);
        $builder->tenant('b');
        $query = trim($builder->toSql());
        self::assertSame('SELECT * FROM `edge` LEFT JOIN `node` ON `node`.`id` = `edge`.`child_id` WHERE `edge`.`child` = :p1 AND `edge`.`parent_id` IN (:p0) AND `edge`.`tenant_id` = :p3 AND `node`.`tenant_id` = :p2 AND `edge`.`deleted_at` IS NULL AND `node`.`deleted_at` IS NULL', $query);
        $params = $builder->getParameters();
        self::assertSame([':p0' => 'a', ':p1' => 'DummyModel',':p2' => 'b', ':p3' => 'b'], $params);
    }

    public function testSelect(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $model = new DummyModel();
        $builder = MysqlQueryBuilder::forModel($model, 'DummyModel');
        $builder->select(['created_at', 'last_name']);
        $query = trim($builder->toSql());
        self::assertSame('SELECT `node_last_name`.`value_float`), `node_last_name`.`value_int`), `node_last_name`.`value_string`), `node`.`created_at` FROM `node` LEFT JOIN `node_property` AS `node_last_name` ON (`node_last_name`.`node_id` = `node`.`id` AND `node_last_name`.`property` = :p1 AND `node_last_name`.`deleted_at` IS NULL) WHERE `node`.`model` = :p0 AND `node`.`deleted_at` IS NULL', $query);
        $params = $builder->getParameters();
        self::assertSame([':p0' => 'DummyModel',':p1' => 'last_name'], $params);
    }

    public function testSelectAll(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $model = new DummyModel();
        $builder = MysqlQueryBuilder::forModel($model, 'DummyModel');
        $builder->select(['*']);
        $query = trim($builder->toSql());
        self::assertSame('SELECT `node`.* FROM `node`  WHERE `node`.`model` = :p0 AND `node`.`deleted_at` IS NULL', $query);
        $params = $builder->getParameters();
        self::assertSame([':p0' => 'DummyModel'], $params);
    }

    public function testSelectError(): void
    {
        $this->expectException(RuntimeException::class);
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $model = new DummyModel();
        $builder = MysqlQueryBuilder::forModel($model, 'DummyModel');
        $builder->update(['last_name' => 'Huber']);
        $builder->select(['last_name']);
    }

    public function testDelete(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not implemented yet');
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $model = new DummyModel();
        $builder = MysqlQueryBuilder::forModel($model, 'DummyModel');
        $builder->delete();
        $query = trim($builder->toSql());
        /*
        self::assertSame('SELECT `node_last_name`.`value_float`), `node_last_name`.`value_int`), `node_last_name`.`value_string`), `node`.`created_at` FROM `node` LEFT JOIN `node_property` AS `node_last_name` ON (`node_last_name`.`node_id` = `node`.`id` AND `node_last_name`.`property` = :p1 AND `node_last_name`.`deleted_at` IS NULL) WHERE `node`.`model` = :p0 AND `node`.`deleted_at` IS NULL', $query);
        $params = $builder->getParameters();
        self::assertSame([':p0' => 'DummyModel',':p1' => 'last_name'], $params);*/
    }

    public function testDeleteError(): void
    {
        $this->expectException(RuntimeException::class);
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $model = new DummyModel();
        $builder = MysqlQueryBuilder::forModel($model, 'DummyModel');
        $builder->update(['last_name' => 'Huber']);
        $builder->delete();
    }

    public function testUpdate(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $model = new DummyModel();
        $builder = MysqlQueryBuilder::forModel($model, 'DummyModel');
        $builder->update(['deleted_at' => '2021-07-22 12:51:12', 'last_name' => 'Huber', 'time' => 123, 'float' => 12.345,'_ignored' => 'asfd']);
        $query = trim($builder->toSql());
        self::assertSame('UPDATE `node` LEFT JOIN `node_property` AS `node_last_name` ON (`node_last_name`.`node_id` = `node`.`id` AND `node_last_name`.`property` = :p1 AND `node_last_name`.`deleted_at` IS NULL) LEFT JOIN `node_property` AS `node_time` ON (`node_time`.`node_id` = `node`.`id` AND `node_time`.`property` = :p2 AND `node_time`.`deleted_at` IS NULL) LEFT JOIN `node_property` AS `node_float` ON (`node_float`.`node_id` = `node`.`id` AND `node_float`.`property` = :p3 AND `node_float`.`deleted_at` IS NULL) SET `node`.`updated_at` = now(), `node`.`deleted_at` = :u0, `node_last_name`.`value_string` = :u1, `node_time`.`value_int` = :u2, `node_float`.`value_float` = :u3 WHERE `node`.`model` = :p0', $query);
        $params = $builder->getUpdateParameters();
        $expected = [
            ':p0' => 'DummyModel',
            ':p1' => 'last_name',
            ':p2' => 'time',
            ':p3' => 'float',
            ':u0' => '2021-07-22 12:51:12',
            ':u1' => 'Huber',
            ':u2' => 123,
            ':u3' => 12.345,
        ];
        self::assertSame($expected, $params);
    }

    public function testUpdateError(): void
    {
        $this->expectException(RuntimeException::class);
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $model = new DummyModel();
        $builder = MysqlQueryBuilder::forModel($model, 'DummyModel');
        $builder->select(['last_name']);
        $builder->update(['last_name' => 'Huber']);
    }

    public function testSelectMax(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $model = new DummyModel();
        $builder = MysqlQueryBuilder::forModel($model, 'DummyModel');
        $builder->selectMax('created_at','A')->selectMax('last_name', 'B', 'value_string');
        $query = trim($builder->toSql());
        self::assertSame('SELECT max(`node_last_name`.`value_string`) AS `B`, max(`node`.`created_at`) AS `A` FROM `node` LEFT JOIN `node_property` AS `node_last_name` ON (`node_last_name`.`node_id` = `node`.`id` AND `node_last_name`.`property` = :p1 AND `node_last_name`.`deleted_at` IS NULL) WHERE `node`.`model` = :p0 AND `node`.`deleted_at` IS NULL', $query);
        $params = $builder->getParameters();
        self::assertSame([':p0' => 'DummyModel',':p1' => 'last_name'], $params);
    }

    public function testSelectMaxError(): void
    {
        $this->expectException(RuntimeException::class);
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $model = new DummyModel();
        $builder = MysqlQueryBuilder::forModel($model, 'DummyModel');
        $builder->update(['last_name' => 'Huber']);
        $builder->selectMax('last_name', 'A');
    }

    public function testOrderBy(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $model = new DummyModel();
        $builder = MysqlQueryBuilder::forModel($model, 'DummyModel');
        $builder->orderBy([['field' => 'created_at', 'order' => 'ASC'],['field' => 'last_name', 'order' => 'DESC'],['order' => 'RAND']]);
        $query = trim($builder->toSql());
        self::assertSame('SELECT * FROM `node` LEFT JOIN `node_property` AS `node_last_name` ON (`node_last_name`.`node_id` = `node`.`id` AND `node_last_name`.`property` = :p1 AND `node_last_name`.`deleted_at` IS NULL) WHERE `node`.`model` = :p0 AND `node`.`deleted_at` IS NULL ORDER BY `node`.`created_at` ASC, `node_last_name`.`value_int` DESC, `node_last_name`.`value_float` DESC, `node_last_name`.`value_string` DESC, rand()', $query);
        $params = $builder->getParameters();
        self::assertSame([':p0' => 'DummyModel',':p1' => 'last_name'], $params);
    }

    public function testLimit(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $model = new DummyModel();
        $builder = MysqlQueryBuilder::forModel($model, 'DummyModel');
        $builder->limit(1,2);
        $query = trim($builder->toSql());
        self::assertSame('SELECT * FROM `node`  WHERE `node`.`model` = :p0 AND `node`.`deleted_at` IS NULL  LIMIT 2, 1', $query);
        $params = $builder->getParameters();
        self::assertSame([':p0' => 'DummyModel'], $params);
    }

    public function testWithTrashed(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $model = new DummyModel();
        $builder = MysqlQueryBuilder::forModel($model, 'DummyModel');
        $builder->withTrashed();
        $query = trim($builder->toSql());
        self::assertSame('SELECT * FROM `node`  WHERE `node`.`model` = :p0', $query);
        $params = $builder->getParameters();
        self::assertSame([':p0' => 'DummyModel'], $params);
    }

    public function testOnlySoftDeleted(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $model = new DummyModel();
        $builder = MysqlQueryBuilder::forModel($model, 'DummyModel');
        $builder->onlySoftDeleted();
        $query = trim($builder->toSql());
        self::assertSame('SELECT * FROM `node`  WHERE `node`.`model` = :p0 AND `node`.`deleted_at` IS NOT NULL', $query);
        $params = $builder->getParameters();
        self::assertSame([':p0' => 'DummyModel'], $params);
    }

    public function testOnlySoftDeletedRelation(): void
    {
        $relation = Relation::belongsToMany(DummyModel::class);
        $builder = MysqlQueryBuilder::forRelation($relation, ['a']);
        $builder->onlySoftDeleted();
        $query = trim($builder->toSql());
        self::assertSame('SELECT * FROM `edge` LEFT JOIN `node` ON `node`.`id` = `edge`.`parent_id` WHERE `edge`.`child_id` IN (:p0) AND `edge`.`parent` = :p1 AND `node`.`deleted_at` IS NOT NULL AND `edge`.`deleted_at` IS NOT NULL', $query);
        $params = $builder->getParameters();
        self::assertSame([':p0' => 'a',':p1' => 'DummyModel'], $params);
    }
    
    public function testEmptyWhere(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $model = new DummyModel();
        $builder = MysqlQueryBuilder::forModel($model, 'DummyModel');
        $builder->where(null)->where([]);
        $query = trim($builder->toSql());
        self::assertSame('SELECT * FROM `node`  WHERE `node`.`model` = :p0 AND `node`.`deleted_at` IS NULL', $query);
        $params = $builder->getParameters();
        self::assertSame([':p0' => 'DummyModel'], $params);
    }

    public function testWhere(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $model = new DummyModel();
        $builder = MysqlQueryBuilder::forModel($model, 'DummyModel');
        $builder->where(
            ['OR' => [
                ['AND' => [
                    ['column' => 'last_name', 'operator' => 'LIKE', 'value' => 'Huber'],
                    ['column' => 'created_at', 'operator' => 'BETWEEN', 'value' => ['2020-01-01 00:00:00', '2021-01-01 00:00:00']],
                    ['column' => 'id', 'operator' => 'IN', 'value' => ['a','b']]
                ]],
                ['column'=> 'date', 'operator' => 'IS NULL']
            ]],
        );
        $query = trim($builder->toSql());
        self::assertSame('SELECT * FROM `node` LEFT JOIN `node_property` AS `node_last_name` ON (`node_last_name`.`node_id` = `node`.`id` AND `node_last_name`.`property` = :p1 AND `node_last_name`.`deleted_at` IS NULL) LEFT JOIN `node_property` AS `node_date` ON (`node_date`.`node_id` = `node`.`id` AND `node_date`.`property` = :p7 AND `node_date`.`deleted_at` IS NULL) WHERE ((`node_last_name`.`value_string` LIKE :p2 AND `node`.`created_at` BETWEEN :p3 AND :p4 AND `node`.`id` IN (:p5,:p6)) OR `node_date`.`value_int` IS NULL) AND `node`.`model` = :p0 AND `node`.`deleted_at` IS NULL', $query);
        $params = $builder->getParameters();
        $expected = [
            ':p0' => 'DummyModel',
            ':p1' => 'last_name',
            ':p2' => 'Huber',
            ':p3' => '2020-01-01 00:00:00',
            ':p4' => '2021-01-01 00:00:00',
            ':p5' => 'a',
            ':p6' => 'b',
            ':p7' => 'date',
        ];
        self::assertSame($expected, $params);
    }

    public function testWhereNull(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $model = new DummyModel();
        $builder = MysqlQueryBuilder::forModel($model, 'DummyModel');
        $builder->where(
            ['OR' => []],
        );
        $query = trim($builder->toSql());
        self::assertSame('SELECT * FROM `node`  WHERE `node`.`model` = :p0 AND `node`.`deleted_at` IS NULL', $query);
        $params = $builder->getParameters();
        self::assertSame([':p0' => 'DummyModel'], $params);
    }

    public function testWhereError(): void
    {
        $this->expectException(Error::class);
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $model = new DummyModel();
        $builder = MysqlQueryBuilder::forModel($model, 'DummyModel');
        $builder->where(['column' => 'id', 'operator' => 'IN', 'value' => 'a']);
    }

    public function testWhereError2(): void
    {
        $this->expectException(Error::class);
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $model = new DummyModel();
        $builder = MysqlQueryBuilder::forModel($model, 'DummyModel');
        $builder->where(['column' => 'id', 'operator' => 'BETWEEN', 'value' => 'a']);
    }

    public function testWhereError3(): void
    {
        $this->expectException(Error::class);
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $model = new DummyModel();
        $builder = MysqlQueryBuilder::forModel($model, 'DummyModel');
        $builder->where(['column' => 'id', 'operator' => '=']);
    }

    public function testEmptyWhereHas(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $model = new DummyModel();
        $builder = MysqlQueryBuilder::forModel($model, 'DummyModel');
        $builder->whereHas($model, 'DummyModel', Relation::BELONGS_TO_MANY, null)
            ->whereHas($model, 'DummyModel', Relation::BELONGS_TO_MANY, []);
        $query = trim($builder->toSql());
        self::assertSame('SELECT * FROM `node`  WHERE `node`.`model` = :p0 AND `node`.`deleted_at` IS NULL', $query);
        $params = $builder->getParameters();
        self::assertSame([':p0' => 'DummyModel'], $params);
    }

    public function testWhereHasBelongs(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $model = new DummyModel();
        $builder = MysqlQueryBuilder::forModel($model, 'DummyModel');
        $builder->whereHas($model, 'DummyModel', Relation::BELONGS_TO_MANY,
            ['OR' => []]
        );
        $query = trim($builder->toSql());
        self::assertSame('SELECT * FROM `node`  LEFT JOIN `edge` AS `DummyModelEdge` ON (`DummyModelEdge`.`child_id` = `node`.`id` AND `DummyModelEdge`.`parent` = :p1) WHERE `DummyModelEdge`.`parent` IN (SELECT `node`.`id` FROM `node`  WHERE `node`.`model` = :DummyModel0 AND `node`.`deleted_at` IS NULL ) AND `node`.`model` = :p0 AND `node`.`deleted_at` IS NULL', $query);
        $params = $builder->getParameters();
        $expected = [
            ':p0' => 'DummyModel',
            ':p1' => 'DummyModel',
            ':DummyModel0' => 'DummyModel',
        ];
        self::assertSame($expected, $params);
    }

    public function testWhereHasHas(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $model = new DummyModel();
        $builder = MysqlQueryBuilder::forModel($model, 'DummyModel');
        $builder->whereHas($model, 'DummyModel', Relation::HAS_ONE, ['column' => 'updated_at', 'operator' => 'IS NULL']);
        $query = trim($builder->toSql());
        self::assertSame('SELECT * FROM `node`  LEFT JOIN `edge` AS `DummyModelEdge` ON (`DummyModelEdge`.`parent_id` = `node`.`id` AND `DummyModelEdge`.`child` = :p1) WHERE `DummyModelEdge`.`child_id` IN (SELECT `node`.`id` FROM `node`  WHERE `node`.`model` = :DummyModel0 AND `node`.`updated_at` IS NULL AND `node`.`deleted_at` IS NULL ) AND `node`.`model` = :p0 AND `node`.`deleted_at` IS NULL', $query);
        $params = $builder->getParameters();
        $expected = [
            ':p0' => 'DummyModel',
            ':p1' => 'DummyModel',
            ':DummyModel0' => 'DummyModel',
        ];
        self::assertSame($expected, $params);
    }

    public function testWhereHasError(): void
    {
        $this->expectException(RuntimeException::class);
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $model = new DummyModel();
        $builder = MysqlQueryBuilder::forModel($model, 'DummyModel');
        $builder->whereHas($model, 'DummyModel', 'not-a-valid-relation-type', ['OR' => []]);
    }

    public function testEmptyWhereReleated(): void
    {
        $relation = Relation::belongsToMany(DummyModel::class);
        $builder = MysqlQueryBuilder::forRelation($relation, ['a']);
        $builder->whereRelated(null)->whereRelated([]);
        $query = trim($builder->toSql());
        self::assertSame('SELECT * FROM `edge` LEFT JOIN `node` ON `node`.`id` = `edge`.`parent_id` WHERE `edge`.`child_id` IN (:p0) AND `edge`.`parent` = :p1 AND `edge`.`deleted_at` IS NULL AND `node`.`deleted_at` IS NULL', $query);
        $params = $builder->getParameters();
        self::assertSame([':p0' => 'a', ':p1' => 'DummyModel'], $params);
    }

    public function testWhereReleated(): void
    {
        $relation = Relation::belongsToMany(DummyModel::class);
        $relation->float = Field::float()->nullable();
        $builder = MysqlQueryBuilder::forRelation($relation, ['a']);
        $builder->whereRelated(['column' => '_float', 'operator' => '=', 'value' => 1.123]);
        $query = trim($builder->toSql());
        self::assertSame('SELECT * FROM `edge` LEFT JOIN `node` ON `node`.`id` = `edge`.`parent_id` LEFT JOIN `edge_property` AS `edge_float` ON (`edge_float`.`parent_id` = `edge`.`parent_id` AND `edge_float`.`child_id` = `edge`.`child_id`  AND `edge_float`.`property` = :p2 AND `edge_float`.`deleted_at` IS NULL) WHERE `edge_float`.`value_float` = :p3 AND `edge`.`child_id` IN (:p0) AND `edge`.`parent` = :p1 AND `edge`.`deleted_at` IS NULL AND `node`.`deleted_at` IS NULL', $query);
        $params = $builder->getParameters();
        self::assertSame([':p0' => 'a', ':p1' => 'DummyModel',':p2'=> 'float',':p3'=>1.123], $params);
    }

    public function testWhereRelatedError(): void
    {
        $this->expectException(RuntimeException::class);
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $model = new DummyModel();
        $builder = MysqlQueryBuilder::forModel($model, 'DummyModel');
        $builder->whereRelated(['column' => 'updated_at', 'operator' => 'IS NULL']);
    }

    public function testSearch(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $model = new DummyModel();
        $builder = MysqlQueryBuilder::forModel($model, 'DummyModel');
        $builder->search('test  2 2.5 7bead738-fc30-4d11-87ee-74178abbc9fb');
        $query = trim($builder->toSql());
        self::assertSame('SELECT * FROM `node`  WHERE (`node`.`id` = :p6 OR `node`.`id` IN (SELECT `node_id` FROM `node_property` WHERE `value_string` = :p7 AND `deleted_at` IS NULL)) AND `node`.`id` IN (SELECT `node_id` FROM `node_property` WHERE ((`value_float` > 1.9999 AND `value_float` < 2.0001) OR `value_int` = :p2 OR `value_string` LIKE :p3) AND `deleted_at` IS NULL) AND `node`.`id` IN (SELECT `node_id` FROM `node_property` WHERE ((`value_float` > 2.4999 AND `value_float` < 2.5001) OR `value_int` = :p4 OR `value_string` LIKE :p5) AND `deleted_at` IS NULL) AND `node`.`id` IN (SELECT `node_id` FROM `node_property` WHERE (`value_string` LIKE :p1) AND `deleted_at` IS NULL) AND `node`.`model` = :p0 AND `node`.`deleted_at` IS NULL', $query);
        $params = $builder->getParameters();
        $expected = [
            ':p0' => 'DummyModel',
            ':p1' => '%test%',
            ':p2' => 2,
            ':p3' => '%2%',
            ':p4' => 2,
            ':p5' => '%2.5%',
            ':p6' => '7bead738-fc30-4d11-87ee-74178abbc9fb',
            ':p7' => '7bead738-fc30-4d11-87ee-74178abbc9fb',
        ];
        self::assertSame($expected, $params);
    }

    public function testCountSql(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $model = new DummyModel();
        $builder = MysqlQueryBuilder::forModel($model, 'DummyModel');
        $query = trim($builder->toCountSql());
        self::assertSame('SELECT count(*) FROM `node`  WHERE `node`.`model` = :p0 AND `node`.`deleted_at` IS NULL', $query);
        $params = $builder->getParameters();
        self::assertSame([':p0' => 'DummyModel'], $params);

    }

    public function testRelationSelect(): void
    {
        $relation = Relation::belongsToMany(DummyModel::class);
        $relation->pivot_property = Field::string()->nullable();
        $builder = MysqlQueryBuilder::forRelation($relation, ['a']);
        $builder->select(['created_at', '_created_at', '_pivot_property']);
        $query = trim($builder->toSql());
        self::assertSame('SELECT `edge_pivot_property`.`value_float`), `edge_pivot_property`.`value_int`), `edge_pivot_property`.`value_string`), `edge`.`created_at`, `node`.`created_at` FROM `edge` LEFT JOIN `node` ON `node`.`id` = `edge`.`parent_id` LEFT JOIN `edge_property` AS `edge_pivot_property` ON (`edge_pivot_property`.`parent_id` = `edge`.`parent_id` AND `edge_pivot_property`.`child_id` = `edge`.`child_id`  AND `edge_pivot_property`.`property` = :p2 AND `edge_pivot_property`.`deleted_at` IS NULL) WHERE `edge`.`child_id` IN (:p0) AND `edge`.`parent` = :p1 AND `edge`.`deleted_at` IS NULL AND `node`.`deleted_at` IS NULL', $query);
        $params = $builder->getParameters();
        self::assertSame([':p0' => 'a', ':p1' => 'DummyModel',':p2' => 'pivot_property'], $params);
    }



}