<?php

namespace Mrap\GraphCool\DataSource\Mysql;

use Mrap\GraphCool\DataSource\FullTextIndexProvider;
use Mrap\GraphCool\Definition\Field;
use Mrap\GraphCool\Definition\Model;
use Mrap\GraphCool\Definition\Relation;
use stdClass;

class MysqlFullTextIndexProvider implements FullTextIndexProvider
{

    protected array $needIndexing = [];

    public function index(string $model, stdClass $data): void
    {
        if (!is_array($this->needIndexing[$model])) {
            $this->needIndexing[$model] = [];
        }
        $this->needIndexing[$model][] = $data;
    }

    public function shutdown(): void
    {
        $rows = [];
        foreach ($this->needIndexing as $name => $datas) {
            foreach ($datas as $data) {
                $row = $this->prepareRow($name, $data);
                if ($row !== null) {
                    $rows[] = $row;
                }
            }
        }
    }

    protected function prepareRow(string $name, stdClass $data): ?string
    {
        $model = Model::get($name);
        $text = [];

        foreach ($model as $key => $item) {
            if ($item instanceof Field) {
                if ($item->fulltextIndex === true && !empty($data->$key)) {
                    $text[] = (string)$data->$key;
                }
            } elseif ($item instanceof Relation) {
                foreach ($item as $rkey => $field) {
                    if ($field->fulltextIndex === true) {
                        // TODO: might be array
                    }
                }
            }
        }
        if (count($text) === 0) {
            return null;
        }
        return '(' . implode(' ', $text) . ')';
    }
}