<?php
declare(strict_types=1);

namespace Mrap\GraphCool\Types\Inputs;


use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\Type;
use Mrap\GraphCool\Model\Field;
use Mrap\GraphCool\Model\Model;
use Mrap\GraphCool\Types\Objects\ModelType;
use Mrap\GraphCool\Types\TypeLoader;

class EdgeSelectorType extends InputObjectType
{

    public function __construct(string $name, TypeLoader $typeLoader)
    {
        $fields = [
            'id' => new NonNull(Type::id()),
            'columns' => new NonNull(new ListOfType(new NonNull($typeLoader->load(substr($name, 0, -8) . 'ExportColumn')))),
        ];
        $config = [
            'name' => $name,
            'description' => 'Selector for one ' . substr($name, 1, -12) . ' relation.',
            'fields' => $fields,
        ];
        parent::__construct($config);
    }


}