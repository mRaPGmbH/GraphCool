<?php


namespace Mrap\GraphCool\Tests\Model;


use App\Models\DummyModel;
use GraphQL\Type\Definition\Type;
use Mrap\GraphCool\Definition\Field;
use Mrap\GraphCool\Definition\Model;
use Mrap\GraphCool\Definition\Settings;
use Mrap\GraphCool\Tests\TestCase;

class ModelTest extends TestCase
{

    public function testConstructor(): void
    {
        $model = new Model();
        self::assertInstanceOf(Model::class, $model);
        self::assertInstanceOf(Field::class, $model->id);
        self::assertSame(Type::ID, $model->id->type);
        self::assertInstanceOf(Field::class, $model->created_at);
        self::assertSame(Field::CREATED_AT, $model->created_at->type);
        self::assertInstanceOf(Field::class, $model->updated_at);
        self::assertSame(Field::UPDATED_AT, $model->updated_at->type);
        self::assertInstanceOf(Field::class, $model->deleted_at);
        self::assertSame(Field::DELETED_AT, $model->deleted_at->type);
    }

    public function testBeforeInsert(): void
    {
        $data = ['test'];
        $model = new Model();
        $result = $model->beforeInsert('tenant-id', $data);
        self::assertSame($data, $result);
    }

    public function testBeforeUpdate(): void
    {
        $data = ['test'];
        $model = new Model();
        $result = $model->beforeUpdate('tenant-id', 'node-id', $data);
        self::assertSame($data, $result);
    }

    public function testAfterRelationUpdateButBeforeNodeUpdate(): void
    {
        $data = ['test'];
        $model = new Model();
        $result = $model->afterRelationUpdateButBeforeNodeUpdate('tenant-id', 'node-id', $data);
        self::assertSame($data, $result);
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

    public function testGetPropertyNamesForFulltextIndexing(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $model = new DummyModel();
        $fulltext = $model->getPropertyNamesForFulltextIndexing();
        self::assertEquals(['last_name'], $fulltext);
   }

    public function testGetEdgePropertyNamesForFulltextIndexing(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $model = new DummyModel();
        $fulltext = $model->getEdgePropertyNamesForFulltextIndexing();
        self::assertEquals(['pivot_property'], $fulltext);
   }

}
