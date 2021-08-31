<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Model;

abstract class Script
{
    abstract public function run(array $args): void;
}