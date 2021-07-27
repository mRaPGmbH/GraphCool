<?php

namespace Types;


use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Mrap\GraphCool\DataSource\DB;
use Mrap\GraphCool\DataSource\File;
use Mrap\GraphCool\DataSource\Mysql\MysqlDataProvider;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\MutationType;
use Mrap\GraphCool\Types\TypeLoader;
use Mrap\GraphCool\Utils\ClassFinder;
use Mrap\GraphCool\Utils\FileImport2;

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
        $mock = $this->createMock(FileImport2::class);
        $return = [[],[],[]];
        $expected = (object) [
            'inserted_rows' => 0,
            'inserted_ids' => [],
            'updated_rows' => 0,
            'updated_ids' => [],
            'affected_rows' => 0,
            'affected_ids' => [],
            'failed_rows' => 0,
            'failed_row_numbers' => [],
            'errors' => [],
        ];

        $mock->expects($this->once())
            ->method('import')
            ->with('Classname', [], 0)
            ->willReturn($return);

        File::setImporter($mock);
        $result = $closure([], [], [], $info);

        self::assertEquals($expected, $result);
    }

    public function testResolveImport2(): void
    {
        $this->provideJwt();
        $query = new MutationType(new TypeLoader());
        $closure = $query->resolveFieldFn;
        $info = $this->createMock(ResolveInfo::class);
        $info->fieldName = 'importClassnames';
        $info->returnType = $this->createMock(Type::class);
        $info->returnType->name = '_FileImport';
        $mock = $this->createMock(FileImport2::class);
        $return = [['data'=>['a'=>'b']],[['id'=>'id0','data'=>['c'=>'d']]],[]];
        $expected = (object) [
            'inserted_rows' => 1,
            'inserted_ids' => ['id1'],
            'updated_rows' => 1,
            'updated_ids' => ['id2'],
            'affected_rows' => 2,
            'affected_ids' => ['id1','id2'],
            'failed_rows' => 0,
            'failed_row_numbers' => [],
            'errors' => [],
        ];
        $mock->expects($this->once())
            ->method('import')
            ->with('Classname', [], 0)
            ->willReturn($return);

        File::setImporter($mock);
        $mock2 = $this->createMock(MysqlDataProvider::class);
        $mock2->expects($this->once())
            ->method('insert')
            ->willReturn((object)['id'=>'id1']);
        $mock2->expects($this->once())
            ->method('update')
            ->willReturn((object)['id'=>'id2']);
        DB::setProvider($mock2);
        $result = $closure([], [], [], $info);
        self::assertEquals($expected, $result);
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