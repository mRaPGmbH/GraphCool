<?php


namespace Mrap\GraphCool\Tests\Utils;


use Mrap\GraphCool\DataSource\DB;
use Mrap\GraphCool\DataSource\File;
use Mrap\GraphCool\DataSource\FullTextIndex;
use Mrap\GraphCool\DataSource\FullTextIndexProvider;
use Mrap\GraphCool\DataSource\Mysql\MysqlDataProvider;
use Mrap\GraphCool\Definition\Job;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Utils\FileImport2;
use Mrap\GraphCool\Utils\Importer;

class ImporterTest extends TestCase
{
    public function testRun():void
    {
        $job = $this->createMock(Job::class);
        $job->tenantId = '1';
        $job->data = [
            'name' => 'test',
            'args' => [],
            'jwt' => 'test-jwt'
        ];

        $importerMock = $this->createMock(FileImport2::class);
        $importerMock->expects($this->once())
            ->method('import')
            ->withAnyParameters()
            ->willReturn([[['create']], [['update']], []]);
        File::setImporter($importerMock);

        $dbMock = $this->createMock(MysqlDataProvider::class);
        $dbMock->expects($this->once())
            ->method('insert')
            ->withAnyParameters()
            ->willReturn((object)['id' => '1']);
        $dbMock->expects($this->once())
            ->method('update')
            ->withAnyParameters()
            ->willReturn((object)['id' => '2']);
        DB::setProvider($dbMock);

        $providerMock = $this->createMock(FullTextIndexProvider::class);
        $providerMock->expects($this->exactly(2))
            ->method('index')
            ->withAnyParameters();
        FullTextIndex::setProvider($providerMock);

        $importer = new Importer();
        $result = $importer->run($job);

        $expected = [
            'success' => true,
            'inserted_rows' => 1,
            'inserted_ids' => ['1'],
            'updated_rows' => 1,
            'updated_ids' => ['2'],
            'affected_rows' => 2,
            'affected_ids' => ['1','2'],
            'failed_rows' => 0,
            'failed_row_numbers' => [],
            'errors' => [],
        ];
        $this->assertEquals($expected, $result);
    }

}
