<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Models;

use Mrap\GraphCool\Definition\Field;
use Mrap\GraphCool\Definition\Job as Job2;
use Mrap\GraphCool\Definition\Model;

class Job extends Model
{

    public function __construct()
    {
        parent::__construct();
        unset($this->updated_at, $this->deleted_at);
        $this->worker = Field::string();
        $this->model = Field::string()->nullable();
        $this->status = Field::enum(Job2::allStatuses());
        $this->data = Field::string()->nullable();
        $this->result = Field::string()->nullable();
        $this->run_at = Field::dateTime()->nullable();
        $this->started_at = Field::dateTime()->nullable();
        $this->finished_at = Field::dateTime()->nullable();
    }

}