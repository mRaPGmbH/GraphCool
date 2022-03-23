<?php

namespace App\Scripts;

use Mrap\GraphCool\Definition\Script;

class DummyScript extends Script
{

    public function run(array $args): void
    {
        $this->log('test log');
        echo 'test';
    }
}