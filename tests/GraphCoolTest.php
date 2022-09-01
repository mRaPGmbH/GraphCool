<?php


namespace Mrap\GraphCool\Tests;


use Mrap\GraphCool\DataSource\DB;
use Mrap\GraphCool\DataSource\Mysql\MysqlDataProvider;
use Mrap\GraphCool\Definition\Job;
use Mrap\GraphCool\GraphCool;
use Mrap\GraphCool\Utils\ClassFinder;
use Mrap\GraphCool\Utils\Exporter;
use Mrap\GraphCool\Utils\Importer;
use Mrap\GraphCool\Utils\Scheduler;
use RuntimeException;

class GraphCoolTest extends TestCase
{

    public function testRun(): void
    {
        $this->provideJwt();
        require_once($this->dataPath().'/app/Queries/DummyQuery.php');
        ClassFinder::setRootPath($this->dataPath());

        $this->expectOutputRegex('/asdf/i');
        $_POST['operations'] = '{"query":"query{DummyQuery(arg:\"asdf\")}"}';
        GraphCool::run();
    }

    public function testRunQueryException(): void
    {
        $this->provideJwt();
        require_once($this->dataPath().'/app/Queries/ExceptionQuery.php');
        ClassFinder::setRootPath($this->dataPath());

        $this->expectOutputRegex('/Internal server error/i');
        $_POST['operations'] = '{"query":"query{ExceptionQuery}"}';
        GraphCool::run();
    }

    public function testRunQueryJsonException(): void
    {
        $this->provideJwt();
        require_once($this->dataPath().'/app/Queries/InvalidJsonQuery.php');
        ClassFinder::setRootPath($this->dataPath());

        $this->expectOutputRegex('/Internal server error/i');
        $_POST['operations'] = '{"query":"query{InvalidJsonQuery}"}';
        GraphCool::run();
    }

    public function testRunQueryError(): void
    {
        $this->provideJwt();
        require_once($this->dataPath().'/app/Queries/ErrorQuery.php');
        ClassFinder::setRootPath($this->dataPath());

        $this->expectOutputRegex('/nada/i');
        $_POST['operations'] = '{"query":"query{ErrorQuery}"}';
        GraphCool::run();
    }


    public function testRunError(): void
    {
        unset($_POST['operations']);
        $this->expectOutputRegex('/Syntax Error: Unexpected \<EOF\>/i');
        GraphCool::run();
    }

    public function testRunJsonError(): void
    {
        $this->expectOutputRegex('/Syntax Error: Unexpected \<EOF\>/i');
        $_POST['operations'] = '[not-a-valid-json';
        GraphCool::run();
    }

    public function testRunEmptyError(): void
    {
        $this->expectOutputRegex('/Syntax Error: Unexpected \<EOF\>/i');
        $_POST['operations'] = '';
        GraphCool::run();
    }

    public function xtestDebugFlags(): void
    {
        $backup = getenv('APP_ENV');
        putenv('APP_ENV=production');
        $this->expectOutputRegex('/Syntax Error: Unexpected \<EOF\>/i');
        GraphCool::run();
        putenv('APP_ENV='.$backup);
        // this sets APP_ENV to empty string - but $backup is actually false, since the variable does not exist.
    }

    public function testShutDown(): void
    {
        $this->expectOutputRegex('/Syntax Error: Unexpected \<EOF\>/i');
        $this->expectOutputRegex('/testRegex/');
        $closure = static function(): void {
            echo 'testRegex';
        };
        GraphCool::onShutdown($closure);
        GraphCool::run();
    }

    public function testMigrate(): void
    {
        $mock = $this->createMock(MysqlDataProvider::class);
        $mock->expects($this->once())
            ->method('migrate');
        DB::setProvider($mock);
        GraphCool::migrate();
    }

    public function testRunScript(): void
    {
        require_once($this->dataPath().'/app/Scripts/DummyScript.php');
        ClassFinder::setRootPath($this->dataPath());
        $this->expectOutputString('test log' . PHP_EOL . 'test');
        $result = GraphCool::runScript(['DummyScript']);
        self::assertEquals(true, $result['success']);
        self::assertEquals(null, $result['error'] ?? null);
    }


    public function testRunScriptError(): void
    {
        $result = GraphCool::runScript(['does-not-exist']);
        self::assertFalse($result['success']);
        self::assertEquals('Script does-not-exist not found.', $result['error']);
    }

    public function testRunScriptException(): void
    {
        require_once($this->dataPath().'/app/Scripts/DummyScriptException.php');
        ClassFinder::setRootPath($this->dataPath());
        $result = GraphCool::runScript(['DummyScriptException']);
        self::assertEquals(false, $result['success']);
        self::assertEquals('nope', $result['error']);
    }

    public function testRunNoScript(): void
    {
        require_once($this->dataPath().'/app/Scripts/DummyNoScript.php');
        ClassFinder::setRootPath($this->dataPath());
        $result = GraphCool::runScript(['DummyNoScript']);
        self::assertFalse($result['success']);
        self::assertArrayHasKey('error', $result);
    }

    public function testFileUploadJsonError(): void
    {
        $_REQUEST['map'] = '{not-a-valid-json:(';
        $this->expectOutputRegex('/file map/i');
        $_POST['operations'] = '{"query":"query{DummyQuery(arg:\"asdf\")}"}';
        GraphCool::run();
        unset($_REQUEST['map']);
    }

    public function testRunScheduler(): void
    {
        $result = GraphCool::runScript(['scheduler']);
        self::assertTrue($result['success']);
    }

    public function testRunScriptSchedulerException(): void
    {
        $scheduler = $this->createMock(Scheduler::class);
        $scheduler->expects($this->once())
            ->method('run')
            ->willThrowException(new RuntimeException('test message'));
        GraphCool::setScheduler($scheduler);
        $result = GraphCool::runScript(['scheduler']);
        self::assertFalse($result['success']);
        self::assertEquals('test message', $result['error']);
    }

    public function testRunScriptImporterEmptyJob(): void
    {
        $result = GraphCool::runScript(['importer', $this->createMock(Job::class)]);
        self::assertFalse($result['success']);
        self::assertEquals('Typed property Mrap\GraphCool\Definition\Job::$data must not be accessed before initialization', $result['error']);
    }

    public function testRunScriptImporter(): void
    {
        $mock = $this->createMock(Importer::class);
        $mock->expects($this->once())
            ->method('run')
            ->withAnyParameters()
            ->willReturn([]);
        GraphCool::setImporter($mock);
        $result = GraphCool::runScript(['importer', $this->createMock(Job::class)]);
        self::assertEquals([], $result);
    }

    public function testRunScriptImporterException(): void
    {
        $mock = $this->createMock(Importer::class);
        $mock->expects($this->once())
            ->method('run')
            ->withAnyParameters()
            ->willThrowException(new RuntimeException('test message'));
        GraphCool::setImporter($mock);
        $result = GraphCool::runScript(['importer', $this->createMock(Job::class)]);
        self::assertFalse($result['success']);
        self::assertEquals('test message', $result['error']);
    }

    public function testRunScriptExporterEmptyJob(): void
    {
        $result = GraphCool::runScript(['exporter', $this->createMock(Job::class)]);
        self::assertFalse($result['success']);
        self::assertEquals('Typed property Mrap\GraphCool\Definition\Job::$data must not be accessed before initialization', $result['error']);
    }

    public function testRunScriptExporter(): void
    {
        $mock = $this->createMock(Exporter::class);
        $mock->expects($this->once())
            ->method('run')
            ->withAnyParameters()
            ->willReturn([]);
        GraphCool::setExporter($mock);
        $result = GraphCool::runScript(['exporter', $this->createMock(Job::class)]);
        self::assertEquals([], $result);
    }

    public function testRunScriptExporterException(): void
    {
        $mock = $this->createMock(Exporter::class);
        $mock->expects($this->once())
            ->method('run')
            ->withAnyParameters()
            ->willThrowException(new RuntimeException('test message'));
        GraphCool::setExporter($mock);
        $result = GraphCool::runScript(['exporter', $this->createMock(Job::class)]);
        self::assertFalse($result['success']);
        self::assertEquals('test message', $result['error']);
    }

}
