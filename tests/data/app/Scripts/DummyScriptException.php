<?php

namespace App\Scripts;

use Mrap\GraphCool\Model\Script;

class DummyScriptException extends Script
{

    public function run(array $args): void
    {
        throw new \RuntimeException('nope');
    }
}