<?php


namespace Mrap\GraphCool\Tests\Model;


use GraphQL\Type\Definition\Type;
use Mrap\GraphCool\Model\Field;
use Mrap\GraphCool\Model\Model;
use Mrap\GraphCool\Model\Settings;
use Mrap\GraphCool\Tests\TestCase;

class ModelTest extends TestCase
{

    public function testConstructor(): void
    {
        $model = new Model();
        self::assertInstanceOf(Model::class, $model);
        self::assertInstanceOf(Field::class, $model->id);
        self::assertEquals(Type::ID, $model->id->type);
        self::assertInstanceOf(Field::class, $model->created_at);
        self::assertEquals(Field::CREATED_AT, $model->created_at->type);
        self::assertInstanceOf(Field::class, $model->updated_at);
        self::assertEquals(Field::UPDATED_AT, $model->updated_at->type);
        self::assertInstanceOf(Field::class, $model->deleted_at);
        self::assertEquals(Field::DELETED_AT, $model->deleted_at->type);
    }

    public function testBeforeInsert(): void
    {
        $data = ['test'];
        $model = new Model();
        $result = $model->beforeInsert('tenant-id', $data);
        self::assertEquals($data, $result);
    }

    public function testBeforeUpdate(): void
    {
        $data = ['test'];
        $model = new Model();
        $result = $model->beforeUpdate('tenant-id', 'node-id', $data);
        self::assertEquals($data, $result);
    }

    public function testAfterRelationUpdateButBeforeNodeUpdate(): void
    {
        $data = ['test'];
        $model = new Model();
        $result = $model->afterRelationUpdateButBeforeNodeUpdate('tenant-id', 'node-id', $data);
        self::assertEquals($data, $result);
    }

    public function testSettings()
    {
        $model = new Model();
        $settings = $model->settings();
        self::assertInstanceOf(Settings::class, $settings);
    }

    public function testEmptyMethods()
    {
        $model = new Model();
        $data = new \stdClass();
        $closure = static function(){};

        $originalData = clone $data;

        $model->afterInsert($data);
        $model->afterUpdate($data);
        $model->afterDelete($data);
        $model->afterBulkUpdate($closure);

        self::assertEquals($originalData, $data);
    }

}