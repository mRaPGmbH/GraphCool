<?php


namespace Mrap\GraphCool\Tests\Types\Objects;


use GraphQL\Type\Definition\ObjectType;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Objects\JobType;
use Mrap\GraphCool\Types\TypeLoader;

class JobTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        $object = new JobType('_ImportJob', new TypeLoader());
        self::assertInstanceOf(ObjectType::class, $object);
    }
}
