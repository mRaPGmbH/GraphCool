<?php

declare(strict_types=1);

namespace Mrap\GraphCool\DataSource;

use stdClass;

interface FileProvider
{
    public function store(string $name, string $id, string $key, array $input): stdClass;
    public function retrieve(string $name, string $id, string $key, string $value): ?stdClass;
    public function delete(string $name, string $id, string $key, string $value): void;
}