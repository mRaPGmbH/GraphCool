<?php
declare(strict_types=1);

namespace Mrap\GraphCool\Model;


class Model
{

    public function __construct()
    {
        $this->id = Field::id();
        $this->created_at = Field::createdAt();
        $this->updated_at = Field::updatedAt();
        $this->deleted_at = Field::deletedAt();
    }

    public function beforeInsert(array $data): array
    {
        return $data;
    }

    public function beforeUpdate(string $id, array $updates): array
    {
        return $updates;
    }

}