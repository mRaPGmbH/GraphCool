<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Definition;

use Closure;
use GraphQL\Type\Definition\Type;
use Mrap\GraphCool\Exception\DoNotUpdateDerivedFieldException;
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


}