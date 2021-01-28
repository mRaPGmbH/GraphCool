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



    /*
    protected function getCollation(): string
    {
        return 'COLLATE \'' . env('DB_COLLATION','utf8mb4_general_ci') . '\'';
    }*/

}