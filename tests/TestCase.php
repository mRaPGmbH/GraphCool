<?php


namespace Mrap\GraphCool\Tests;


use Mrap\GraphCool\DataSource\Mysql\Mysql;
use Mrap\GraphCool\GraphCool;
use Mrap\GraphCool\Utils\ClassFinder;
use Mrap\GraphCool\Utils\Config;
use Mrap\GraphCool\Utils\ConfigProvider;
use Mrap\GraphCool\Utils\TimeZone;

class TestCase extends \PHPUnit\Framework\TestCase
{

    protected function setUp(): void
    {
        ClassFinder::setRootPath($this->dataPath());
        TimeZone::unset();
        Mysql::reset();
        Config::setProvider(new ConfigProvider());
        unset($_SERVER['HTTP_AUTHORIZATION']);
        $_FILES = [];
    }

    protected function dataPath(): string
    {
        return __DIR__ . '/data';
    }

    protected function provideJwt(string $jwt = null): void
    {
        if ($jwt === null) {
            $jwt = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJzdWIiOiIxIiwiZXhwIjoyNTk4MjQ3OTcwLCJ0aWQiOiIxIiwicGVybSI6ImNybToqLjUxMXxmaWxlOiouNTExIn0.S7wGjhZ8M2RFLdNaYhMCwOXyHzHrN_EW_3-eZe1njPmIjD4c38As-Vh5xvO22r8c2rfoUz_oX4z9_14HSC4OxQKs7fzwKHl3TMjWABduLgnMKeg-juVEozB75HBmYnz99_f7MIIVf1t07y-NrcKMz3KuVoKsOdgritkcujTpq4BK9l0nvaYRtHTPXbTCAXR316EeEC09mI7gXeV8i5x7t4VwaSB8iCGd1Jt9oPtqFc6hMug6qd6t7c_ZfRNsSwubFJ4PWpjuo366oc9kkScJ8doZIMAaKZSTow8t3dwRCKa0Bz72zi_BeneAYSmEYRhNI4UY4aGkO5cc1GC_MnkXiQ';
        }
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $jwt;
    }

}