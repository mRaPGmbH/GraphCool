<?php


namespace Mrap\GraphCool\Tests\DataSource;


use Mrap\GraphCool\DataSource\File;
use Mrap\GraphCool\DataSource\FileSystem\SystemFileProvider;
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
        self::assertSame($expected, $result);
    }

    public function testRead(): void
    {
        $mock = $this->createMock(FileImport2::class);
        $expected = ['e'];
        $mock->expects($this->once())
            ->method('import')
            ->with('a',['b'])
            ->willReturn($expected);
        File::setImporter($mock);
        $result = File::read('a',['b']);
        self::assertSame($expected, $result);
    }

    public function testStore(): void
    {
        $name = 'DummyModel';
        $id = '43e0deae-de62-4b9c-812d-56a80b90f1b2';
        $key = 'file';
        $file = [
            'filename' => 'test.txt',
            'data_base64' => base64_encode('Hello World!')
        ];
        $return = (object)[
            'id' => 'test.txt'
        ];

        $mock = $this->createMock(SystemFileProvider::class);
        $mock->expects($this->once())
            ->method('store')
            ->with($name, $id, $key, $file)
            ->willReturn($return);

        File::setFileProvider($mock);
        $result = File::store($name, $id, $key, $file);
        self::assertSame('test.txt', $result->id);
    }

    public function testRetrieve(): void
    {
        $name = 'DummyModel';
        $id = '43e0deae-de62-4b9c-812d-56a80b90f1b2';
        $key = 'file';
        $file = (object)[
            'filename' => 'test.txt',
            'mimetype' => 'text/plain',
            'data_base64' => base64_encode('Hello World!')
        ];

        $mock = $this->createMock(SystemFileProvider::class);
        $mock->expects($this->once())
            ->method('retrieve')
            ->with($name, $id, $key, 'test.txt')
            ->willReturn($file);

        File::setFileProvider($mock);
        $result = File::retrieve($name, $id, $key, 'test.txt');
        self::assertSame($file, $result);
    }

    public function testDelete(): void
    {
        $name = 'DummyModel';
        $id = '43e0deae-de62-4b9c-812d-56a80b90f1b2';
        $key = 'file';

        $mock = $this->createMock(SystemFileProvider::class);
        $mock->expects($this->once())
            ->method('delete')
            ->with($name, $id, $key);

        File::setFileProvider($mock);
        File::delete($name, $id, $key, 'test.txt');
    }

    public function testSoftDelete(): void
    {
        $name = 'DummyModel';
        $id = '43e0deae-de62-4b9c-812d-56a80b90f1b2';
        $key = 'file';

        $mock = $this->createMock(SystemFileProvider::class);
        $mock->expects($this->once())
            ->method('softDelete')
            ->with($name, $id, $key);

        File::setFileProvider($mock);
        File::softDelete($name, $id, $key, 'test.txt');
    }

    public function testRestore(): void
    {
        $name = 'DummyModel';
        $id = '43e0deae-de62-4b9c-812d-56a80b90f1b2';
        $key = 'file';

        $mock = $this->createMock(SystemFileProvider::class);
        $mock->expects($this->once())
            ->method('restore')
            ->with($name, $id, $key);

        File::setFileProvider($mock);
        File::restore($name, $id, $key, 'test.txt');
    }

}
