<?php

namespace Mrap\GraphCool\Utils;

use GraphQL\Error\Error;

class Authorization
{
    public const READ = 'read';
    public const FIND = 'find';
    public const EXPORT = 'export';

    public const CREATE = 'create';
    public const UPDATE = 'update';
    public const UPDATE_MANY = 'updatemany';

    public const DELETE = 'delete';
    public const RESTORE = 'restore';
    public const IMPORT = 'import';

    public static function authorize(string $method, string $model): void
    {
        JwtAuthentication::authenticate();
        static::checkPermissions($method, $model, JwtAuthentication::getClaim('perm'));
    }

    public static function checkPermissions(string $method, string $model, ?string $permissions): void
    {
        if ($permissions === null || !Permissions::check($permissions, $method, strtolower($model), Env::get('APP_NAME'))) {
            throw new Error('403 Forbidden. JWT does not grant access to ' . Env::get('APP_NAME') . ':' . $model . '.' . $method);
        }
    }
}
