<?php


namespace Mrap\GraphCool\Types;


use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\Type;
use Mrap\GraphCool\Model\Field;
use Mrap\GraphCool\Model\Model;

class WhereInputType extends InputObjectType
{

    public function __construct(ModelType $type, TypeLoader $typeLoader)
    {
        $classname = 'App\\Models\\' . $type->name;
        $model = new $classname();
        $fields = [
            'column' => $this->getColumns($model, $type),
            'operator' => $typeLoader->load('SQLOperator')(),
            'value' => Type::string(),
            'AND' => new ListOfType($this),
            'OR' => new ListOfType($this)
        ];
        $config = [
            'name' => $type->name . 'WhereConditions',
            'fields' => $fields
        ];
        parent::__construct($config);
    }

    protected function getColumns(Model $model, ModelType $type): EnumType
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
            'name' => $type->name . 'sColumn',
            'description' => 'Allowed column names for the `where` argument on the query `' . strtolower($type->name). 's`.',
            'values' => $values
        ];
        return new EnumType($config);
    }

}