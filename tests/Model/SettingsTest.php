<?php


namespace Model;


use Mrap\GraphCool\Model\QuerySettings;
use Mrap\GraphCool\Model\Settings;
use Mrap\GraphCool\Tests\TestCase;

class SettingsTest extends TestCase
{
    public function testGet(): void
    {
        $settings = (new Settings())->get();
        self::assertEquals(QuerySettings::QUERY, $settings->get->type);
        self::assertEquals(QuerySettings::USER, $settings->get->access);
    }

    public function testFind(): void
    {
        $settings = (new Settings())->find();
        self::assertEquals(QuerySettings::QUERY, $settings->find->type);
        self::assertEquals(QuerySettings::USER, $settings->find->access);
    }

    public function testExport(): void
    {
        $settings = (new Settings())->export();
        self::assertEquals(QuerySettings::QUERY, $settings->export->type);
        self::assertEquals(QuerySettings::USER, $settings->export->access);
    }

    public function testCreate(): void
    {
        $settings = (new Settings())->create();
        self::assertEquals(QuerySettings::MUTATION, $settings->create->type);
        self::assertEquals(QuerySettings::USER, $settings->create->access);
    }

    public function testUpdate(): void
    {
        $settings = (new Settings())->update();
        self::assertEquals(QuerySettings::MUTATION, $settings->update->type);
        self::assertEquals(QuerySettings::USER, $settings->update->access);
    }

    public function testUpdateMany(): void
    {
        $settings = (new Settings())->updateMany();
        self::assertEquals(QuerySettings::MUTATION, $settings->updateMany->type);
        self::assertEquals(QuerySettings::USER, $settings->updateMany->access);
    }

    public function testImport(): void
    {
        $settings = (new Settings())->import();
        self::assertEquals(QuerySettings::MUTATION, $settings->import->type);
        self::assertEquals(QuerySettings::USER, $settings->import->access);
    }

    public function testDelete(): void
    {
        $settings = (new Settings())->delete();
        self::assertEquals(QuerySettings::MUTATION, $settings->delete->type);
        self::assertEquals(QuerySettings::USER, $settings->delete->access);
    }

    public function testRestore(): void
    {
        $settings = (new Settings())->restore();
        self::assertEquals(QuerySettings::MUTATION, $settings->restore->type);
        self::assertEquals(QuerySettings::USER, $settings->restore->access);
    }

}