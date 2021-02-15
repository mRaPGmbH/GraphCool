<?php


namespace Mrap\GraphCool\Model;

class Model
{

    public function __construct()
    {
        $this->id = Field::id();
        $this->created_at = Field::createdAt();
        $this->updated_at = Field::updatedAt();
    }

    public function beforeInsert(){}

    public function beforeUpdate(){}

}