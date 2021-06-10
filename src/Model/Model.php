<?php
declare(strict_types=1);

namespace Mrap\GraphCool\Model;


use stdClass;

class Model
{

    public function __construct()
    {
        $this->id = Field::id();
        $this->created_at = Field::createdAt();
        $this->updated_at = Field::updatedAt();
        $this->deleted_at = Field::deletedAt();
    }

    public function beforeInsert(string $tenantId, array $data): array
    {
        return $data;
    }

    public function beforeUpdate(string $tenantId, string $id, array $updates): array
    {
        return $updates;
    }

    public function afterRelationUpdateButBeforeNodeUpdate(string $tenantId, string $id, array $updates): array
    {
        return $updates;
    }

    public function afterInsert(stdClass $data): void {}
    public function afterUpdate(stdClass $data): void {}
    public function afterDelete(stdClass $data): void {}
    public function afterBulkUpdate(callable $closure): void {}

}