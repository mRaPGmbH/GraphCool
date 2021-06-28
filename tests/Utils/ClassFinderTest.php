<?php


namespace Mrap\GraphCool\Tests\Utils;


use Mrap\GraphCool\Utils\ClassFinder;
use Mrap\GraphCool\Tests\TestCase;

class ClassFinderTest extends TestCase
{

    public function testRootPath(): void
    {
        $path = ClassFinder::rootPath();
        self::assertEquals(dirname(__DIR__, 2) . '/vendor/bin', $path);

        $backup = $_SERVER['SCRIPT_FILENAME'];
        $_SERVER['SCRIPT_FILENAME'] = '.';
        self::assertEquals(dirname(__DIR__, 2), ClassFinder::rootPath());
        $_SERVER['SCRIPT_FILENAME'] = $backup;

        self::assertEquals($this->dataPath(), ClassFinder::rootPath());
    }

    public function testModels()
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $models = ClassFinder::models();
        self::assertArrayHasKey('DummyModel', $models);
        self::assertEquals('App\\Models\\DummyModel', $models['DummyModel']);
    }

    public function testQueries()
    {
        require_once($this->dataPath().'/app/Queries/DummyQuery.php');
        $queries = ClassFinder::queries();
        self::assertArrayHasKey('DummyQuery', $queries);
        self::assertEquals('App\\Queries\\DummyQuery', $queries['DummyQuery']);
    }

    public function testMutations()
    {
        require_once($this->dataPath().'/app/Mutations/DummyMutation.php');
        $mutations = ClassFinder::mutations();
        self::assertArrayHasKey('DummyMutation', $mutations);
        self::assertEquals('App\\Mutations\\DummyMutation', $mutations['DummyMutation']);
    }

    public function testScripts()
    {
        require_once($this->dataPath().'/app/Scripts/DummyScript.php');
        $scripts = ClassFinder::scripts();
        self::assertArrayHasKey('DummyScript', $scripts);
        self::assertEquals('App\\Scripts\\DummyScript', $scripts['DummyScript']);
    }

}