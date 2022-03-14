<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Definition;

abstract class Script
{
    protected array $log = [];

    /**
     * @param mixed[] $args
     */
    abstract public function run(array $args): void;

    protected function log(string $msg): void
    {
        echo $msg . PHP_EOL;
        $this->log[] = $msg;
    }

    public function getLog(): array
    {
        return $this->log;
    }
}