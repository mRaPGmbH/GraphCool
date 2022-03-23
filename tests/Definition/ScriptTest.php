<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Tests\Model;

use App\Scripts\DummyScript;
use Mrap\GraphCool\Tests\TestCase;

class ScriptTest extends TestCase
{
    public function testLog(): void
    {
        require_once($this->dataPath().'/app/Scripts/DummyScript.php');
        $script = new DummyScript();
        $this->expectOutputString('test log' . PHP_EOL . 'test');
        $script->run([]);
        $log = $script->getLog();
        self::assertEquals(['test log'], $log);
    }
}