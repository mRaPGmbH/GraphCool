<?php


namespace Mrap\GraphCool\Tests;


use Mrap\GraphCool\Utils\ClassFinder;

class TestCase extends \PHPUnit\Framework\TestCase
{

    protected function setUp(): void
    {
        ClassFinder::setRootPath($this->dataPath());
    }

    protected function dataPath(): string
    {
        return __DIR__ . '/data';
    }

}