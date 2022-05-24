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
        $info->fieldName = 'test';

        $mock = $this->createMock(MysqlDataProvider::class);

        $object = (object) ['id'=>123, 'last_name'=>'test'];

        $mock->expects($this->once())
            ->method('load')
            ->with(1, 'classname', 'some-id-string')
            ->willReturn($object);

        DB::setProvider($mock);
        $result = $closure([], ['id' => 'some-id-string', '_timezone' => 0], [], $info);

        self::assertSame($object, $result);
    }

    public function testCustomResolver(): void
    {
        $this->provideJwt();
        $query = new QueryType(new TypeLoader());
        $closure = $query->resolveFieldFn;
        $info = $this->createMock(ResolveInfo::class);
        $info->fieldName = 'DummyQuery';
        $result = $closure([], [], [], $info);
        self::assertSame('dummy-query-resolve', $result);
    }

    public function testResolvePaginator(): void
    {
        $this->provideJwt();
        $query = new QueryType(new TypeLoader());
        $closure = $query->resolveFieldFn;
        $info = $this->createMock(ResolveInfo::class);
        $info->returnType = $this->createMock(Type::class);
        $info->returnType->name = '_classnamePaginator';

        $mock = $this->createMock(MysqlDataProvider::class);

        $object = (object) ['id'=>123, 'last_name'=>'test'];

        $mock->expects($this->once())
            ->method('findAll')
            ->with(1, 'classname', [])
            ->willReturn($object);

        DB::setProvider($mock);
        $result = $closure([], [], [], $info);

        self::assertSame($object, $result);
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

        self::assertSame($object, $result);
    }

    public function testResolveDiagram(): void
    {
        $this->provideJwt();
        $query = new QueryType(new TypeLoader());
        $closure = $query->resolveFieldFn;
        $info = $this->createMock(ResolveInfo::class);
        $info->fieldName = '__classDiagram';
        $result = $closure([], [], [], $info);
        $expected = '```mermaidNEWLINEclassDiagramNEWLINE    class DummyModel{NEWLINE        STRING last_nameNEWLINE        DATE dateNEWLINE        DATE_TIME date_timeNEWLINE        TIME timeNEWLINE        FLOAT floatNEWLINE        ENUM enum!NEWLINE        STRING uniqueNEWLINE        COUNTRY_CODE countryNEWLINE        TIMEZONE_OFFSET timezoneNEWLINE        LOCALE_CODE localeNEWLINE        CURRENCY_CODE currencyNEWLINE        LANGUAGE_CODE languageNEWLINE        DECIMAL decimalNEWLINE        BOOLEAN boolNEWLINE        FILE fileNEWLINE        BELONGS_TO(DummyModel) belongs_toNEWLINE        BELONGS_TO(DummyModel) belongs_to2NEWLINE        BELONGS_TO_MANY(DummyModel) belongs_to_manyNEWLINE        HAS_ONE(DummyModel) has_oneNEWLINE    }NEWLINE    DummyModel --|> DummyModel : String pivot_property!
String pivot_property2
String pivot_property3NEWLINE    DummyModel --|> DummyModel : NEWLINE    DummyModel --|> DummyModel : String pivot_property
ENUM pivot_enum!';
        self::assertSame($expected, $result);
    }

    public function testResolveToken(): void
    {
        $this->provideJwt();
        $query = new QueryType(new TypeLoader());
        $closure = $query->resolveFieldFn;
        $info = $this->createMock(ResolveInfo::class);
        $info->fieldName = '_Token';

        $jwt = $closure([], ['endpoint' => 'customer', 'operation' => 'read'], [], $info);

        $payload = json_decode(base64_decode(explode('.', $jwt)[1]??''));

        self::assertSame('crm', $payload->iss);
        self::assertSame('1', $payload->tid);
        self::assertSame('crm:customer.1', $payload->perm);
    }

    public function testResolveJobPaginator(): void
    {
        $this->provideJwt();
        $query = new QueryType(new TypeLoader());
        $closure = $query->resolveFieldFn;
        $info = $this->createMock(ResolveInfo::class);
        $info->returnType = $this->createMock(Type::class);
        $info->returnType->name = '_Import_JobPaginator';

        $mock = $this->createMock(MysqlDataProvider::class);
        $object = (object) ['id'=>123, 'last_name'=>'test'];
        $mock->expects($this->once())
            ->method('findJobs')
            ->withAnyParameters()
            ->willReturn($object);
        DB::setProvider($mock);

        $result = $closure([], [], [], $info);

        self::assertSame($object, $result);
    }

    public function testResolveExportAsync(): void
    {
        $this->provideJwt();
        $query = new QueryType(new TypeLoader());
        $closure = $query->resolveFieldFn;
        $info = $this->createMock(ResolveInfo::class);
        $info->fieldName = 'exportDummyModelsAsync';
        $info->returnType = $this->createMock(Type::class);
        $info->returnType->name = '_FileExport';

        $mock = $this->createMock(MysqlDataProvider::class);
        $mock->expects($this->once())
            ->method('addJob')
            ->withAnyParameters()
            ->willReturn('test-job-id');
        DB::setProvider($mock);

        $result = $closure([], [], [], $info);

        self::assertSame('test-job-id', $result);

    }

    public function testResolveExportJob(): void
    {
        $this->provideJwt();
        $query = new QueryType(new TypeLoader());
        $closure = $query->resolveFieldFn;
        $info = $this->createMock(ResolveInfo::class);
        $info->fieldName = '_ImportJob';
        $info->returnType = $this->createMock(Type::class);
        $info->returnType->name = '_ImportSummary';

        $mock = $this->createMock(MysqlDataProvider::class);
        $object = (object) ['id'=>123, 'last_name'=>'test'];
        $mock->expects($this->once())
            ->method('getJob')
            ->withAnyParameters()
            ->willReturn($object);
        DB::setProvider($mock);

        $result = $closure([], ['id' => '123'], [], $info);

        self::assertSame($object, $result);
    }

}
