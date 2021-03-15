<?php
declare(strict_types=1);

namespace Mrap\GraphCool\Types\Scalars;

use GraphQL\Error\Error;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;

class Date extends ScalarType
{

    public $name = '_Date';
    public $description = 'A Date string in ISO 8601 format: "2021-03-11"';

    public function serialize($value)
    {
        $dateTime = new \DateTime();
        $dateTime->setTimezone(new \DateTimeZone("+0000"));
        $dateTime->setTimestamp($value);
        return $dateTime->format('Y-m-d');
    }

    public function parseValue($value)
    {
        return $this->validate($value);
    }

    public function parseLiteral(Node $valueNode, ?array $variables = null)
    {
        if (!$valueNode instanceof StringValueNode) {
            throw new Error('Query error: Can only parse strings but got: ' . $valueNode->kind, [$valueNode]);
        }
        return $this->validate($valueNode->value);
    }

    protected function validate($value)
    {
        $dateTime = \DateTime::createFromFormat('Y-m-d', $value);
        if ($dateTime === false || !$dateTime->format('Y-m-d') === $value) {
            throw new Error('Invalid date format; Try using this format instead: "2021-03-11"');
        }
        $dateTime->setTimezone(new \DateTimeZone("+0000"));
        return $dateTime->getTimestamp();
    }

}