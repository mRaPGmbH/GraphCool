<?php


namespace Mrap\GraphCool\Tests;


use Mrap\GraphCool\DataSource\DB;
use Mrap\GraphCool\DataSource\Mysql\MysqlDataProvider;
use Mrap\GraphCool\GraphCool;
use Mrap\GraphCool\Utils\ClassFinder;

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

    public function testDebugFlags(): void
    {
        $backup = getenv('APP_ENV');
        putenv('APP_ENV=production');
        $this->expectOutputRegex('/Syntax Error: Unexpected \<EOF\>/i');
        GraphCool::run();
        putenv('APP_ENV='.$backup);
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
        $this->expectOutputString('test');
        $result = GraphCool::runScript(['DummyScript']);
        self::assertEquals(true, $result['success']);
        self::assertEquals(null, $result['error'] ?? null);
    }


    public function testRunScriptError(): void
    {
        $this->expectException(\RuntimeException::class);
        GraphCool::runScript(['does-not-exist']);
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

    public function xtestFileUpload(): void
    {
        $_REQUEST['map'] = '';

        unset($_REQUEST['map']);
    }

    public function testFileUploadJsonError(): void
    {
        $_REQUEST['map'] = '{not-a-valid-json:(';
        $this->expectOutputRegex('/file map/i');
        $_POST['operations'] = '{"query":"query{DummyQuery(arg:\"asdf\")}"}';
        GraphCool::run();
        unset($_REQUEST['map']);
    }


}