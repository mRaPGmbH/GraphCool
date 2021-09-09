<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Definition;

abstract class Script
{
    /**
     * @param mixed[] $args
     */
    abstract public function run(array $args): void;
}