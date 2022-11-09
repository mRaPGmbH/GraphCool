<?php

namespace Mrap\GraphCool\Models;

use Mrap\GraphCool\Definition\CustomTable;
use Mrap\GraphCool\Definition\Field;
use Mrap\GraphCool\Definition\Model;

class History extends Model implements CustomTable
{

    public function __construct()
    {
        parent::__construct();
        unset($this->updated_at, $this->deleted_at);
        $this->number = Field::int();
        $this->node_id = Field::string();
        $this->model = Field::string();
        $this->sub = Field::string()->nullable();
        $this->ip = Field::string()->nullable();
        $this->user_agent = Field::string()->nullable();
        $this->change_type = Field::enum(['create', 'update', 'massUpdate', 'delete', 'restore'])->nullable();
        $this->changes = Field::string();
        $this->preceding_hash = Field::string()->nullable();
        $this->hash = Field::string();
    }

}