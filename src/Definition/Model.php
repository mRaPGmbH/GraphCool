<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Definition;

use Closure;
use Mrap\GraphCool\Exception\DoNotUpdateDerivedFieldException;
use Mrap\GraphCool\Types\Type;
use RuntimeException;
use stdClass;

class Model extends stdClass
{

    public function __construct()
    {
        $this->id = Field::id();
        $this->created_at = Field::createdAt();
        $this->updated_at = Field::updatedAt();
        $this->deleted_at = Field::deletedAt();
    }

    public function onSave(stdClass $loaded, array $changes): void {}
    public function onDelete(stdClass $loaded): void {}
    public function onShutdown(): void {}

    /**
     * @deprecated use onSave() instead!
     */
    public function onChange(stdClass $loaded, array $changes): void {}

    public function udpateDerivedFields(string $tenantId, array $changes, ?string $id = null): array
    {
        foreach ($this as $key => $field) {
            if (!$field instanceof Field || $field->derived !== true) {
                continue;
            }
            try {
                $changes[$key] = ($field->closure)($changes, $tenantId, $id);
            } catch (DoNotUpdateDerivedFieldException){
                // do nothing
            }
        }
        return $changes;
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

    public function beforeRestore(string $tenantId, string $id): void
    {
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

    public function afterRestore(stdClass $data): void
    {
    }

    public function afterBulkUpdate(Closure $closure): void
    {
    }


    public function getPropertyNamesForFulltextIndexing(): array
    {
        $result = [];
        foreach ($this as $key => $field) {
            if (!$field instanceof Field) {
                continue;
            }
            if ($field->fulltextIndex) {
                $result[] = $key;
            }
        }
        return $result;
    }

    protected function getBelongsToRelations(): array
    {
        $edges = [];
        foreach ($this as $key => $relation) {
            if (!$relation instanceof Relation) {
                continue;
            }
            if ($relation->type !== 'BELONGS_TO' && $relation->type !== 'BELONGS_TO_MANY') {
                continue;
            }
            $edges[$key] = $relation;
        }
        return $edges;
    }

    public function getEdgePropertyNamesForFulltextIndexing(): array
    {
        $result = [];
        foreach ($this->getBelongsToRelations() as $relation) {
            foreach ($relation as $key => $field) {
                if (!$field instanceof Field) {
                    continue;
                }
                if ($field->fulltextIndex) {
                    $result[$key] = $relation->name; // TODO: $key might not be unique!
                }
            }
        }
        return $result;
    }

    public function getPropertyNamesForHistory(?array $updates = null): array
    {
        $result = [];
        foreach ($this as $key => $field) {
            if ($updates !== null && !array_key_exists($key, $updates)) {
                continue;
            }
            if ($field instanceof Field && $field->history) {
                $result[$key] = $field->type;
            } elseif ($field instanceof Relation && ($field->type === 'BELONGS_TO' || $field->type === 'BELONGS_TO_MANY')) {
                $relation = [];
                if ($field->history) {
                    $relation['parent_id'] = Type::STRING;
                }
                foreach ($field as $rkey => $rfield) {
                    if ($rfield instanceof Field && $rfield->history) {
                        $relation[$rkey] = $rfield->type;
                    }
                }
                if (count($relation) > 0) {
                    $result[$key] = $relation;
                }
            }
        }
        return $result;
    }

    public function injectFieldNames(): self
    {
        foreach ($this->fields() as $key => $field) {
            $field->namekey = basename(str_replace('\\', DIRECTORY_SEPARATOR, static::class)) . '__' . $key;
        }
        foreach ($this->relations() as $key => $relation) {
            $relation->namekey = basename(str_replace('\\', DIRECTORY_SEPARATOR, static::class)) . '__' . $key;
            foreach ($this->relationFields($key) as $fkey => $field) {
                $field->namekey = $relation->namekey . '__' . $fkey;
            }
        }
        return $this;
    }

    public function fields(): array
    {
        $ret = [];
        foreach (get_object_vars($this) as $key => $field) {
            if ($field instanceof Field) {
                $ret[$key] = $field;
            }
        }
        return $ret;
    }

    public function relations(?array $filterTypes = null): array
    {
        $ret = [];
        foreach (get_object_vars($this) as $key => $relation) {
            if (!$relation instanceof Relation) {
                continue;
            }
            if ($filterTypes === null || in_array($relation->type, $filterTypes, true)) {
                $ret[$key] = $relation;
            }
        }
        return $ret;
    }

    public function relationFields(string $relationKey): array
    {
        $relation = $this->$relationKey;
        if (!$relation instanceof Relation) {
            throw new RuntimeException('Cannot get relationFields: ' . $relationKey . ' is not a relation!');
        }
        return static::relationFieldsForRelation($relation);
    }

    public static function relationFieldsForRelation(Relation $relation): array
    {
        $ret = [];
        foreach (get_object_vars($relation) as $key => $field) {
            if ($field instanceof Field) {
                $ret[$key] = $field;
            }
        }
        return $ret;
    }


}
