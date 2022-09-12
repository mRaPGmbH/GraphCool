<?php

namespace Mrap\GraphCool\Definition;

use Mrap\GraphCool\DataSource\File;
use function Mrap\GraphCool\model;

class Entity
{
    private string $_name;
    public function __construct(string $name)
    {
        $this->_name = $name;
    }

    public function _name(): string
    {
        return $this->_name;
    }

    public function _model(): Model
    {
        return model($this->_name);
    }

    public function _vars(): array
    {
        return get_object_vars($this->_model());
    }

    public function _retrieveFiles(): void
    {
        foreach ($this->_vars() as $key => $item) {
            if (
                !$item instanceof Field
                || $item->type !== Field::FILE
                || ($data->$key ?? null) === null
            ) {
                continue;
            }
            //can't use closure here, because there are subfields - graphql-php only allows closures at leaf-nodes
            //$value = $data->$key;
            //$data->$key = function() use($name, $id, $key, $value) {File::retrieve($name, $id, $key, $value);};
            $this->$key = File::retrieve($this->_name, $this->id, $key, $this->$key);
        }
    }


}