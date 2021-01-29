<?php


namespace Mrap\GraphCool\Types;

use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
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
         * @var string $fieldName
         * @var Field $field
         */
        foreach ($model as $fieldName => $field)
        {
            if ($field instanceof Relation) {
                if ($field->type === Relation::BELONGS_TO || $field->type === Relation::HAS_ONE) {
                    $type = $typeLoader->load($field->name);
                } elseif ($field->type === Relation::HAS_MANY) {
                    $type = new ListOfType($typeLoader->load($field->name));
                } else {
                    continue;
                }
            } else {
                if (!$field instanceof Field) {
                    continue;
                }
                $type = $typeLoader->loadForField($field, $fieldName);
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
            $config['fields'][$fieldName] = $typeConfig;
        }
        ksort($config['fields']);
        parent::__construct($config);
    }
}