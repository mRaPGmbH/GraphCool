<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Tests\Utils;

use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Utils\ConfigProvider;

class ConfigProviderTest extends TestCase
{
    public function testGet(): void
    {
        $provider = new ConfigProvider();
        $result1 = $provider->get('does_not_exist');
        $result2 = $provider->get('does_not_exist', 'anything');
        self::assertEquals([], $result1);
        self::assertNull($result2);
    }
}