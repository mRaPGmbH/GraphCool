<?php

declare(strict_types=1);

namespace Mrap\GraphCool\DataSource;

use stdClass;

interface FileProvider
{
    public function store(string $key, array $input): ?string;
    public function retrieve(string $key, string $value): stdClass;
    public function delete(string $key): void;
}