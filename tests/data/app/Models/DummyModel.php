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

        $this->enum = Field::enum(['A','B','C'])->description('test description');

        $this->ignoreMe = 'not a field';

        $this->belongs_to = Relation::belongsTo(__CLASS__)->nullable();
        $this->belongs_to2 = Relation::belongsTo(__CLASS__);
        $this->belongs_to_many = Relation::belongsToMany(__CLASS__);
        $this->belongs_to_many->pivot_property = Field::string()->nullable();
        $this->belongs_to_many->pivot_enum = Field::enum(['X','Y','Z']);
    }

}