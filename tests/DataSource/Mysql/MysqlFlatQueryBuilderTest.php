<?php

namespace Mrap\GraphCool\Tests\DataSource\Mysql;

use GraphQL\Error\Error;
use Mrap\GraphCool\DataSource\Mysql\MysqlFlatQueryBuilder;
use Mrap\GraphCool\Tests\TestCase;
use RuntimeException;

class MysqlFlatQueryBuilderTest extends TestCase
{
    public function testForTable(): void
    {
        $builder = MysqlFlatQueryBuilder::forTable('table');
        self::assertInstanceOf(MysqlFlatQueryBuilder::class, $builder);
        $query = trim($builder->toSql());
        self::assertSame('SELECT * FROM `table`', $query);
        $params = $builder->getParameters();
        self::assertSame([], $params);
    }

    public function testSelect(): void
    {
        $builder = MysqlFlatQueryBuilder::forTable('table')
            ->select(['some_field', 'other_field'])
            ->orderBy([['field' => 'some_field', 'order' => 'ASC'],['field' => 'other_field', 'order' => 'DESC'], ['order' => 'RAND']])
            ->limit(10,10);
        $query = trim($builder->toSql());
        self::assertSame('SELECT `table`.`other_field`, `table`.`some_field` FROM `table`  ORDER BY `table`.`some_field` ASC, `table`.`other_field` DESC, rand()  LIMIT 10, 10', $query);
        $params = $builder->getParameters();
        self::assertSame([], $params);
    }

    public function testSelectError(): void
    {
        $this->expectException(RuntimeException::class);
        MysqlFlatQueryBuilder::forTable('table')
            ->update(['some_field' => 123])
            ->select(['some_field']);
    }

    public function testSelectMax(): void
    {
        $builder = MysqlFlatQueryBuilder::forTable('table')
            ->selectMax('some_field', 'count');
        $query = trim($builder->toSql());
        self::assertSame('SELECT max(`table`.`some_field`) AS `count` FROM `table`', $query);
        $params = $builder->getParameters();
        self::assertSame([], $params);
    }

    public function testSelectMaxError(): void
    {
        $this->expectException(RuntimeException::class);
        MysqlFlatQueryBuilder::forTable('table')
            ->update(['some_field' => 123])
            ->selectMax('some_field', 'count');
    }

    public function testSelectSum(): void
    {
        $builder = MysqlFlatQueryBuilder::forTable('table')
            ->selectSum('some_field', 'total');
        $query = trim($builder->toSql());
        self::assertSame('SELECT sum(`table`.`some_field`) AS `total` FROM `table`', $query);
        $params = $builder->getParameters();
        self::assertSame([], $params);
    }

    public function testSelectSumError(): void
    {
        $this->expectException(RuntimeException::class);
        MysqlFlatQueryBuilder::forTable('table')
            ->update(['some_field' => 123])
            ->selectSum('some_field', 'total');
    }

    public function testDelete(): void
    {
        $this->expectException(RuntimeException::class);
        $builder = MysqlFlatQueryBuilder::forTable('table')
            ->delete();
        $builder->toSql();
        // delete not implemented yet!
    }

    public function testDeleteError(): void
    {
        $this->expectException(RuntimeException::class);
        MysqlFlatQueryBuilder::forTable('table')
            ->select(['some_field'])
            ->delete();
    }

    public function testUpdate(): void
    {
        $builder = MysqlFlatQueryBuilder::forTable('table')
            ->update(['some_field' => 'new-value']);
        $query = trim($builder->toSql());
        self::assertSame('UPDATE `table`  SET `table`.`updated_at` = now(), `table`.`some_field` = :u0 WHERE', $query);
        $params = $builder->getUpdateParameters();
        self::assertSame([':u0' => 'new-value'], $params);
    }

    public function testUpdateError(): void
    {
        $this->expectException(RuntimeException::class);
        MysqlFlatQueryBuilder::forTable('table')
            ->select(['some_field'])
            ->update(['some_field' => 'new-value']);
    }

    public function testWhere(): void
    {
        $builder = MysqlFlatQueryBuilder::forTable('table')
            ->where(['OR' => [
                ['column' => 'some_field', 'operator' => '=', 'value' => 'x'],
                ['column' => 'other_field', 'operator' => 'IN', 'value' => ['a','b','c']],
                ['column' => 'whatever', 'operator' => 'BETWEEN', 'value' => [1, 99]],
            ]]);
        $query = trim($builder->toSql());
        self::assertSame('SELECT * FROM `table`  WHERE (`table`.`some_field` = :p0 OR `table`.`other_field` IN (:p1,:p2,:p3) OR `table`.`whatever` BETWEEN :p4 AND :p5)', $query);
        $params = $builder->getParameters();
        self::assertSame([
            ':p0' => 'x',
            ':p1' => 'a',
            ':p2' => 'b',
            ':p3' => 'c',
            ':p4' => 1,
            ':p5' => 99,
        ], $params);
    }

    public function testWhere2(): void
    {
        $builder = MysqlFlatQueryBuilder::forTable('table')
            ->where(['AND' => [
                ['column' => 'some_field', 'operator' => '=', 'value' => 'x'],
                ['column' => 'other_field', 'operator' => 'IN', 'value' => ['a','b','c']],
                ['column' => 'whatever', 'operator' => 'BETWEEN', 'value' => [1, 99]],
            ]]);
        $query = trim($builder->toSql());
        self::assertSame('SELECT * FROM `table`  WHERE (`table`.`some_field` = :p0 AND `table`.`other_field` IN (:p1,:p2,:p3) AND `table`.`whatever` BETWEEN :p4 AND :p5)', $query);
        $params = $builder->getParameters();
        self::assertSame([
            ':p0' => 'x',
            ':p1' => 'a',
            ':p2' => 'b',
            ':p3' => 'c',
            ':p4' => 1,
            ':p5' => 99,
        ], $params);
    }

    public function testWhereError1(): void
    {
        $this->expectException(Error::class);
        MysqlFlatQueryBuilder::forTable('table')
            ->where(['column' => 'some_field', 'operator' => 'IN', 'value' => 1]);
    }

    public function testWhereError2(): void
    {
        $this->expectException(Error::class);
        MysqlFlatQueryBuilder::forTable('table')
            ->where(['column' => 'some_field', 'operator' => 'IN', 'value' => []]);
    }

    public function testWhereError3(): void
    {
        $this->expectException(Error::class);
        MysqlFlatQueryBuilder::forTable('table')
            ->where(['column' => 'some_field', 'operator' => '=']);
    }

    public function testWhereError4(): void
    {
        $this->expectException(Error::class);
        MysqlFlatQueryBuilder::forTable('table')
            ->where(['column' => 'some_field', 'operator' => 'BETWEEN', 'value' => ['a']]);
    }

    public function testWhereEmpty(): void
    {
        $builder = MysqlFlatQueryBuilder::forTable('table')
            ->where(['AND' => []]);
        $query = trim($builder->toSql());
        self::assertSame('SELECT * FROM `table`', $query);
        $params = $builder->getParameters();
        self::assertSame([], $params);
    }


}