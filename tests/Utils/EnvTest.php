<?php


namespace Mrap\GraphCool\Tests\Utils;


use Mrap\GraphCool\Utils\Env;
use Mrap\GraphCool\Tests\TestCase;

class EnvTest extends TestCase
{
    public function testNull(): void
    {
        putenv('test=null');
        putenv('test2=(null)');
        self::assertNull(Env::get('test'));
        self::assertNull(Env::get('test2'));
        self::assertNull(Env::get('test3'));
    }

    public function testTrue(): void
    {
        putenv('test=(true)');
        putenv('test2=true');
        self::assertTrue(Env::get('test'));
        self::assertTrue(Env::get('test2'));
    }

    public function testFalse(): void
    {
        putenv('test=(false)');
        putenv('test2=false');
        self::assertFalse(Env::get('test'));
        self::assertFalse(Env::get('test2'));
    }

    public function testEmpty(): void
    {
        putenv('test=(empty)');
        putenv('test2=empty');
        self::assertEmpty(Env::get('test'));
        self::assertEmpty(Env::get('test2'));
    }

    public function testQuotedString(): void
    {
        putenv('test=\'string\'');
        putenv('test2="string"');
        self::assertEquals('string', Env::get('test'));
        self::assertEquals('string', Env::get('test2'));
    }


}