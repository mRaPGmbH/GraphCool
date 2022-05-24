<?php


namespace Mrap\GraphCool\Tests\Utils;


use GraphQL\Error\Error;
use Mrap\GraphCool\DataSource\DB;
use Mrap\GraphCool\DataSource\File;
use Mrap\GraphCool\DataSource\FileProvider;
use Mrap\GraphCool\DataSource\FileSystem\SystemFileProvider;
use Mrap\GraphCool\DataSource\Mysql\MysqlDataProvider;
use Mrap\GraphCool\Definition\Job;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Utils\Authorization;
use Mrap\GraphCool\Utils\Exporter;
use Mrap\GraphCool\Utils\FileExport;

class ExporterTest extends TestCase
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

        $dbMock = $this->createMock(MysqlDataProvider::class);
        $dbMock->expects($this->once())
            ->method('findAll')
            ->withAnyParameters()
            ->willReturn((object)[]);
        DB::setProvider($dbMock);

        $exporterMock = $this->createMock(FileExport::class);
        $exporterMock->expects($this->once())
            ->method('export')
            ->withAnyParameters()
            ->willReturn((object)[]);
        File::setExporter($exporterMock);

        $providerMock = $this->createMock(SystemFileProvider::class);
        $providerMock->expects($this->once())
            ->method('store')
            ->withAnyParameters()
            ->willReturn((object)[
                'filename' => 'test.csv',
                'mime_type' => 'text/csv',
                'url' => 'https://test.com',
                'filesize' => 1234
            ]);
        File::setFileProvider($providerMock);

        $exporter = new Exporter();
        $result = $exporter->run($job);

        $expected = [
            'success' => true,
            'filename' => 'test.csv',
            'mime_type' => 'text/csv',
            'url' => 'https://test.com',
            'filesize' => 1234
        ];

        $this->assertEquals($expected, $result);
    }

}
