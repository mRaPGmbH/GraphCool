<?php


namespace Mrap\GraphCool\Tests\DataSource\Mysql;


use GraphQL\Error\Error;
use Mrap\GraphCool\DataSource\File;
use Mrap\GraphCool\DataSource\FileSystem\SystemFileProvider;
use Mrap\GraphCool\DataSource\Mysql\Mysql;
use Mrap\GraphCool\DataSource\Mysql\MysqlConnector;
use Mrap\GraphCool\DataSource\Mysql\MysqlDataProvider;
use Mrap\GraphCool\DataSource\Mysql\MysqlNodeReader;
use Mrap\GraphCool\DataSource\Mysql\MysqlNodeWriter;
use Mrap\GraphCool\Definition\Job;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Enums\Result;
use PDO;
use stdClass;

class MysqlDataProviderTest extends TestCase
{

    public function testFindAllError(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $this->expectException(Error::class);
        $provider = new MysqlDataProvider();
        $provider->findNodes('a12f', 'DummyModel', ['page' => 0]);
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
            ->method('loadMany')
            ->withAnyParameters()
            ->willReturn([$node]);
        Mysql::setNodeReader($readerMock);

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
        foreach ([Result::DEFAULT, Result::WITH_TRASHED, Result::ONLY_SOFT_DELETED] as $resultType) {
            $result = $provider->findNodes('a12f', 'DummyModel', ['where' => ['column' => 'last_name', 'operator' => '=', 'value' => 'b'], 'result' => $resultType]);
            self::assertEquals($expected, $result->paginatorInfo, 'Pagination Info does not match expectation for ' . $resultType);
            self::assertEquals([$node], ($result->data)(), 'Data does not match expectation for ' . $resultType);
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

    public function testGetSum(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('fetch')
            ->withAnyParameters()
            ->willReturn((object)['sum' => 'Zander']);
        Mysql::setConnector($mock);

        $provider = new MysqlDataProvider();
        $result = $provider->getSum('a12f', 'DummyModel', 'last_name');

        self::assertEquals('Zander', $result);
    }

    public function testGetSumInt(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('fetch')
            ->withAnyParameters()
            ->willReturn((object)['sum' => 123]);
        Mysql::setConnector($mock);

        $provider = new MysqlDataProvider();
        $result = $provider->getSum('a12f', 'DummyModel', 'decimal');

        self::assertEquals(1.23, $result);
    }

    public function testGetSumFloat(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('fetch')
            ->withAnyParameters()
            ->willReturn((object)['sum' => 1.23]);
        Mysql::setConnector($mock);

        $provider = new MysqlDataProvider();
        $result = $provider->getSum('a12f', 'DummyModel', 'float');

        self::assertEquals(1.23, $result);
    }

    public function testGetSumNull(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('fetch')
            ->withAnyParameters()
            ->willReturn(null);
        Mysql::setConnector($mock);

        $provider = new MysqlDataProvider();
        $result = $provider->getSum('a12f', 'DummyModel', 'last_name');

        self::assertEquals('', $result);
    }

    public function testGetSumBoolNull(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('fetch')
            ->withAnyParameters()
            ->willReturn(null);
        Mysql::setConnector($mock);

        $provider = new MysqlDataProvider();
        $result = $provider->getSum('a12f', 'DummyModel', 'bool');

        self::assertEquals(0, $result);
    }

    public function testGetSumIntNull(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('fetch')
            ->withAnyParameters()
            ->willReturn(null);
        Mysql::setConnector($mock);

        $provider = new MysqlDataProvider();
        $result = $provider->getSum('a12f', 'DummyModel', 'time');

        self::assertEquals(0, $result);
    }

    public function testGetSumFloatNull(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('fetch')
            ->withAnyParameters()
            ->willReturn(null);
        Mysql::setConnector($mock);

        $provider = new MysqlDataProvider();
        $result = $provider->getSum('a12f', 'DummyModel', 'float');

        self::assertEquals(0, $result);
    }

    public function testGetCount(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('fetchColumn')
            ->withAnyParameters()
            ->willReturn(2);
        Mysql::setConnector($mock);

        $provider = new MysqlDataProvider();
        $result = $provider->getCount('a12f', 'DummyModel');

        self::assertEquals(2, $result);
    }


    public function testInsert(): void
    {
        $this->mockTransactions();
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $writerMock = $this->createMock(MysqlNodeWriter::class);
        $writerMock->expects($this->once())
            ->method('insert')
            ->withAnyParameters();

        $node = $this->expectLoadNode();

        Mysql::setNodeWriter($writerMock);

        $provider = new MysqlDataProvider();
        $result = $provider->insert('a12f', 'DummyModel', []);

        self::assertEquals($node, $result);
    }

    protected function mockTransactions(bool $success = true): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())
            ->method('beginTransaction');
        if ($success) {
            $pdoMock->expects($this->once())
                ->method('commit');
        } else {
            $pdoMock->expects($this->once())
                ->method('rollBack');
        }
        $connector = new MysqlConnector();
        $connector->setPdo($pdoMock);
        Mysql::setConnector($connector);
    }

    public function testUpdate(): void
    {
        //$this->mockTransactions();
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $writerMock = $this->createMock(MysqlNodeWriter::class);
        $writerMock->expects($this->once())
            ->method('update')
            ->withAnyParameters();

        $node = $this->expectLoadNode(2);

        Mysql::setNodeWriter($writerMock);

        $provider = new MysqlDataProvider();
        $result = $provider->update('a12f', 'DummyModel', ['id' => 'y5']);

        self::assertEquals($node, $result);
    }

    public function testUpdateExistsError(): void
    {
        //$this->mockTransactions(false);
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $writerMock = $this->createMock(MysqlNodeWriter::class);
        $writerMock->expects($this->never())
            ->method('update');

        $readerMock = $this->createMock(MysqlNodeReader::class);
        $readerMock->expects($this->once())
            ->method('load')
            ->withAnyParameters()
            ->willReturn(null);

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

        $this->expectLoadNode();

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->exactly(1))
            ->method('fetchColumn')
            ->withAnyParameters()
            ->willReturn(1);

        Mysql::setConnector($mock);
        Mysql::setNodeWriter($writerMock);

        $provider = new MysqlDataProvider();
        $this->expectException(Error::class);
        $provider->update('a12f', 'DummyModel', ['id' => 'y5', 'data' => ['unique' => 'something that already exists']]);
    }

    public function testUpdateNullError(): void
    {
        //$this->mockTransactions(false);
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $writerMock = $this->createMock(MysqlNodeWriter::class);
        $writerMock->expects($this->never())
            ->method('update');

        $readerMock = $this->createMock(MysqlNodeReader::class);
        $readerMock->expects($this->once())
            ->method('load');

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

        $node = $this->expectLoadNode();

        $connectorMock = $this->createMock(MysqlConnector::class);
        $connectorMock->expects($this->once())
            ->method('fetch')
            ->withAnyParameters()
            ->willReturn(null);

        Mysql::setNodeWriter($writerMock);
        Mysql::setConnector($connectorMock);

        $provider = new MysqlDataProvider();
        $result = $provider->delete('a12f', 'DummyModel', 'y5');

        self::assertEquals($node, $result);
    }

    public function testDeleteNull(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $this->mockTransactions(false);

        $readerMock = $this->createMock(MysqlNodeReader::class);
        $readerMock->expects($this->once())
            ->method('load')
            ->withAnyParameters()
            ->willReturn(null);

        Mysql::setNodeReader($readerMock);

        $provider = new MysqlDataProvider();
        $result = $provider->delete('a12f', 'DummyModel', 'y5');

        self::assertNull($result);
    }

    public function testRestore(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $writerMock = $this->createMock(MysqlNodeWriter::class);
        $writerMock->expects($this->once())
            ->method('restore')
            ->withAnyParameters();

        $node = $this->expectLoadNode();

        $connectorMock = $this->createMock(MysqlConnector::class);
        $connectorMock->expects($this->once())
            ->method('fetch')
            ->withAnyParameters()
            ->willReturn(null);

        Mysql::setNodeWriter($writerMock);
        Mysql::setConnector($connectorMock);

        $provider = new MysqlDataProvider();
        $result = $provider->restore('a12f', 'DummyModel', 'y5');

        self::assertEquals($node, $result);
    }

    public function testRestoreError(): void
    {
        $this->expectException(Error::class);
        $this->mockTransactions(false);

        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $readerMock = $this->createMock(MysqlNodeReader::class);
        $readerMock->expects($this->once())
            ->method('load')
            ->withAnyParameters()
            ->willReturn(null);

        Mysql::setNodeReader($readerMock);

        $provider = new MysqlDataProvider();
        $provider->restore('a12f', 'DummyModel', 'y5');
    }

    public function testIncrement(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('increment')
            ->withAnyParameters()
            ->willReturn(2);
        Mysql::setConnector($mock);

        $provider = new MysqlDataProvider();
        $result = $provider->increment('a12f', 'key');

        self::assertEquals(2, $result);
    }

    public function testAddJob(): void
    {
        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('execute')
            ->withAnyParameters();
        Mysql::setConnector($mock);

        $provider = new MysqlDataProvider();
        $provider->addJob('1', 'test', 'DummyModel', []);
    }

    public function testFinishJob(): void
    {
        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('execute')
            ->withAnyParameters();
        Mysql::setConnector($mock);

        $provider = new MysqlDataProvider();
        $provider->finishJob('id', []);
    }

    public function testTakeJob(): void
    {
        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('fetch')
            ->withAnyParameters()
            ->willReturn((object)['id' => 'id1', 'tenant_id' => '1', 'worker' => 'testworker', 'data' => null]);
        $mock->expects($this->once())
            ->method('execute')
            ->withAnyParameters();
        Mysql::setConnector($mock);

        $provider = new MysqlDataProvider();
        $job = $provider->takeJob();

        $expected = new Job();
        $expected->id = 'id1';
        $expected->tenantId = '1';
        $expected->worker = 'testworker';
        $expected->data = null;
        $expected->result = null;

        self::assertEquals($expected, $job);
    }

    public function testTakeJobNull(): void
    {
        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('fetch')
            ->withAnyParameters()
            ->willReturn(null);
        Mysql::setConnector($mock);

        $provider = new MysqlDataProvider();
        $job = $provider->takeJob();

        self::assertNull($job);
    }

    public function testTakeJobWithData(): void
    {
        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('fetch')
            ->withAnyParameters()
            ->willReturn((object)['id' => 'id1', 'tenant_id' => '1', 'worker' => 'testworker', 'data' => '{"test":"data"}']);
        $mock->expects($this->once())
            ->method('execute')
            ->withAnyParameters();
        Mysql::setConnector($mock);

        $provider = new MysqlDataProvider();
        $job = $provider->takeJob();

        $expected = new Job();
        $expected->id = 'id1';
        $expected->tenantId = '1';
        $expected->worker = 'testworker';
        $expected->data = ['test'=>'data'];
        $expected->result = null;

        self::assertEquals($expected, $job);
    }

    public function testGetJob(): void
    {
        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('fetch')
            ->withAnyParameters()
            ->willReturn((object)['id' => 'id1', 'tenant_id' => '1', 'worker' => 'testworker', 'data' => '{"test":"data"}', 'result' => '{"test":"data2"}']);
        Mysql::setConnector($mock);

        $provider = new MysqlDataProvider();
        $job = $provider->getJob('1', 'import', 'id1');

        $expected = (object)[
            'id' => 'id1',
            'tenant_id' => '1',
            'worker' => 'testworker',
            'data' => '{"test":"data"}',
            'result' => (object)['test'=>'data2'],
        ];
        self::assertEquals($expected, $job);
    }

    public function testGetJobNull(): void
    {
        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('fetch')
            ->withAnyParameters()
            ->willReturn(null);
        Mysql::setConnector($mock);

        $provider = new MysqlDataProvider();
        $job = $provider->getJob('1', 'import', 'id1');

        self::assertNull($job);
    }

    public function testFindJobs(): void
    {
        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('fetchAll')
            ->withAnyParameters()
            ->willReturn([(object)['id' => 'id1', 'tenant_id' => '1', 'worker' => 'testworker', 'data' => '{"test":"data"}', 'result' => '{"test":"data2"}']]);
        $mock->expects($this->once())
            ->method('fetchColumn')
            ->withAnyParameters()
            ->willReturn(1);
        Mysql::setConnector($mock);

        $provider = new MysqlDataProvider();
        $result = $provider->findJobs('1', 'import', ['where' => []]);

        self::assertEquals(1, $result->paginatorInfo->count ?? 0);
        self::assertEquals(1, $result->paginatorInfo->currentPage ?? 0);
        self::assertEquals(1, $result->paginatorInfo->firstItem ?? 0);
        self::assertEquals(1, $result->paginatorInfo->lastItem ?? 0);
        self::assertEquals(1, $result->paginatorInfo->lastPage ?? 0);
        self::assertEquals(10, $result->paginatorInfo->perPage ?? 0);
        self::assertEquals(1, $result->paginatorInfo->total ?? 0);
        self::assertFalse($result->paginatorInfo->hasMorePages ?? true);

        self::assertEquals('id1', $result->data[0]->id);
        self::assertEquals('1', $result->data[0]->tenant_id);
        self::assertEquals('testworker', $result->data[0]->worker);
        self::assertEquals('{"test":"data"}', $result->data[0]->data);
        self::assertEquals((object)['test'=>'data2'], $result->data[0]->result);
    }

    public function testFindJobsError(): void
    {
        $this->expectException(Error::class);
        $provider = new MysqlDataProvider();
        $provider->findJobs('1', 'import', ['page' => 0]);
    }

    public function testStoreFile(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $writerMock = $this->createMock(MysqlNodeWriter::class);
        $writerMock->expects($this->once())
            ->method('update')
            ->withAnyParameters();

        $node = $this->expectLoadNode(2);

        $mock = $this->createMock(MysqlConnector::class);
        $mock->expects($this->once())
            ->method('fetch')
            ->withAnyParameters()
            ->willReturn((object)['value_string' => 'old-value']);

        Mysql::setConnector($mock);
        Mysql::setNodeWriter($writerMock);

        $provider = new MysqlDataProvider();
        $result = $provider->update('a12f', 'DummyModel', [
            'id' => 'y5',
            'data' => [
                'file' => [
                    'filename' => 'test.txt',
                    'data_base64' => base64_encode('Hello World!')
                ]
            ]
        ]);
        self::assertEquals($node, $result);
    }

    public function testRetrieveFile(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $this->expectLoadNode(1, true);

        $file = (object)[
            'filename' => 'test.txt',
            'mimetype' => 'text/plain',
            'data_base64' => base64_encode('Hello World!')
        ];
        $mock = $this->createMock(SystemFileProvider::class);
        $mock->expects($this->once())
            ->method('retrieve')
            ->withAnyParameters()
            ->willReturn($file);
        File::setFileProvider($mock);

        $provider = new MysqlDataProvider();
        $result = $provider->load('a12f', 'y5');

        self::assertSame('SGVsbG8gV29ybGQh', $result->file->data_base64);
        self::assertSame('test.txt', $result->file->filename);
        self::assertSame('text/plain', $result->file->mimetype);
    }

    public function testSoftDeleteFile(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $writerMock = $this->createMock(MysqlNodeWriter::class);
        $writerMock->expects($this->once())
            ->method('delete')
            ->withAnyParameters();

        $this->expectLoadNode(1, true);

        $connectorMock = $this->createMock(MysqlConnector::class);
        $connectorMock->expects($this->once())
            ->method('fetch')
            ->withAnyParameters()
            ->willReturn((object)['value_string' => 'old-value']);

        Mysql::setNodeWriter($writerMock);
        Mysql::setConnector($connectorMock);

        $file = (object)[
            'filename' => 'test.txt',
            'mimetype' => 'text/plain',
            'data_base64' => base64_encode('Hello World!')
        ];
        $mock = $this->createMock(SystemFileProvider::class);
        $mock->expects($this->once())
            ->method('retrieve')
            ->withAnyParameters()
            ->willReturn($file);
        $mock->expects($this->once())
            ->method('softDelete')
            ->withAnyParameters();
        File::setFileProvider($mock);

        $provider = new MysqlDataProvider();
        $result = $provider->delete('a12f', 'DummyModel', 'y5');

        self::assertSame('SGVsbG8gV29ybGQh', $result->file->data_base64);
        self::assertSame('test.txt', $result->file->filename);
        self::assertSame('text/plain', $result->file->mimetype);
    }

    public function testRestoreFile(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');

        $writerMock = $this->createMock(MysqlNodeWriter::class);
        $writerMock->expects($this->once())
            ->method('restore')
            ->withAnyParameters();

        $this->expectLoadNode(1, true);

        $connectorMock = $this->createMock(MysqlConnector::class);
        $connectorMock->expects($this->once())
            ->method('fetch')
            ->withAnyParameters()
            ->willReturn((object)['value_string' => 'old-value']);

        Mysql::setNodeWriter($writerMock);
        Mysql::setConnector($connectorMock);

        $file = (object)[
            'filename' => 'test.txt',
            'mimetype' => 'text/plain',
            'data_base64' => base64_encode('Hello World!')
        ];
        $mock = $this->createMock(SystemFileProvider::class);
        $mock->expects($this->once())
            ->method('retrieve')
            ->withAnyParameters()
            ->willReturn($file);
        $mock->expects($this->once())
            ->method('restore')
            ->withAnyParameters();
        File::setFileProvider($mock);


        $provider = new MysqlDataProvider();
        $result = $provider->restore('a12f', 'DummyModel', 'y5');

        self::assertSame('SGVsbG8gV29ybGQh', $result->file->data_base64);
        self::assertSame('test.txt', $result->file->filename);
        self::assertSame('text/plain', $result->file->mimetype);
    }

    protected function expectLoadNode(int $exactly = 1, bool $includeFile = false): stdClass
    {
        $node = (object)[
            'id' => 'y5',
            'tenant_id' => 'a12f',
            'model' => 'DummyModel',
            'created_at' => '2021-08-30 12:00:00',
            'updated_at' => null,
            'deleted_at' => null
        ];
        if ($includeFile) {
            $node->file = 'test-file';
        }

        $readerMock = $this->createMock(MysqlNodeReader::class);
        $readerMock->expects($this->exactly($exactly))
            ->method('load')
            ->withAnyParameters()
            ->willReturn($node);
        Mysql::setNodeReader($readerMock);
        return $node;
    }


}