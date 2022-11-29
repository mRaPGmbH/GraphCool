<?php

declare(strict_types=1);

namespace Mrap\GraphCool\DataSource;

interface FullTextIndexProvider
{
    public function index(string $tenantId, string $model, string $id): void;
    public function delete(string $tenantId, string $model, string $id): void;
    public function shutdown(): void;
    public function search(string $tenantId, string $searchString): array;
    public function rebuildIndex(): void;

}
