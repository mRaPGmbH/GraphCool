<?php


namespace Mrap\GraphCool\Tests;


class TestCase extends \PHPUnit\Framework\TestCase
{

    protected function dataPath(): string
    {
        if (!defined('APP_ROOT_PATH')) {
            define('APP_ROOT_PATH', __DIR__ . '/data');
        }
        return APP_ROOT_PATH;
    }

}