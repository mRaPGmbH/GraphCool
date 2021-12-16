<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Definition;

use Closure;
use GraphQL\Error\Error;
use stdClass;

class Model extends stdClass
{
    /** @var Model[] */
    private static array $instances = [];
    private Settings $settings;

    public function __construct()
    {
        $this->id = Field::id();
        $this->created_at = Field::createdAt();
        $this->updated_at = Field::updatedAt();
        $this->deleted_at = Field::deletedAt();
        $this->settings = new Settings();
    }

    public static function get(string $name): Model
    {
        if (!isset(self::$instances[$name])) {
            $classname = 'App\\Models\\' . $name;
            if (!class_exists($classname)) {
                throw new Error('Unknown entity: '.$name);
            }
            self::$instances[$name] = new $classname();
        }
        return self::$instances[$name];
    }

    /**
     * @param string $tenantId
     * @param mixed[] $data
     * @return mixed[]
     */
    public function beforeInsert(string $tenantId, array $data): array
    {
        return $data;
    }

    /**
     * @param string $tenantId
     * @param string $id
     * @param mixed[] $updates
     * @return mixed[]
     */
    public function beforeUpdate(string $tenantId, string $id, array $updates): array
    {
        return $updates;
    }

    /**
     * @param string $tenantId
     * @param string $id
     * @param mixed[] $updates
     * @return mixed[]
     */
    public function afterRelationUpdateButBeforeNodeUpdate(string $tenantId, string $id, array $updates): array
    {
        return $updates;
    }

    public function afterInsert(stdClass $data): void
    {
    }

    public function afterUpdate(stdClass $data): void
    {
    }

    public function afterDelete(stdClass $data): void
    {
    }

    public function afterBulkUpdate(Closure $closure): void
    {
    }

    /**
     * @return Settings
     */
    public function settings(): Settings
    {
        return $this->settings;
    }

}