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

    public function beforeInsert(){}

    public function beforeUpdate(){}

}