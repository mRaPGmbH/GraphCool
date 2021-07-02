<?php

namespace Types;


use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Mrap\GraphCool\DataSource\DB;
use Mrap\GraphCool\DataSource\File;
use Mrap\GraphCool\DataSource\Providers\MysqlDataProvider;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\MutationType;
use Mrap\GraphCool\Types\QueryType;
use Mrap\GraphCool\Types\TypeLoader;
use Mrap\GraphCool\Utils\ClassFinder;
use Mrap\GraphCool\Utils\FileExport;
use Mrap\GraphCool\Utils\FileImport;

class MutationTypeTest extends TestCase
{

    public function testConstructor(): void
    {
        require_once($this->dataPath().'/app/Mutations/DummyMutation.php');
        ClassFinder::setRootPath($this->dataPath());
        $query = new MutationType(new TypeLoader());
        self::assertInstanceOf(ObjectType::class, $query);
    }

    public function testCustomResolver(): void
    {
        $this->provideJwt();
        ClassFinder::setRootPath($this->dataPath());
        $query = new MutationType(new TypeLoader());
        $closure = $query->resolveFieldFn;
        $info = $this->createMock(ResolveInfo::class);
        $info->fieldName = 'DummyMutation';
        $result = $closure([], ['_timezone' => 0], [], $info);
        self::assertEquals('dummy-mutation-resolve', $result);
    }

    public function testResolveCreate(): void
    {
        $this->provideJwt();
        $query = new MutationType(new TypeLoader());
        $closure = $query->resolveFieldFn;
        $info = $this->createMock(ResolveInfo::class);
        $info->fieldName = 'createClassname';
        $info->returnType = $this->createMock(Type::class);
        $info->returnType->expects($this->once())
            ->method('toString')
            ->willReturn('classname');

        $mock = $this->createMock(MysqlDataProvider::class);

        $object = (object) ['id'=>123, 'last_name'=>'test'];

        $mock->expects($this->once())
            ->method('insert')
            ->with(1, 'classname', ['id' => 'some-id-string'])
            ->willReturn($object);

        DB::setProvider($mock);
        $result = $closure([], ['id' => 'some-id-string'], [], $info);

        self::assertEquals($object, $result);
    }

    public function testResolveUpdateMany(): void
    {
        $this->provideJwt();
        $query = new MutationType(new TypeLoader());
        $closure = $query->resolveFieldFn;
        $info = $this->createMock(ResolveInfo::class);
        $info->fieldName = 'updateManyClassnames';
        $info->returnType = $this->createMock(Type::class);

        $mock = $this->createMock(MysqlDataProvider::class);

        $object = (object) ['id'=>123, 'last_name'=>'test'];

        $mock->expects($this->once())
            ->method('updateMany')
            ->with(1, 'Classname', ['id' => 'some-id-string'])
            ->willReturn($object);

        DB::setProvider($mock);
        $result = $closure([], ['id' => 'some-id-string'], [], $info);

        self::assertEquals($object, $result);
    }

    public function testResolveUpdate(): void
    {
        $this->provideJwt();
        $query = new MutationType(new TypeLoader());
        $closure = $query->resolveFieldFn;
        $info = $this->createMock(ResolveInfo::class);
        $info->fieldName = 'updateClassname';
        $info->returnType = $this->createMock(Type::class);
        $info->returnType->expects($this->once())
            ->method('toString')
            ->willReturn('classname');

        $mock = $this->createMock(MysqlDataProvider::class);

        $object = (object) ['id'=>123, 'last_name'=>'test'];

        $mock->expects($this->once())
            ->method('update')
            ->with(1, 'classname', ['id' => 'some-id-string'])
            ->willReturn($object);

        DB::setProvider($mock);
        $result = $closure([], ['id' => 'some-id-string'], [], $info);

        self::assertEquals($object, $result);
    }

    public function testResolveDelete(): void
    {
        $this->provideJwt();
        $query = new MutationType(new TypeLoader());
        $closure = $query->resolveFieldFn;
        $info = $this->createMock(ResolveInfo::class);
        $info->fieldName = 'deleteClassname';
        $info->returnType = $this->createMock(Type::class);
        $info->returnType->expects($this->once())
            ->method('toString')
            ->willReturn('classname');

        $mock = $this->createMock(MysqlDataProvider::class);

        $object = (object) ['id'=>123, 'last_name'=>'test'];

        $mock->expects($this->once())
            ->method('delete')
            ->with(1, 'classname', 'some-id-string')
            ->willReturn($object);

        DB::setProvider($mock);
        $result = $closure([], ['id' => 'some-id-string'], [], $info);

        self::assertEquals($object, $result);
    }

    public function testResolveRestore(): void
    {
        $this->provideJwt();
        $query = new MutationType(new TypeLoader());
        $closure = $query->resolveFieldFn;
        $info = $this->createMock(ResolveInfo::class);
        $info->fieldName = 'restoreClassname';
        $info->returnType = $this->createMock(Type::class);
        $info->returnType->expects($this->once())
            ->method('toString')
            ->willReturn('classname');

        $mock = $this->createMock(MysqlDataProvider::class);

        $object = (object) ['id'=>123, 'last_name'=>'test'];

        $mock->expects($this->once())
            ->method('restore')
            ->with(1, 'classname', 'some-id-string')
            ->willReturn($object);

        DB::setProvider($mock);
        $result = $closure([], ['id' => 'some-id-string'], [], $info);

        self::assertEquals($object, $result);
    }

    public function testResolveImport(): void
    {
        $this->provideJwt();
        $query = new MutationType(new TypeLoader());
        $closure = $query->resolveFieldFn;
        $info = $this->createMock(ResolveInfo::class);
        $info->fieldName = 'importClassnames';
        $info->returnType = $this->createMock(Type::class);
        $info->returnType->name = '_FileImport';
        $mock = $this->createMock(FileImport::class);
        $object = (object) ['id'=>123, 'last_name'=>'test'];

        $mock->expects($this->once())
            ->method('import')
            ->with([])
            ->willReturn($object);

        File::setImporter($mock);
        $result = $closure([], [], [], $info);

        self::assertEquals($object, $result);
    }

    public function testResolveError(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->provideJwt();
        $query = new MutationType(new TypeLoader());
        $closure = $query->resolveFieldFn;
        $info = $this->createMock(ResolveInfo::class);
        $info->fieldName = 'not-a-known-mutation';

        $closure([], [], [], $info);
    }

}