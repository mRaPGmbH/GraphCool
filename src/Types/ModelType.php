<?php


namespace Mrap\GraphCool\Types;

use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Mrap\GraphCool\Model\Field;
use Mrap\GraphCool\Model\Relation;
use Mrap\GraphCool\Utils\TypeFinder;

class ModelType extends ObjectType
{

    public function __construct(string $name, TypeLoader $typeLoader)
    {
        $classname = 'App\\Models\\' . $name;
        $model = new $classname();
        $config = [
            'name' => $name,
            'fields' => [],
        ];
        /**
         * @var string $key
         * @var Field $field
         */
        foreach ($model as $key => $field)
        {
            $args = null;
            if ($field instanceof Relation) {
                if ($field->type === Relation::BELONGS_TO || $field->type === Relation::HAS_ONE) {
                    $type = $typeLoader->load($field->name);
                } elseif ($field->type === Relation::HAS_MANY) {
                    $type = $typeLoader->load('_' . $name . '_' . $key . 'Edges', null, $this);
                    $args = [
                        'first'=> Type::int(),
                        'page' => Type::int(),
                        'where' => $typeLoader->load('_' . $field->name . 'WhereConditions'),
                    ];
                } else {
                    continue;
                }
            } else {
                if (!$field instanceof Field) {
                    continue;
                }
                $type = $typeLoader->loadForField($field, $key);
                if ($field->null === false) {
                    $type = new NonNull($type);
                }
            }
            $typeConfig = [
                'type' => $type
            ];
            if (isset($field->description)) {
                $typeConfig['description'] = $field->description;
            }
            if ($args !== null) {
                $typeConfig['args'] = $args;
            }
            $config['fields'][$key] = $typeConfig;
        }
        ksort($config['fields']);
        parent::__construct($config);
    }
}