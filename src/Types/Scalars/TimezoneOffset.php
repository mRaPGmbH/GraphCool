<?php
declare(strict_types=1);

namespace Mrap\GraphCool\Types\Scalars;

use GraphQL\Error\Error;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;

class TimezoneOffset extends ScalarType
{

    public $name = '_TimezoneOffset';
    public $description = 'A Timezone offset string in ISO 8601 format: "+00:00" or "Z"';

    public function serialize($value)
    {
        if ($value < 0) {
            $sign = '-';
        } else {
            $sign = '+';
        }
        $value = round(abs($value) / 60);
        $minutes = $value % 60;
        $hours = floor($value / 60);

        $string = $sign
            . str_pad((string)$hours, 2, '0', STR_PAD_LEFT)
            . ':'
            . str_pad((string)$minutes, 2, '0', STR_PAD_LEFT);

        $dateTime = new \DateTime();
        $dateTime->setTimezone(new \DateTimeZone($string));
        return $dateTime->format('P');
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
        $dateTime = \DateTime::createFromFormat('P', $value);
        if ($dateTime === false || !(
                $dateTime->format('P') === $value // +00:00
                || $dateTime->format('p') === $value // Z
                || $dateTime->format('O') === $value // +0000
                || substr($dateTime->format('O'), 0, -2) === $value // +00
            )) {
            throw new Error('Invalid Time format; Try using this format instead: "+00:00"');
        }
        return $dateTime->getOffset();
    }

}