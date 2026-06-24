<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Tests\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type as GraphQLType;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Type;

class TypeTest extends TestCase
{
    public function testRegisterMakesCustomTypeResolvableByGet(): void
    {
        $custom = new ObjectType([
            'name' => '_RegisterTestType',
            'fields' => [
                'ok' => GraphQLType::boolean(),
            ],
        ]);

        Type::register('_RegisterTestType', $custom);

        // get() must return the exact same instance (singleton) the schema needs.
        self::assertSame($custom, Type::get('_RegisterTestType'));
    }
}
