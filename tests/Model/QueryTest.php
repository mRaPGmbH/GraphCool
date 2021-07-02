<?php


namespace Model;


use App\Queries\DummyQuery;
use App\Queries\PublicQuery;
use Mrap\GraphCool\Model\Query;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\TypeLoader;

class QueryTest extends TestCase
{

    public function testNoAuthentication(): void
    {
        require_once($this->dataPath().'/app/Queries/PublicQuery.php');
        $query = new PublicQuery(new TypeLoader());
        self::assertInstanceOf(Query::class, $query);
    }

    public function testAuthentication(): void
    {
        $this->provideJwt();
        require_once($this->dataPath().'/app/Queries/DummyQuery.php');
        $query = new DummyQuery(new TypeLoader());
        $query->authenticate();
        self::assertInstanceOf(Query::class, $query);
    }

}