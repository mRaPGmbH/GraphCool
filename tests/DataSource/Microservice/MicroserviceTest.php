<?php

namespace Mrap\GraphCool\Tests\DataSource\Microservice;

use GraphQL\Error\Error;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Stream;
use Mrap\GraphCool\DataSource\Microservice\Microservice;
use Mrap\GraphCool\Tests\TestCase;
use RuntimeException;

class MicroserviceTest extends TestCase
{

    public function testConstruct(): void
    {
        $microservice = Microservice::endpoint('endpoint');
        self::assertInstanceOf(Microservice::class, $microservice);
    }

    public function testCall(): void
    {
        $body = $this->createMock(Stream::class);
        $body->expects($this->once())
            ->method('__toString')
            ->willReturn('{"data":{"customers":["test"]}}');

        $response = $this->createMock(Response::class);
        $response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(200);
        $response->expects($this->once())
            ->method('getBody')
            ->willReturn($body);

        $mock = $this->createMock(Client::class);
        $mock->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $microservice = Microservice::endpoint('crm:query:customers', $mock);
        $result = $microservice->call();

        self::assertEquals(['test'], $result);
    }

    public function testCall400(): void
    {
        $this->expectException(RuntimeException::class);

        $body = $this->createMock(Stream::class);
        $body->expects($this->once())
            ->method('__toString')
            ->willReturn('{"data":{"customers":["test"]}}');

        $response = $this->createMock(Response::class);
        $response->expects($this->exactly(2))
            ->method('getStatusCode')
            ->willReturn(400);
        $response->expects($this->once())
            ->method('getBody')
            ->willReturn($body);

        $mock = $this->createMock(Client::class);
        $mock->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $microservice = Microservice::endpoint('crm:query:customers', $mock);
        $microservice->call();
    }

    public function testCallError(): void
    {
        $this->expectException(Error::class);

        $body = $this->createMock(Stream::class);
        $body->expects($this->once())
            ->method('__toString')
            ->willReturn('{"errors":[{"message": "test-error"}]}');

        $response = $this->createMock(Response::class);
        $response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(200);
        $response->expects($this->once())
            ->method('getBody')
            ->willReturn($body);

        $mock = $this->createMock(Client::class);
        $mock->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $microservice = Microservice::endpoint('crm:query:customers', $mock);
        $microservice->call();
    }

    public function testRawQuery(): void
    {
        $body = $this->createMock(Stream::class);
        $body->expects($this->once())
            ->method('__toString')
            ->willReturn('{"data": ["test"]}');

        $response = $this->createMock(Response::class);
        $response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(200);
        $response->expects($this->once())
            ->method('getBody')
            ->willReturn($body);

        $mock = $this->createMock(Client::class);
        $mock->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $microservice = Microservice::endpoint('crm:query:customers', $mock);
        $result = $microservice->rawQuery('')
            ->call();

        self::assertEquals(['test'], $result);
    }

    public function testFullQuery(): void
    {
        $body = $this->createMock(Stream::class);
        $body->expects($this->once())
            ->method('__toString')
            ->willReturn('{"data": {"customers":["test"]}}');

        $response = $this->createMock(Response::class);
        $response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(200);
        $response->expects($this->once())
            ->method('getBody')
            ->willReturn($body);

        $mock = $this->createMock(Client::class);
        $mock->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $microservice = Microservice::endpoint('crm:query:customers', $mock);
        $result = $microservice
            ->authorization('asdf')
            ->paramString('string', 'value')
            ->paramString('string-null', null)
            ->paramNull('null')
            ->paramInt('int', 2)
            ->paramInt('int-null', null)
            ->paramFloat('float', 2.5)
            ->paramFloat('float-null', null)
            ->paramEnum('enum', 'value')
            ->paramEnum('enum-null', null)
            ->paramDate('date', '2022-05-19')
            ->paramDate('date-null', null)
            ->paramDateTime('datetime', '2022-05-19 00:00:00')
            ->paramDateTime('datetime-null', null)
            ->paramTime('time', '00:00:00')
            ->paramTime('time-null', null)
            ->paramBool('bool', true)
            ->paramRaw('raw: "raw"')
            ->paramRawValue('raw', '"raw"')
            ->fields(['id', 'subfield' => ['key']])
            ->addField('created_at')
            ->setTimeout(30)
            ->call();

        self::assertEquals(['test'], $result);
    }


}