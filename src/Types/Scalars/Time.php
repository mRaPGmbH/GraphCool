<?php
declare(strict_types=1);

namespace Mrap\GraphCool\Types\Scalars;

use GraphQL\Error\Error;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;
use Mrap\GraphCool\Utils\TimeZone;

class Time extends ScalarType
{

    public $name = '_Time';
    public $description = 'A Time string in ISO 8601 format: "11:54:04+00:00" or "11:54:04Z"';

    public function serialize($value)
    {
        $dateTime = new \DateTime();
        $dateTime->setTimestamp($value);
        $dateTime->setTimezone(TimeZone::get());
        return $dateTime->format('H:i:sP');
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
        $dateTime = \DateTime::createFromFormat('H:i:sP', $value);
        if ($dateTime === false || !(
                $dateTime->format('H:i:sP') === $value // 11:54:04+00:00
                || $dateTime->format('H:i:sp') === $value // 11:54:04Z
                || $dateTime->format('H:i:sO') === $value // 11:54:04+0000
                || substr($dateTime->format('H:i:sO'), 0, -2) === $value // 11:54:04+00
                || $dateTime->format('H:i:s.vO') === $value // 11:54:04.123+0000
                || $dateTime->format('H:i:s.uO') === $value // 11:54:04.123456+0000
                || $dateTime->format('H:i:s.vp') === $value // 11:54:04.123Z
                || $dateTime->format('H:i:s.up') === $value // 11:54:04.123456Z
            )) {
            throw new Error('Invalid Time format; Try using this format instead: "11:54:04+00:00"');
        }
        return $dateTime->getTimestamp();
    }

}