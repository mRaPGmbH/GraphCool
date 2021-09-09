<?php

namespace App\Scripts;

use Mrap\GraphCool\Definition\Script;

class DummyScriptException extends Script
{

    public function run(array $args): void
    {
        throw new \RuntimeException('nope');
    }
}