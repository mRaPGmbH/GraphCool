<?php


namespace Mrap\GraphCool\Tests\Types\Inputs;


use App\Models\DummyModel;
use GraphQL\Type\Definition\InputType;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Inputs\FileInputType;
use Mrap\GraphCool\Types\Inputs\ModelInputType;
use Mrap\GraphCool\Types\Inputs\OrderByClauseType;
use Mrap\GraphCool\Types\TypeLoader;

class OrderByClauseTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $input = new OrderByClauseType('_DummyModelOrderByClause', new TypeLoader());
        self::assertInstanceOf(InputType::class, $input);
    }
}