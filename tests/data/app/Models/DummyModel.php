<?php

namespace App\Models;

use Mrap\GraphCool\Definition\Field;
use Mrap\GraphCool\Definition\Model;
use Mrap\GraphCool\Definition\Relation;

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
        $this->unique = Field::string()->nullable()->unique();

        $this->country = Field::countryCode()->nullable();
        $this->timezone = Field::timezoneOffset()->nullable();
        $this->locale = Field::localeCode()->nullable();
        $this->currency = Field::currencyCode()->nullable();
        $this->language = Field::languageCode()->nullable();
        $this->decimal = Field::decimal()->nullable();
        $this->bool = Field::bool()->nullable();

        $this->ignoreMe = 'not a field';

        $this->belongs_to = Relation::belongsTo(__CLASS__)->nullable();
        $this->belongs_to->pivot_property = Field::string()->default('default');
        $this->belongs_to->pivot_property2 = Field::string()->nullable();
        $this->belongs_to->pivot_property3 = Field::string()->nullable();
        $this->belongs_to2 = Relation::belongsTo(__CLASS__);
        $this->belongs_to_many = Relation::belongsToMany(__CLASS__);
        $this->belongs_to_many->pivot_property = Field::string()->nullable();
        $this->belongs_to_many->pivot_enum = Field::enum(['X','Y','Z']);
        $this->has_one = Relation::hasOne(__CLASS__)->nullable();
    }

}