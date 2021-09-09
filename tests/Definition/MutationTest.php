<?php


namespace Model;


use App\Mutations\DummyMutation;
use App\Mutations\PublicMutation;
use Mrap\GraphCool\Definition\Mutation;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\TypeLoader;

class MutationTest extends TestCase
{
    public function testNoAuthentication(): void
    {
        require_once($this->dataPath().'/app/Mutations/PublicMutation.php');
        $mutation = new PublicMutation(new TypeLoader());
        self::assertInstanceOf(Mutation::class, $mutation);
    }

    public function testAuthentication(): void
    {
        $this->provideJwt();
        require_once($this->dataPath().'/app/Mutations/DummyMutation.php');
        $mutation = new DummyMutation(new TypeLoader());
        $mutation->authenticate();
        self::assertInstanceOf(Mutation::class, $mutation);
    }
}