<?php


namespace Mrap\GraphCool\Tests\Types\Objects;


use GraphQL\Error\Error;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Objects\ModelType;
use Mrap\GraphCool\Types\TypeLoader;

class ModelTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        $object = new ModelType('DummyModel', new TypeLoader());
        self::assertInstanceOf(ObjectType::class, $object);
    }

    public function testConstructorError(): void
    {
        $this->expectException(Error::class);
        new ModelType('DoesNotExist', new TypeLoader());
    }

    public function testResolve(): void
    {
        $object = new ModelType('DummyModel', new TypeLoader());
        $closure = $object->resolveFieldFn;
        $info = $this->createMock(ResolveInfo::class);
        $info->fieldName = 'last_name';
        $data = new \stdClass();
        $data->last_name = 'Last Name';
        $result = $closure($data, [], [], $info);
        self::assertSame('Last Name', $result);
    }

    public function testResolveRelation(): void
    {
        $object = new ModelType('DummyModel', new TypeLoader());
        $closure = $object->resolveFieldFn;
        $info = $this->createMock(ResolveInfo::class);
        $info->fieldName = 'belongs_to_many';
        $data = new \stdClass();
        $data->belongs_to_many = function(array $args) {return 'some value';};
        $result = $closure($data, [], [], $info);
        self::assertSame('some value', $result);
    }

}