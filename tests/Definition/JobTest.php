<?php


namespace Model;


use App\Mutations\DummyMutation;
use App\Mutations\PublicMutation;
use Mrap\GraphCool\Definition\Job;
use Mrap\GraphCool\Definition\Mutation;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\TypeLoader;

class JobTest extends TestCase
{

    public function testStatuses(): void
    {
        $result = Job::allStatuses();
        self::assertIsArray($result);
    }
}