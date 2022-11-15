<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Queries;

use GraphQL\Type\Definition\ResolveInfo;
use Mrap\GraphCool\Definition\Field;
use Mrap\GraphCool\Definition\Query;
use Mrap\GraphCool\Definition\Relation;
use Mrap\GraphCool\Types\Type;
use Mrap\GraphCool\Utils\ClassFinder;
use function Mrap\GraphCool\model;

class Diagram extends Query
{

    public function __construct(?string $model = null)
    {
        $this->name = '_classDiagram';
        $this->config = [
            'type' => Type::nonNull(Type::string()),
            'description' => 'Get a mermaid diagram of the database structure for all models.',
        ];
    }

    public function resolve(array $rootValue, array $args, mixed $context, ResolveInfo $info): mixed
    {
        $classes = [
            '```mermaid',
            'classDiagram',
        ];
        $relations = [];
        $t = '    ';
        foreach (ClassFinder::models() as $name => $classname) {
            $model = model($name);
            $classes[] = $t . 'class ' . $name . '{';
            foreach ($this->filter($model->fields()) as $key => $field) {
                $classes[] = $t . $t . strtoupper($field->type) . ' ' . $key . ($field->null ? '' : '!');
            }
            foreach ($model->relations() as $key => $relation) {
                $classes[] = $t . $t . $relation->type . '(' . $relation->name . ') ' . $key ;
                if ($relation->type === Relation::BELONGS_TO || $relation->type === Relation::BELONGS_TO_MANY) {
                    $pivotFields = [];
                    foreach ($this->filter($model->relationFields($key)) as $subKey => $subItem) {
                        $pivotFields[] = $subItem->type . ' ' . $subKey . ($subItem->null ? '' : '!');
                    }
                    $relations[] = $t . $name . ' --|> ' . $relation->name . ' : ' . implode(PHP_EOL, $pivotFields);
                }
            }
            $classes[] = $t . '}';
        }
        $newline = 'NEWLINE';
        return implode($newline, $classes) . $newline . implode($newline, $relations);
    }

    protected function filter(array $fields): array
    {
        $result = [];
        foreach ($fields as $key => $field) {
            if (
                $field->type === Field::CREATED_AT
                || $field->type === Field::UPDATED_AT
                || $field->type === Field::DELETED_AT
                || $field->type === Type::ID
            ) {
                continue;
            }
            $result[$key] = $field;
        }
        return $result;
    }
}
