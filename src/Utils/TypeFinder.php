<?php


namespace Mrap\GraphCool\Utils;


use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\Type;
use Mrap\GraphCool\Model\Field;

class TypeFinder
{
    public static function byField(Field $field): ScalarType
    {
        switch ($field->type) {
            case Type::STRING:
            case Field::CREATED_AT:
            case Field::UPDATED_AT:
                return Type::string();
            case Type::BOOLEAN:
                return Type::boolean();
            case Type::FLOAT:
                return Type::float();
            case Type::ID:
                return Type::id();
            case Type::INT:
                return Type::int();
        }
    }
}