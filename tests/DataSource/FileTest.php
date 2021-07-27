<?php


namespace Mrap\GraphCool\Tests\DataSource;


use Mrap\GraphCool\DataSource\File;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Utils\FileExport;
use Mrap\GraphCool\Utils\FileImport2;

class FileTest extends TestCase
{

    public function testWrite(): void
    {
        $mock = $this->createMock(FileExport::class);
        $expected = (object)['e'];
        $mock->expects($this->once())
            ->method('export')
            ->with('a',['b'],['c'],'d')
            ->willReturn($expected);
        File::setExporter($mock);
        $result = File::write('a',['b'],['c'],'d');
        self::assertEquals($expected, $result);
    }

    public function testRead(): void
    {
        $mock = $this->createMock(FileImport2::class);
        $expected = ['e'];
        $mock->expects($this->once())
            ->method('import')
            ->with('a',['b'],0)
            ->willReturn($expected);
        File::setImporter($mock);
        $result = File::read('a',['b'],0);
        self::assertEquals($expected, $result);
    }

}