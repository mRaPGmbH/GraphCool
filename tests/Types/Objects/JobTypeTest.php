<?php


namespace Mrap\GraphCool\Tests\Types\Objects;


use GraphQL\Type\Definition\ObjectType;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Objects\Job;

class JobTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        $object = new Job('Import');
        self::assertInstanceOf(ObjectType::class, $object);
    }
}
