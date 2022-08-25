<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Tests\Utils;

use Mrap\GraphCool\DataSource\DataProvider;
use Mrap\GraphCool\DataSource\DB;
use Mrap\GraphCool\DataSource\Mysql\MysqlDataProvider;
use Mrap\GraphCool\Definition\Job;
use Mrap\GraphCool\Utils\Config;
use Mrap\GraphCool\Utils\ConfigProvider;
use RuntimeException;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Utils\Scheduler;

class SchedulerTest extends TestCase
{

    public function testConstructor(): void
    {
        $scheduler = new Scheduler();
        self::assertInstanceOf(Scheduler::class, $scheduler);
    }

    public function testConstructorExceptionAlways(): void
    {
        $configProvider = $this->createMock(ConfigProvider::class);
        $configProvider->expects($this->once())
            ->method('get')
            ->withAnyParameters()
            ->willReturn(['always' => 'not_an_array']);
        Config::setProvider($configProvider);
        $this->expectException(RuntimeException::class);
        new Scheduler();
    }

    public function testConstructorExceptionHourly(): void
    {
        $configProvider = $this->createMock(ConfigProvider::class);
        $configProvider->expects($this->once())
            ->method('get')
            ->withAnyParameters()
            ->willReturn(['hourly' => 'not_an_array']);
        Config::setProvider($configProvider);
        $this->expectException(RuntimeException::class);
        new Scheduler();
    }

    public function testConstructorExceptionDaily(): void
    {
        $configProvider = $this->createMock(ConfigProvider::class);
        $configProvider->expects($this->once())
            ->method('get')
            ->withAnyParameters()
            ->willReturn(['daily' => 'not_an_array']);
        Config::setProvider($configProvider);
        $this->expectException(RuntimeException::class);
        new Scheduler();
    }

    public function testConstructorExceptionWeekly(): void
    {
        $configProvider = $this->createMock(ConfigProvider::class);
        $configProvider->expects($this->once())
            ->method('get')
            ->withAnyParameters()
            ->willReturn(['weekly' => 'not_an_array']);
        Config::setProvider($configProvider);
        $this->expectException(RuntimeException::class);
        new Scheduler();
    }

    public function testScripts(): void
    {
        $configProvider = $this->createMock(ConfigProvider::class);
        $configProvider->expects($this->once())
            ->method('get')
            ->withAnyParameters()
            ->willReturn(['always' => ['DummyScript']]);
        Config::setProvider($configProvider);
        $scheduler = new Scheduler();
        $this->expectOutputString('test log' . PHP_EOL . 'test');
        $result = $scheduler->run();
        self::assertEquals(['success'=>true], $result);
    }

    public function testScriptException(): void
    {
        $configProvider = $this->createMock(ConfigProvider::class);
        $configProvider->expects($this->once())
            ->method('get')
            ->withAnyParameters()
            ->willReturn(['always' => ['script-does-not-exist']]);
        Config::setProvider($configProvider);
        $scheduler = new Scheduler();
        $result = $scheduler->run();
        self::assertEquals(['success'=>true], $result);
    }

    public function testJob(): void
    {
        $job = new Job();
        $job->id = 'asdf123';
        $job->tenantId = '1';
        $job->worker = 'DummyScript';

        $dataProvider = $this->createMock(MysqlDataProvider::class);
        $dataProvider->expects($this->exactly(2))
            ->method('takeJob')
            ->with()
            ->willReturnOnConsecutiveCalls($job, null);
        $dataProvider->expects($this->once())
            ->method('finishJob')
            ->with('asdf123', ['success'=>true, 'log'=>['test log']], false);
        DB::setProvider($dataProvider);
        $scheduler = new Scheduler();
        $this->expectOutputString('running job asdf123 with worker DummyScript...test log' . PHP_EOL . 'test DONE' . PHP_EOL);
        $result = $scheduler->run();
        self::assertEquals(['success'=>true], $result);
    }

    public function testJobException(): void
    {
        $job = new Job();
        $job->id = 'asdf123';
        $job->tenantId = '1';
        $job->worker = 'does-not-exist';

        $dataProvider = $this->createMock(MysqlDataProvider::class);
        $dataProvider->expects($this->exactly(2))
            ->method('takeJob')
            ->with()
            ->willReturnOnConsecutiveCalls($job, null);
        $dataProvider->expects($this->once())
            ->method('finishJob')
            ->with('asdf123', ['success'=>false, 'error' => 'Script does-not-exist not found.'], true);
        DB::setProvider($dataProvider);
        $scheduler = new Scheduler();
        $this->expectOutputString('running job asdf123 with worker does-not-exist... DONE' . PHP_EOL);
        $result = $scheduler->run();
        self::assertEquals(['success'=>true], $result);
    }

}
