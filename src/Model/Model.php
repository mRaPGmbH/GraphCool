<?php
declare(strict_types=1);

namespace Mrap\GraphCool\Model;


use stdClass;

class Model
{
    private $settings;
    private static $instances = [];

    public function __construct()
    {
        $this->id = Field::id();
        $this->created_at = Field::createdAt();
        $this->updated_at = Field::updatedAt();
        $this->deleted_at = Field::deletedAt();
        $this->settings = new Settings();
    }

    public function beforeInsert(string $tenantId, array $data): array
    {
        return $data;
    }

    public function beforeUpdate(string $tenantId, string $id, array $updates): array
    {
        return $updates;
    }

    public function afterRelationUpdateButBeforeNodeUpdate(string $tenantId, string $id, array $updates): array
    {
        return $updates;
    }

    public function afterInsert(stdClass $data): void {}
    public function afterUpdate(stdClass $data): void {}
    public function afterDelete(stdClass $data): void {}
    public function afterBulkUpdate(\Closure $closure): void {}

    public function settings()
    {
        return $this->settings;
    }

    public static function get(string $name): Model
    {
        if (!isset(self::$instances[$name])) {
            $classname = 'App\\Models\\' . $name;
            self::$instances[$name] = new $classname();
        }
        return self::$instances[$name];
    }

}