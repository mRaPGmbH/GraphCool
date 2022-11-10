<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Queries;

use GraphQL\Type\Definition\ResolveInfo;
use Mrap\GraphCool\Definition\Field;
use Mrap\GraphCool\Definition\Query;
use Mrap\GraphCool\Definition\Relation;
use Mrap\GraphCool\Types\Type;
use Mrap\GraphCool\Utils\ClassFinder;

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
            $model = new $classname();
            $classes[] = $t . 'class ' . $name . '{';
            foreach ($model as $key => $item) {
                if ($item instanceof Field) {
                    if (
                        $item->type === Field::CREATED_AT
                        || $item->type === Field::UPDATED_AT
                        || $item->type === Field::DELETED_AT
                        || $item->type === Type::ID
                    ) {
                        continue;
                    }
                    $classes[] = $t . $t . strtoupper($item->type) . ' ' . $key . ($item->null?'':'!');
                } elseif ($item instanceof Relation) {
                    $classes[] = $t . $t . $item->type . '(' . $item->name . ') ' . $key ;
                    if ($item->type === Relation::BELONGS_TO || $item->type === Relation::BELONGS_TO_MANY) {
                        $pivotFields = [];
                        foreach ($item as $subKey => $subItem) {
                            if ($subItem instanceof Field) {
                                if (
                                    $subItem->type === Field::CREATED_AT
                                    || $subItem->type === Field::UPDATED_AT
                                    || $subItem->type === Field::DELETED_AT
                                    || $subItem->type === Type::ID
                                ) {
                                    continue;
                                }
                                $pivotFields[] = $subItem->type . ' ' . $subKey . ($subItem->null?'':'!');
                            }
                        }
                        $relations[] = $t . $name . ' --|> ' . $item->name . ' : ' . implode(PHP_EOL, $pivotFields);
                    }
                }
            }
            $classes[] = $t . '}';
        }
        $newline = 'NEWLINE';
        return implode($newline, $classes) . $newline . implode($newline, $relations);
    }
}
