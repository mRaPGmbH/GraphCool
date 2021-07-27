<?php

namespace Mrap\GraphCool\Tests\Types;


use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Mrap\GraphCool\DataSource\DB;
use Mrap\GraphCool\DataSource\File;
use Mrap\GraphCool\DataSource\Mysql\MysqlDataProvider;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\QueryType;
use Mrap\GraphCool\Types\TypeLoader;
use Mrap\GraphCool\Utils\ClassFinder;
use Mrap\GraphCool\Utils\FileExport;

class QueryTypeTest extends TestCase
{

    public function testConstructor(): void
    {
        require_once($this->dataPath().'/app/Queries/DummyQuery.php');
        ClassFinder::setRootPath($this->dataPath());
        $query = new QueryType(new TypeLoader());
        self::assertInstanceOf(ObjectType::class, $query);
    }

    public function testResolve(): void
    {
        $this->provideJwt();
        $query = new QueryType(new TypeLoader());
        $closure = $query->resolveFieldFn;
        $info = $this->createMock(ResolveInfo::class);
        $info->returnType = $this->createMock(Type::class);
        $info->returnType->expects($this->once())
            ->method('toString')
            ->willReturn('classname');
        $info->returnType->name = 'name';

        $mock = $this->createMock(MysqlDataProvider::class);

        $object = (object) ['id'=>123, 'last_name'=>'test'];

        $mock->expects($this->once())
            ->method('load')
            ->with(1, 'classname', 'some-id-string')
            ->willReturn($object);

        DB::setProvider($mock);
        $result = $closure([], ['id' => 'some-id-string', '_timezone' => 0], [], $info);

        self::assertEquals($object, $result);
    }

    public function testCustomResolver(): void
    {
        $this->provideJwt();
        $query = new QueryType(new TypeLoader());
        $closure = $query->resolveFieldFn;
        $info = $this->createMock(ResolveInfo::class);
        $info->fieldName = 'DummyQuery';
        $result = $closure([], [], [], $info);
        self::assertEquals('dummy-query-resolve', $result);
    }

    public function testResolvePaginator(): void
    {
        $this->provideJwt();
        $query = new QueryType(new TypeLoader());
        $closure = $query->resolveFieldFn;
        $info = $this->createMock(ResolveInfo::class);
        $info->returnType = $this->createMock(Type::class);
        $info->returnType->expects($this->once())
            ->method('toString')
            ->willReturn('_classnamePaginator');
        $info->returnType->name = 'namePaginator';

        $mock = $this->createMock(MysqlDataProvider::class);

        $object = (object) ['id'=>123, 'last_name'=>'test'];

        $mock->expects($this->once())
            ->method('findAll')
            ->with(1, 'classname', [])
            ->willReturn($object);

        DB::setProvider($mock);
        $result = $closure([], [], [], $info);

        self::assertEquals($object, $result);
    }

    public function testResolveExport(): void
    {
        $this->provideJwt();
        $query = new QueryType(new TypeLoader());
        $closure = $query->resolveFieldFn;
        $info = $this->createMock(ResolveInfo::class);
        $info->fieldName = 'exportClassnames';
        $info->returnType = $this->createMock(Type::class);
        $info->returnType->name = '_FileExport';
        $mock = $this->createMock(FileExport::class);
        $object = (object) ['id'=>123, 'last_name'=>'test'];

        $mock->expects($this->once())
            ->method('export')
            ->with('Classname', [], ['first' => 1048575], 'xlsx')
            ->willReturn($object);

        File::setExporter($mock);
        $result = $closure([], [], [], $info);

        self::assertEquals($object, $result);
    }

}