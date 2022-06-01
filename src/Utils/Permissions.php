<?php

namespace Mrap\GraphCool\Utils;

/**
 * Permissions class
 * usage:
 * $code = Permissions::new()->read()->find()->create()->update()->getCode();
 *
 * $allowed = Permissions::check($code, 'read');
 */
class Permissions
{
    protected const READ = 1 << 0; // 0b 000 000 001
    protected const FIND = 1 << 1; // 0b 000 000 010
    protected const EXPORT = 1 << 2; // 0b 000 000 100

    protected const CREATE = 1 << 3; // 0b 000 001 000
    protected const UPDATE = 1 << 4; // 0b 000 010 000
    protected const UPDATE_MANY = 1 << 5; // 0b 000 100 000

    protected const DELETE = 1 << 6; // 0b 001 000 000
    protected const RESTORE = 1 << 7; // 0b 010 000 000
    protected const IMPORT = 1 << 8; // 0b 100 000 000

    public static function check(string $code, string $permission, string $entity, string $service): bool
    {
        $permissions = static::parseCode($code);
        if (!isset($permissions[$service])) {
            return false;
        }
        $bitmask = $permissions[$service][$entity] ?? $permissions[$service]['*'] ?? 0;
        return (self::permissionCode($permission) & $bitmask) > 0;
    }

    public static function createLocalCode(array $permissions): string
    {
        $tmp = [];
        foreach ($permissions as $entity => $allowed) {
            $tmp[] = $entity . '.' . static::getCode($allowed);
        }
        if (count($tmp) === 0) {
            return Env::get('APP_NAME') . ':*.0';
        }
        return Env::get('APP_NAME') . ':' . implode(',', $tmp);
    }

    protected static function parseCode(string $code): array
    {
        $services = [];
        foreach (explode('|', $code) as $service) {
            [$serviceName, $servicePermissions] = explode(':', $service);
            $entities = [];
            foreach (explode(',', $servicePermissions) as $entity) {
                [$entityName, $entityPermissions] = explode('.', $entity);
                $entities[$entityName] = (int)$entityPermissions;
            }
            $services[$serviceName] = $entities;
        }
        return $services;
    }

    protected static function getCode(array $allowed): int
    {
        $ret = 0;
        foreach ($allowed as $permission) {
            $ret |= static::permissionCode($permission);
        }
        return $ret;
    }

    protected static function permissionCode(string $permission): int
    {
        return match(strtolower($permission)) {
            'read' => self::READ,
            'find' => self::FIND,
            'export' => self::EXPORT,
            'create' => self::CREATE,
            'update' => self::UPDATE,
            'updatemany', 'update_many' => self::UPDATE_MANY,
            'delete' => self::DELETE,
            'restore' => self::RESTORE,
            'import' => self::IMPORT,
            default => 0,
        };
    }

}
