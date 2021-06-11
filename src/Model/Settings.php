<?php


namespace Mrap\GraphCool\Model;


class Settings
{

    public QuerySettings $get;
    public QuerySettings $find;
    public QuerySettings $export;

    public QuerySettings $create;
    public QuerySettings $update;
    public QuerySettings $updateMany;
    public QuerySettings $import;
    public QuerySettings $delete;
    public QuerySettings $restore;

    public function get(string $access = QuerySettings::USER): Settings
    {
        $this->get = new QuerySettings(QuerySettings::QUERY, $access);
        return $this;
    }

    public function find(string $access = QuerySettings::USER): Settings
    {
        $this->find = new QuerySettings(QuerySettings::QUERY, $access);
        return $this;
    }

    public function export(string $access = QuerySettings::USER): Settings
    {
        $this->export = new QuerySettings(QuerySettings::QUERY, $access);
        return $this;
    }

    public function create(string $access = QuerySettings::USER): Settings
    {
        $this->create = new QuerySettings(QuerySettings::MUTATION, $access);
        return $this;
    }

    public function update(string $access = QuerySettings::USER): Settings
    {
        $this->update = new QuerySettings(QuerySettings::MUTATION, $access);
        return $this;
    }

    public function updateMany(string $access = QuerySettings::USER): Settings
    {
        $this->updateMany = new QuerySettings(QuerySettings::MUTATION, $access);
        return $this;
    }

    public function import(string $access = QuerySettings::USER): Settings
    {
        $this->import = new QuerySettings(QuerySettings::MUTATION, $access);
        return $this;
    }

    public function delete(string $access = QuerySettings::USER): Settings
    {
        $this->delete = new QuerySettings(QuerySettings::MUTATION, $access);
        return $this;
    }

    public function restore(string $access = QuerySettings::USER): Settings
    {
        $this->restore = new QuerySettings(QuerySettings::MUTATION, $access);
        return $this;
    }




}