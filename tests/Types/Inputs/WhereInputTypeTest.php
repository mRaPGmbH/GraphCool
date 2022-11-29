<?php


namespace Mrap\GraphCool\Tests\Types\Inputs;


use GraphQL\Type\Definition\InputType;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Inputs\WhereConditions;
use Mrap\GraphCool\Types\TypeLoader;

class WhereInputTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $input = new WhereConditions('_DummyModelWhereConditions', new TypeLoader());
        self::assertInstanceOf(InputType::class, $input);
    }
}