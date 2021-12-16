<?php

declare(strict_types=1);

namespace Mrap\GraphCool\DataSource;

use stdClass;

interface FullTextIndexProvider
{
    public function index(string $model, stdClass $data): void;
    public function shutdown(): void;
}