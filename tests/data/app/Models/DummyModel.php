<?php

namespace App\Models;

use Mrap\GraphCool\Model\Field;
use Mrap\GraphCool\Model\Model;
use Mrap\GraphCool\Model\Relation;

class DummyModel extends Model
{

    public function __construct()
    {
        parent::__construct();
        $this->last_name = Field::string()->nullable();
        $this->date = Field::date()->nullable();
        $this->date_time = Field::dateTime()->nullable();
        $this->time = Field::time()->nullable();
        $this->float = Field::float()->nullable();

        $this->belongs_to = Relation::belongsTo(__CLASS__);
        $this->belongs_to_many = Relation::belongsToMany(__CLASS__);
        $this->belongs_to_many->pivot_property = Field::string()->nullable();
    }

}