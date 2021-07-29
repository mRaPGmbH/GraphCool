<?php


namespace Mrap\GraphCool\Tests\Utils;


use Mrap\GraphCool\Utils\ClassFinder;
use Mrap\GraphCool\Tests\TestCase;

class ClassFinderTest extends TestCase
{

    public function testRootPath(): void
    {
        ClassFinder::setRootPath(null);
        $path = ClassFinder::rootPath();
        self::assertSame(dirname(__DIR__, 2) . '/vendor/bin', $path, 'Path 1 is wrong');

        ClassFinder::setRootPath(null);
        $backup = $_SERVER['SCRIPT_FILENAME'];
        $_SERVER['SCRIPT_FILENAME'] = '.';
        self::assertSame(dirname(__DIR__, 2), ClassFinder::rootPath(), 'Path 2 is wrong');
        $_SERVER['SCRIPT_FILENAME'] = $backup;

        ClassFinder::setRootPath($this->dataPath());
        self::assertSame($this->dataPath(), ClassFinder::rootPath(), 'Path 3 is wrong');
    }

    public function testModels()
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        ClassFinder::setRootPath($this->dataPath());
        $models = ClassFinder::models();
        self::assertArrayHasKey('DummyModel', $models);
        self::assertSame('App\\Models\\DummyModel', $models['DummyModel']);
    }

    public function testQueries()
    {
        require_once($this->dataPath().'/app/Queries/DummyQuery.php');
        ClassFinder::setRootPath($this->dataPath());
        $queries = ClassFinder::queries();
        self::assertArrayHasKey('DummyQuery', $queries);
        self::assertSame('App\\Queries\\DummyQuery', $queries['DummyQuery']);
    }

    public function testMutations()
    {
        require_once($this->dataPath().'/app/Mutations/DummyMutation.php');
        ClassFinder::setRootPath($this->dataPath());
        $mutations = ClassFinder::mutations();
        self::assertArrayHasKey('DummyMutation', $mutations);
        self::assertSame('App\\Mutations\\DummyMutation', $mutations['DummyMutation']);
    }

    public function testScripts()
    {
        require_once($this->dataPath().'/app/Scripts/DummyScript.php');
        ClassFinder::setRootPath($this->dataPath());
        $scripts = ClassFinder::scripts();
        self::assertArrayHasKey('DummyScript', $scripts);
        self::assertSame('App\\Scripts\\DummyScript', $scripts['DummyScript']);
    }

}