<?php

namespace Mrap\GraphCool\Tests\DataSource\Aws;


use Aws\S3\S3Client;
use GraphQL\Error\Error;
use GuzzleHttp\Psr7\Stream;
use Mrap\GraphCool\DataSource\Aws\AwsFileProvider;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Utils\Env;

class AwsFileProviderTest extends TestCase
{
    public function testStoreBase64(): void
    {
        $name = 'DummyModel';
        $id = '43e0deae-de62-4b9c-812d-56a80b90f1b2';
        $key = 'file';
        $data = base64_encode('Hello World!');

        $mock = $this->getMockBuilder(S3Client::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->addMethods(['putObject'])
            ->getMock();
        $mock->expects($this->once())
            ->method('putObject')
            ->with([
                'Bucket' => Env::get('AWS_BUCKET_NAME') ?? 'GraphCool-Uploaded-Files',
                'Key' => $name . '.' . $id . '.' . $key,
                'Body' => $data
            ])
            ->willReturn([]);

        $provider = new AwsFileProvider();
        $provider->setClient($mock);
        $input = [
            'data_base64' => $data,
            'filename' => 'test.txt',
        ];
        $result = $provider->store($name, $id, $key, $input);

        self::assertSame('test.txt', $result->id);
    }

    public function testStoreMultipart(): void
    {
        $name = 'DummyModel';
        $id = '43e0deae-de62-4b9c-812d-56a80b90f1b2';
        $key = 'file';
        $data = base64_encode('Hello World!');
        $tmp = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tmp, 'Hello World!');

        $mock = $this->getMockBuilder(S3Client::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->addMethods(['putObject'])
            ->getMock();
        $mock->expects($this->once())
            ->method('putObject')
            ->with([
                'Bucket' => Env::get('AWS_BUCKET_NAME') ?? 'GraphCool-Uploaded-Files',
                'Key' => $name . '.' . $id . '.' . $key,
                'Body' => $data
            ])
            ->willReturn([]);

        $input = [
            'file' => [
                'tmp_name' => $tmp
            ],
            'filename' => 'test.txt',
        ];
        $provider = new AwsFileProvider();
        $provider->setClient($mock);
        $result = $provider->store($name, $id, $key, $input);
        unlink($tmp);

        self::assertSame('test.txt', $result->id);
    }

    public function testStoreMultipartError(): void
    {
        $this->expectException(Error::class);

        $name = 'DummyModel';
        $id = '43e0deae-de62-4b9c-812d-56a80b90f1b2';
        $key = 'file';
        $tmp = tempnam(sys_get_temp_dir(), 'test');
        @unlink($tmp); // make sure file doesn't exist

        $mock = $this->getMockBuilder(S3Client::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->addMethods(['putObject'])
            ->getMock();
        $mock->expects($this->never())
            ->method('putObject');

        $input = [
            'file' => [
                'tmp_name' => $tmp
            ],
            'filename' => 'test.txt',
        ];
        $provider = new AwsFileProvider();
        $provider->setClient($mock);
        $provider->store($name, $id, $key, $input);
    }

    public function testRetrieve(): void
    {
        $name = 'DummyModel';
        $id = '43e0deae-de62-4b9c-812d-56a80b90f1b2';
        $key = 'file';
        $data = base64_encode('Hello World!');

        $mock2 = $this->createMock(Stream::class);
        $mock2->expects($this->once())
            ->method('getContents')
            ->willReturn($data);

        $mock = $this->getMockBuilder(S3Client::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->addMethods(['getObject'])
            ->getMock();
        $mock->expects($this->once())
            ->method('getObject')
            ->with([
                'Bucket' => Env::get('AWS_BUCKET_NAME') ?? 'GraphCool-Uploaded-Files',
                'Key' => $name . '.' . $id . '.' . $key,
            ])
            ->willReturn([
                'Body' => $mock2
            ]);

        $provider = new AwsFileProvider();
        $provider->setClient($mock);
        $result = $provider->retrieve($name, $id, $key, 'test.txt');

        self::assertSame('test.txt', $result->filename);
        $closure = $result->mime_type;
        self::assertSame('text/plain', $closure());
        $closure = $result->data_base64;
        self::assertSame(base64_encode('Hello World!'), $closure());
    }

    public function testDelete(): void
    {
        $name = 'DummyModel';
        $id = '43e0deae-de62-4b9c-812d-56a80b90f1b2';
        $key = 'file';

        $mock = $this->getMockBuilder(S3Client::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->addMethods(['deleteObject'])
            ->getMock();
        $mock->expects($this->once())
            ->method('deleteObject')
            ->with([
                'Bucket' => Env::get('AWS_BUCKET_NAME') ?? 'GraphCool-Uploaded-Files',
                'Key' => $name . '.' . $id . '.' . $key,
            ]);

        $provider = new AwsFileProvider();
        $provider->setClient($mock);
        $provider->delete($name, $id, $key, 'test.txt');
    }

    public function testGetToken(): void
    {
        $provider = new AwsFileProvider();
        $token = $provider->getToken();
        self::assertEquals('', $token);
    }

}