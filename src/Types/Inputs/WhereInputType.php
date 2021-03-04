<?php
declare(strict_types=1);

namespace Mrap\GraphCool\Types\Inputs;


use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\Type;
use Mrap\GraphCool\Model\Field;
use Mrap\GraphCool\Model\Model;
use Mrap\GraphCool\Types\Objects\ModelType;
use Mrap\GraphCool\Types\TypeLoader;

class WhereInputType extends InputObjectType
{

    public function __construct(string $name, TypeLoader $typeLoader)
    {
        $modelName = substr($name, 1, -15);
        $classname = 'App\\Models\\' . $modelName;
        $model = new $classname();
        $fields = [
            'column' => $this->getColumns($model, $modelName),
            'operator' => $typeLoader->load('_SQLOperator')(),
            'value' => Type::string(),
            'AND' => new ListOfType($this),
            'OR' => new ListOfType($this)
        ];
        $config = [
            'name' => '_' . $modelName . 'WhereConditions',
            'fields' => $fields
        ];
        parent::__construct($config);
    }

    protected function getColumns(Model $model, string $shortName): EnumType
    {
        $values = [];
        foreach ($model as $name => $field) {
            if (!$field instanceof Field) {
                continue;
            }
            $upperName = strtoupper($name);
            $values[$upperName] = [
                'value' => $name,
                'description' => $field->description ?? null
            ];
        }
        ksort($values);
        $config = [
            'name' => '_' . $shortName . 'sColumn',
            'description' => 'Allowed column names for the `where` argument on the query `' . lcfirst($shortName). 's`.',
            'values' => $values
        ];
        return new EnumType($config);
    }

}