<?php

namespace Mrap\GraphCool\Tests\DataSource\Microservice;

use Mrap\GraphCool\DataSource\Microservice\Microservice;
use Mrap\GraphCool\DataSource\Microservice\MicroserviceFileProvider;
use Mrap\GraphCool\Tests\TestCase;
use Ramsey\Uuid\Uuid;
use stdClass;

class MicroserviceFileProviderTest extends TestCase
{

    public function testStore(): void
    {
        $this->provideJwt();
        $this->injectMicroserviceMock((object)[
            'id' => 'file-id-1',
            'filesize' => 1234,
            'file' => (object)['url' => 'http://localhost/file/1']
        ]);

        $provider = new MicroserviceFileProvider();
        $input = [
            'data_base64' => base64_encode('Hello World!'),
            'filename' => 'test.txt',
        ];
        $result = $provider->store('DummyModel', '43e0deae-de62-4b9c-812d-56a80b90f1b2', 'file', $input);

        self::assertSame('test.txt', $result->filename);
        self::assertSame(1234, $result->filesize);
        self::assertSame('text/plain', $result->mime_type);
        self::assertSame('SGVsbG8gV29ybGQh', $result->data_base64);
        self::assertSame('file-id-1', $result->id);
        self::assertSame('http://localhost/file/1', $result->url);
    }

    public function testRetrieve(): void
    {
        $this->provideJwt();
        $this->injectMicroserviceMock((object)[
            'id' => 'file-id-2',
            'filesize' => 1234,
            'file' => ['url' => 'http://localhost/file/1'],
            'filename' => 'test.txt',
            'mime_type' => 'text/plain'
        ]);
        $provider = new MicroserviceFileProvider();
        $result = $provider->retrieve('DummyModel', '00ee6f23-6aba-4d69-9c2d-119823303e73', 'file', 'file-id-2');

        self::assertSame('test.txt', ($result->filename)());
    }

    protected function injectMicroserviceMock(mixed $return): void
    {
        $mock = $this->createMock(Microservice::class);
        $mock->method('setTimeout')
            ->willReturn($mock);
        $mock->method('authorization')
            ->willReturn($mock);
        $mock->method('paramString')
            ->willReturn($mock);
        $mock->method('paramRawValue')
            ->willReturn($mock);
        $mock->method('fields')
            ->willReturn($mock);
        $mock->method('paramEnum')
            ->willReturn($mock);
        $mock->expects($this->once())
            ->method('call')
            ->willReturn($return);
        Microservice::inject($mock);
    }

    public function testDelete(): void
    {
        $this->provideJwt();
        $this->injectMicroserviceMock((object)[]);
        $provider = new MicroserviceFileProvider();
        $provider->delete('DummyModel', '00ee6f23-6aba-4d69-9c2d-119823303e73', 'file', 'file-id-3');
    }

    public function testDeleteEmpty(): void
    {
        $this->provideJwt();
        $mock = $this->createMock(Microservice::class);
        $mock->expects($this->never())
            ->method('call');
        Microservice::inject($mock);
        $provider = new MicroserviceFileProvider();
        $provider->delete('DummyModel', '00ee6f23-6aba-4d69-9c2d-119823303e73', 'file', '');
    }

    public function testSoftDelete(): void
    {
        $this->provideJwt();
        $this->injectMicroserviceMock((object)[]);
        $provider = new MicroserviceFileProvider();
        $provider->softDelete('DummyModel', '00ee6f23-6aba-4d69-9c2d-119823303e73', 'file', 'file-id-3');
    }

    public function testRestore(): void
    {
        $this->provideJwt();
        $this->injectMicroserviceMock((object)[]);
        $provider = new MicroserviceFileProvider();
        $provider->restore('DummyModel', '00ee6f23-6aba-4d69-9c2d-119823303e73', 'file', 'file-id-3');
    }

    public function testRestoreEmpty(): void
    {
        $this->provideJwt();
        $mock = $this->createMock(Microservice::class);
        $mock->expects($this->never())
            ->method('call');
        Microservice::inject($mock);
        $provider = new MicroserviceFileProvider();
        $provider->restore('DummyModel', '00ee6f23-6aba-4d69-9c2d-119823303e73', 'file', '');
    }

    public function testGetToken(): void
    {
        $expected = 'asdf';
        $this->provideJwt();
        $this->injectMicroserviceMock($expected);
        $provider = new MicroserviceFileProvider();
        $result = $provider->getToken();
        self::assertSame($expected, $result);
    }


}