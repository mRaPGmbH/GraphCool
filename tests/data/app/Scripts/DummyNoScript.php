<?php

namespace App\Scripts;

// does not extend Script class!
class DummyNoScript
{

    public function run(array $args): void
    {
        echo 'test';
    }
}