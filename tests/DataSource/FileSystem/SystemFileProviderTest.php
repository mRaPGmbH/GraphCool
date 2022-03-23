<?php

namespace Mrap\GraphCool\Tests\DataSource\FileSystem;

use GraphQL\Error\Error;
use Mrap\GraphCool\DataSource\FileSystem\SystemFileProvider;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Utils\ClassFinder;
use RuntimeException;

class SystemFileProviderTest extends TestCase
{

    public function tearDown(): void
    {
        @unlink($this->dataPath().'/storage/files/DummyModel/43e/0de/DummyModel.43e0deae-de62-4b9c-812d-56a80b90f1b2.file');
        @rmdir($this->dataPath().'/storage/files/DummyModel/43e/0de');
        @rmdir($this->dataPath().'/storage/files/DummyModel/43e');
        @rmdir($this->dataPath().'/storage/files/DummyModel');
        parent::tearDown();
    }

    public function testStoreBase64(): void
    {
        $provider = new SystemFileProvider();
        $name = 'DummyModel';
        $id = '43e0deae-de62-4b9c-812d-56a80b90f1b2';
        $key = 'file';
        $input = [
            'data_base64' => base64_encode('Hello World!'),
            'filename' => 'test.txt',
        ];
        $result = $provider->store($name, $id, $key, $input);

        self::assertSame('test.txt', $result->id);
        $file = $this->dataPath().'/storage/files/DummyModel/43e/0de/' . $name . '.' . $id . '.' . $key;
        self::assertFileExists($file);
        self::assertSame('Hello World!', file_get_contents($file));
    }

    public function testStoreMultipart(): void
    {
        $provider = new SystemFileProvider();
        $name = 'DummyModel';
        $id = '43e0deae-de62-4b9c-812d-56a80b90f1b2';
        $key = 'file';
        $tmp = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tmp, 'Hello World!');
        $input = [
            'file' => [
                'tmp_name' => $tmp
            ],
            'filename' => 'test.txt',
        ];
        $result = $provider->store($name, $id, $key, $input);
        unlink($tmp);

        self::assertSame('test.txt', $result->id);
        $file = $this->dataPath().'/storage/files/DummyModel/43e/0de/' . $name . '.' . $id . '.' . $key;
        self::assertFileExists($file);
        self::assertSame('Hello World!', file_get_contents($file));
    }

    public function testStoreMultipartError(): void
    {
        $this->expectException(Error::class);

        $provider = new SystemFileProvider();
        $name = 'DummyModel';
        $id = '43e0deae-de62-4b9c-812d-56a80b90f1b2';
        $key = 'file';
        $tmp = tempnam(sys_get_temp_dir(), 'test');
        unlink($tmp); // make sure file doesn't exist

        $input = [
            'file' => [
                'tmp_name' => $tmp
            ],
            'filename' => 'test.txt',
        ];
        $provider->store($name, $id, $key, $input);
    }

    public function testStoreBase64Error(): void
    {
        $this->expectException(Error::class);

        $provider = new SystemFileProvider();
        $name = 'DummyModel';
        $id = '43e0deae-de62-4b9c-812d-56a80b90f1b2';
        $key = 'file';
        $input = [
            'data_base64' => '',
            'filename' => 'test.txt',
        ];
        $provider->store($name, $id, $key, $input);
    }

    public function testStoreError(): void
    {
        $this->expectException(Error::class);

        $provider = new SystemFileProvider();
        $name = 'DummyModel';
        $id = '43e0deae-de62-4b9c-812d-56a80b90f1b2';
        $key = 'file';
        $input = [
            'filename' => 'test.txt',
        ];
        $provider->store($name, $id, $key, $input);
    }

    public function testPathError(): void
    {
        $this->expectException(RuntimeException::class);

        ClassFinder::setRootPath('/sys/test'); // not writeable

        $provider = new SystemFileProvider();
        $name = 'DummyModel';
        $id = '43e0deae-de62-4b9c-812d-56a80b90f1b2';
        $key = 'file';
        $input = [
            'data_base64' => 'Hello World!',
            'filename' => 'test.txt',
        ];
        $provider->store($name, $id, $key, $input);
    }

    public function testFileNameError(): void
    {
        $this->expectException(RuntimeException::class);

        $provider = new SystemFileProvider();
        $provider->setPath('/sys');
        $name = 'DummyModel';
        $id = '43e0deae-de62-4b9c-812d-56a80b90f1b2';
        $key = 'file';
        $input = [
            'data_base64' => 'Hello World!',
            'filename' => 'test.txt',
        ];
        $provider->store($name, $id, $key, $input);
    }


    public function testRetrieve(): void
    {
        $path = $this->dataPath().'/storage/files/DummyModel/43e/0de';
        $name = 'DummyModel';
        $id = '43e0deae-de62-4b9c-812d-56a80b90f1b2';
        $key = 'file';
        mkdir($path, 0777, true);
        file_put_contents($path . '/' . $name . '.' . $id . '.' . $key, 'Hello World!');

        $provider = new SystemFileProvider();
        $result = $provider->retrieve($name, $id, $key, 'test.txt');

        self::assertSame('test.txt', $result->filename);
        $closure = $result->mime_type;
        self::assertSame('text/plain', $closure());
        $closure = $result->data_base64;
        self::assertSame(base64_encode('Hello World!'), $closure());
    }

    public function testRetrieveMissing(): void
    {
        $path = $this->dataPath().'/storage/files/DummyModel/43e/0de';
        $name = 'DummyModel';
        $id = '43e0deae-de62-4b9c-812d-56a80b90f1b2';
        $key = 'does-not-exist';
        mkdir($path, 0777, true);

        $provider = new SystemFileProvider();
        $result = $provider->retrieve($name, $id, $key, 'test.txt');

        self::assertNull($result);
    }

    public function testDelete(): void
    {
        $path = $this->dataPath().'/storage/files/DummyModel/43e/0de';
        $name = 'DummyModel';
        $id = '43e0deae-de62-4b9c-812d-56a80b90f1b2';
        $key = 'file';
        mkdir($path, 0777, true);
        file_put_contents($path . '/' . $name . '.' . $id . '.' . $key , 'Hello World!');

        $provider = new SystemFileProvider();
        $provider->delete($name, $id, $key, 'test.txt');

        self::assertFileDoesNotExist($path . '/' . $name . '.' . $id . '.' . $key);
    }


}