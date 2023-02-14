<?php


namespace Mrap\GraphCool\Tests\Types\Objects;


use GraphQL\Error\Error;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Objects\ModelObject;

class ModelTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        $object = new ModelObject('DummyModel');
        self::assertInstanceOf(ObjectType::class, $object);
    }

    public function testConstructorError(): void
    {
        $this->expectException(Error::class);
        new ModelObject('DoesNotExist');
    }

    public function testResolve(): void
    {
        $object = new ModelObject('DummyModel');
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
        $object = new ModelObject('DummyModel');
        $closure = $object->resolveFieldFn;
        $info = $this->createMock(ResolveInfo::class);
        $info->fieldName = 'belongs_to_many';
        $data = new \stdClass();
        $data->belongs_to_many = function(array $args) {return 'some value';};
        $data->id = '123';
        $result = $closure($data, [], [], $info);
        self::assertSame('some value', $result);
    }

}