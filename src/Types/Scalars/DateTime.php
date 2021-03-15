<?php
declare(strict_types=1);

namespace Mrap\GraphCool\Types\Scalars;

use GraphQL\Error\Error;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;
use Mrap\GraphCool\Utils\TimeZone;

class DateTime extends ScalarType
{

    public $name = '_DateTime';
    public $description = 'A DateTime string with timezone in one of following ISO 8601 formats: "2021-03-11T11:54:04+00:00", "2021-03-11T11:54:04Z", "2021-03-11T11:54:04+0000" or "2021-03-11T11:54:04+00"';

    public function serialize($value)
    {
        $dateTime = new \DateTime();
        $dateTime->setTimestamp($value);
        $dateTime->setTimezone(TimeZone::get());
        return $dateTime->format(\DateTime::ATOM);
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
        $dateTime = \DateTime::createFromFormat(\DateTime::ATOM, $value);
        if ($dateTime === false || !(
            $dateTime->format(\DateTime::ATOM) === $value // 2021-03-11T11:54:04+00:00
            || $dateTime->format('Y-m-d\TH:i:sp') === $value // 2021-03-11T11:54:04Z
            || $dateTime->format('Y-m-d\TH:i:sO') === $value // 2021-03-11T11:54:04+0000
            || substr($dateTime->format('Y-m-d\TH:i:sO'), 0, -2) === $value // 2021-03-11T11:54:04+00
            || $dateTime->format('Y-m-d\TH:i:s.vO') === $value // 2021-03-11T11:54:04.123+0000
            || $dateTime->format('Y-m-d\TH:i:s.uO') === $value // 2021-03-11T11:54:04.123456+0000
        )) {
            throw new Error('Invalid datetime format; Try using this format instead: "2021-03-11T11:54:04+00:00"');
        }
        return $dateTime->getTimestamp();
    }

}