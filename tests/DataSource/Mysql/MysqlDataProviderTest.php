<?php


namespace Mrap\GraphCool\Tests\DataSource\Mysql;


use GraphQL\Error\Error;
use Mrap\GraphCool\DataSource\Mysql\Mysql;
use Mrap\GraphCool\DataSource\Mysql\MysqlConnector;
use Mrap\GraphCool\DataSource\Mysql\MysqlDataProvider;
use Mrap\GraphCool\DataSource\Mysql\MysqlNodeReader;
use Mrap\GraphCool\DataSource\Mysql\MysqlNodeWriter;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Enums\ResultType;
use stdClass;

class MysqlDataProviderTest extends TestCase
{

    public function testFindAllError(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $this->expectException(Error::class);
        $provider = new MysqlDataProvider();
        $provider->findAll('a12f', 'DummyModel', ['page' => 0]);
    }

    public function testFindAll(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->exactly(3))
            ->method('fetchAll')
            ->withAnyParameters()
            ->willReturn([(object)['id'=>'y5']]);
        $mock->expects($this->exactly(3))
            ->method('fetchColumn')
            ->withAnyParameters()
            ->willReturn('1');
        $mock->expects($this->never())
            ->method('fetch');
        Mysql::setConnector($mock);

        $node = (object)[
            'id' => 'y5',
            'tenant_id' => 'a12f',
            'model' => 'DummyModel',
            'created_at' => '2021-08-30 12:00:00',
            'updated_at' => null,
            'deleted_at' => null
        ];

        $readerMock = $this->createMock(MysqlNodeReader::class);
        $readerMock->expects($this->exactly(3))
            ->method('load')
            ->withAnyParameters()
            ->willReturn($node);

        $provider = new MysqlDataProvider();
        $expected = (object)[
            'count' => 1,
            'currentPage' => 1,
            'firstItem' => 1,
            'hasMorePages' => false,
            'lastItem' => 1,
            'lastPage' => 1,
            'perPage' => 10,
            'total' => 1
        ];
        Mysql::setNodeReader($readerMock);
        foreach ([ResultType::DEFAULT, ResultType::WITH_TRASHED, ResultType::ONLY_SOFT_DELETED] as $resultType) {
            $result = $provider->findAll('a12f', 'DummyModel', ['where' => ['column' => 'last_name', 'operator' => '=', 'value' => 'b'], 'result' => $resultType]);
            self::assertEquals($expected, $result->paginatorInfo);
            self::assertEquals([$node], $result->data);
        }
    }

    public function testGetMax(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('fetch')
            ->withAnyParameters()
            ->willReturn((object)['max' => 'Zander']);
        Mysql::setConnector($mock);

        $provider = new MysqlDataProvider();
        $result = $provider->getMax('a12f', 'DummyModel', 'last_name');

        self::assertEquals('Zander', $result);
    }

    public function testGetMaxInt(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('fetch')
            ->withAnyParameters()
            ->willReturn(null);
        Mysql::setConnector($mock);

        $provider = new MysqlDataProvider();
        $result = $provider->getMax('a12f', 'DummyModel', 'date');

        self::assertEquals(0, $result);
    }

    public function testGetMaxFloat(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('fetch')
            ->withAnyParameters()
            ->willReturn(null);
        Mysql::setConnector($mock);

        $provider = new MysqlDataProvider();
        $result = $provider->getMax('a12f', 'DummyModel', 'float');

        self::assertEquals(0.0, $result);
    }

    public function testGetMaxBoolean(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('fetch')
            ->withAnyParameters()
            ->willReturn(null);
        Mysql::setConnector($mock);

        $provider = new MysqlDataProvider();
        $result = $provider->getMax('a12f', 'DummyModel', 'bool');

        self::assertEquals(false, $result);
    }

    public function testInsert(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $writerMock = $this->createMock(MysqlNodeWriter::class);
        $writerMock->expects($this->once())
            ->method('insert')
            ->withAnyParameters();

        $node = (object)[
            'id' => 'y5',
            'tenant_id' => 'a12f',
            'model' => 'DummyModel',
            'created_at' => '2021-08-30 12:00:00',
            'updated_at' => null,
            'deleted_at' => null
        ];

        $readerMock = $this->createMock(MysqlNodeReader::class);
        $readerMock->expects($this->once())
            ->method('load')
            ->withAnyParameters()
            ->willReturn($node);

        Mysql::setNodeReader($readerMock);
        Mysql::setNodeWriter($writerMock);

        $provider = new MysqlDataProvider();
        $result = $provider->insert('a12f', 'DummyModel', []);

        self::assertEquals($node, $result);
    }

    public function testUpdate(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $writerMock = $this->createMock(MysqlNodeWriter::class);
        $writerMock->expects($this->once())
            ->method('update')
            ->withAnyParameters();

        $node = (object)[
            'id' => 'y5',
            'tenant_id' => 'a12f',
            'model' => 'DummyModel',
            'created_at' => '2021-08-30 12:00:00',
            'updated_at' => null,
            'deleted_at' => null
        ];

        $readerMock = $this->createMock(MysqlNodeReader::class);
        $readerMock->expects($this->once())
            ->method('load')
            ->withAnyParameters()
            ->willReturn($node);

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('fetchColumn')
            ->withAnyParameters()
            ->willReturn(1);

        Mysql::setConnector($mock);
        Mysql::setNodeReader($readerMock);
        Mysql::setNodeWriter($writerMock);

        $provider = new MysqlDataProvider();
        $result = $provider->update('a12f', 'DummyModel', ['id' => 'y5']);

        self::assertEquals($node, $result);
    }

    public function testUpdateExistsError(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $writerMock = $this->createMock(MysqlNodeWriter::class);
        $writerMock->expects($this->never())
            ->method('update');

        $readerMock = $this->createMock(MysqlNodeReader::class);
        $readerMock->expects($this->never())
            ->method('load');

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('fetchColumn')
            ->withAnyParameters()
            ->willReturn(0);

        Mysql::setConnector($mock);
        Mysql::setNodeReader($readerMock);
        Mysql::setNodeWriter($writerMock);

        $provider = new MysqlDataProvider();
        $this->expectException(Error::class);
        $provider->update('a12f', 'DummyModel', ['id' => 'y5']);
    }

    public function testUpdateUniqueError(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $writerMock = $this->createMock(MysqlNodeWriter::class);
        $writerMock->expects($this->never())
            ->method('update');

        $readerMock = $this->createMock(MysqlNodeReader::class);
        $readerMock->expects($this->never())
            ->method('load');

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->exactly(2))
            ->method('fetchColumn')
            ->withAnyParameters()
            ->willReturn(1);

        Mysql::setConnector($mock);
        Mysql::setNodeReader($readerMock);
        Mysql::setNodeWriter($writerMock);

        $provider = new MysqlDataProvider();
        $this->expectException(Error::class);
        $provider->update('a12f', 'DummyModel', ['id' => 'y5', 'data' => ['unique' => 'something that already exists']]);
    }

    public function testUpdateNullError(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $writerMock = $this->createMock(MysqlNodeWriter::class);
        $writerMock->expects($this->never())
            ->method('update');

        $readerMock = $this->createMock(MysqlNodeReader::class);
        $readerMock->expects($this->never())
            ->method('load');

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('fetchColumn')
            ->withAnyParameters()
            ->willReturn(1);

        Mysql::setConnector($mock);
        Mysql::setNodeReader($readerMock);
        Mysql::setNodeWriter($writerMock);

        $provider = new MysqlDataProvider();
        $this->expectException(Error::class);
        $provider->update('a12f', 'DummyModel', ['id' => 'y5', 'data' => ['enum' => null]]);
    }

    public function testUpdateMany(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $expected = new stdClass();

        $writerMock = $this->createMock(MysqlNodeWriter::class);
        $writerMock->expects($this->once())
            ->method('updateMany')
            ->withAnyParameters()
            ->willReturn($expected);

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('fetchAll')
            ->withAnyParameters()
            ->willReturn([(object)['id' => 'y5']]);

        Mysql::setConnector($mock);
        Mysql::setNodeWriter($writerMock);

        $provider = new MysqlDataProvider();
        $result = $provider->updateMany('a12f', 'DummyModel', ['id' => 'y5']);

        self::assertEquals($expected, $result);
    }

    public function testDelete(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $writerMock = $this->createMock(MysqlNodeWriter::class);
        $writerMock->expects($this->once())
            ->method('delete')
            ->withAnyParameters();

        $node = (object)[
            'id' => 'y5',
            'tenant_id' => 'a12f',
            'model' => 'DummyModel',
            'created_at' => '2021-08-30 12:00:00',
            'updated_at' => null,
            'deleted_at' => null
        ];

        $readerMock = $this->createMock(MysqlNodeReader::class);
        $readerMock->expects($this->once())
            ->method('load')
            ->withAnyParameters()
            ->willReturn($node);

        Mysql::setNodeWriter($writerMock);
        Mysql::setNodeReader($readerMock);

        $provider = new MysqlDataProvider();
        $result = $provider->delete('a12f', 'DummyModel', 'y5');

        self::assertEquals($node, $result);
    }

    public function testRestore(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $writerMock = $this->createMock(MysqlNodeWriter::class);
        $writerMock->expects($this->once())
            ->method('restore')
            ->withAnyParameters();

        $node = (object)[
            'id' => 'y5',
            'tenant_id' => 'a12f',
            'model' => 'DummyModel',
            'created_at' => '2021-08-30 12:00:00',
            'updated_at' => null,
            'deleted_at' => null
        ];

        $readerMock = $this->createMock(MysqlNodeReader::class);
        $readerMock->expects($this->once())
            ->method('load')
            ->withAnyParameters()
            ->willReturn($node);

        Mysql::setNodeWriter($writerMock);
        Mysql::setNodeReader($readerMock);

        $provider = new MysqlDataProvider();
        $result = $provider->restore('a12f', 'DummyModel', 'y5');

        self::assertEquals($node, $result);
    }

}