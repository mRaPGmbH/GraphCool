<?php


namespace Mrap\GraphCool\Tests;


use Mrap\GraphCool\Utils\ClassFinder;
use Mrap\GraphCool\Utils\TimeZone;

class TestCase extends \PHPUnit\Framework\TestCase
{

    protected function setUp(): void
    {
        ClassFinder::setRootPath($this->dataPath());
        TimeZone::unset();
    }

    protected function dataPath(): string
    {
        return __DIR__ . '/data';
    }

    protected function provideJwt(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJzdWIiOjEyMywiYXVkIjoiZm9vIiwidGlkIjoxLCJpc3MiOiJodHRwOlwvXC9sb2NhbGhvc3Q6ODA4MCIsImlhdCI6MTU5MzUwOTYxNSwiZXhwIjoxOTAxMDkzNjE1LCJuYmYiOjE1OTM1MDk2MTUsImp0aSI6Inh4MHFYNzMydTVSdGFBNDkifQ.KNOhgi8OzGNrWXT0T0a66Ifk1AX-q2PFGo6YEskz9aHrO4yepK5HmxHyYval6RxjvV22z4p4r4Z_h1EtSUJHovZviWBzXgiOxQXAUlnBWJebpl256D5u0b7JDx2mOR6VZuu6nCpEGr6lq38VuW_yiVyJLhTdvfLVzF6rEFsnI54jBUlK1k5zmPDImzBJUoPa-BvAgOwLUfvDdiudsMs-a3tiZ5me7JmRaktPq6s_dGGjWVzeVAYD8rfs-WlHUJg0DkNbQWN9iPdnChryopwE7KjWZBKQPSH8RNuWd_eC0FQN97mcfPIAs_FBqiOQP0C8p1_2bvw8VpcGBp88DDPlZg';
    }

}