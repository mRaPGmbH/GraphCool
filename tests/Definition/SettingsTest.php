<?php


namespace Mrap\GraphCool\Tests\Definition;


use Mrap\GraphCool\Definition\QuerySettings;
use Mrap\GraphCool\Definition\Settings;
use Mrap\GraphCool\Tests\TestCase;

class SettingsTest extends TestCase
{
    public function testGet(): void
    {
        $settings = (new Settings())->get();
        self::assertSame(QuerySettings::QUERY, $settings->get->type);
        self::assertSame(QuerySettings::USER, $settings->get->access);
    }

    public function testFind(): void
    {
        $settings = (new Settings())->find();
        self::assertSame(QuerySettings::QUERY, $settings->find->type);
        self::assertSame(QuerySettings::USER, $settings->find->access);
    }

    public function testExport(): void
    {
        $settings = (new Settings())->export();
        self::assertSame(QuerySettings::QUERY, $settings->export->type);
        self::assertSame(QuerySettings::USER, $settings->export->access);
    }

    public function testCreate(): void
    {
        $settings = (new Settings())->create();
        self::assertSame(QuerySettings::MUTATION, $settings->create->type);
        self::assertSame(QuerySettings::USER, $settings->create->access);
    }

    public function testUpdate(): void
    {
        $settings = (new Settings())->update();
        self::assertSame(QuerySettings::MUTATION, $settings->update->type);
        self::assertSame(QuerySettings::USER, $settings->update->access);
    }

    public function testUpdateMany(): void
    {
        $settings = (new Settings())->updateMany();
        self::assertSame(QuerySettings::MUTATION, $settings->updateMany->type);
        self::assertSame(QuerySettings::USER, $settings->updateMany->access);
    }

    public function testImport(): void
    {
        $settings = (new Settings())->import();
        self::assertSame(QuerySettings::MUTATION, $settings->import->type);
        self::assertSame(QuerySettings::USER, $settings->import->access);
    }

    public function testDelete(): void
    {
        $settings = (new Settings())->delete();
        self::assertSame(QuerySettings::MUTATION, $settings->delete->type);
        self::assertSame(QuerySettings::USER, $settings->delete->access);
    }

    public function testRestore(): void
    {
        $settings = (new Settings())->restore();
        self::assertSame(QuerySettings::MUTATION, $settings->restore->type);
        self::assertSame(QuerySettings::USER, $settings->restore->access);
    }

}