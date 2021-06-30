<?php


namespace Mrap\GraphCool\Tests\Types\Inputs;


use App\Models\DummyModel;
use GraphQL\Type\Definition\InputType;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Inputs\FileInputType;
use Mrap\GraphCool\Types\TypeLoader;

class FileInputTypeTest extends TestCase
{
    public function testConstructor(): void
    {
        require_once($this->dataPath().'/app/Models/DummyModel.php');
        $typeLoader = new TypeLoader();
        $modelType = $typeLoader->load('DummyModel')();

        $enum = new FileInputType($modelType, $typeLoader);
        self::assertInstanceOf(InputType::class, $enum);
    }
}