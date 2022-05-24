<?php


namespace Mrap\GraphCool\Tests\Utils;


use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Utils\Env;
use Mrap\GraphCool\Utils\Permissions;

class PermissionsTest extends TestCase
{
    public function testPermissions(): void
    {
        $permissions = ['read', 'find', 'export', 'create', 'update', 'updatemany', 'delete', 'restore', 'import'];
        $entities = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o'];
        $service = Env::get('APP_NAME');
        foreach ($permissions as $perm) {
            foreach ($entities as $entity) {
                $code = Permissions::createLocalCode([$entity => [$perm]]);
                self::assertTrue(Permissions::check($code, $perm, $entity, $service));
                foreach ($permissions as $p) {
                    foreach ($entities as $e) {
                        if ($p === $perm && $e === $entity) {
                            self::assertFalse(Permissions::check($code, $p, $e, 'other-service'));
                            continue;
                        }
                        self::assertFalse(Permissions::check($code, $p, $e, $service));
                    }
                }
            }
        }
    }

    public function testPermissionsEmpty(): void
    {
        $code = Permissions::createLocalCode([]);
        $permissions = ['read', 'find', 'export', 'create', 'update', 'updatemany', 'delete', 'restore', 'import'];
        $service = Env::get('APP_NAME');
        foreach ($permissions as $p) {
            self::assertFalse(Permissions::check($code, $p, 'a', $service));
        }
    }


}