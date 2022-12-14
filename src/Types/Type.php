<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types;

use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\NullableType;
use GraphQL\Type\Definition\Type as BaseType;
use MLL\GraphQLScalars\MixedScalar;
use Mrap\GraphCool\Definition\Field;
use Mrap\GraphCool\Types\Enums\ModelColumn;
use Mrap\GraphCool\Definition\Relation;
use Mrap\GraphCool\Types\Enums\CountryCode;
use Mrap\GraphCool\Types\Enums\Currency;
use Mrap\GraphCool\Types\Enums\DynamicEnum;
use Mrap\GraphCool\Types\Enums\EdgeColumn;
use Mrap\GraphCool\Types\Enums\EdgeReducedColumn;
use Mrap\GraphCool\Types\Enums\Entity;
use Mrap\GraphCool\Types\Enums\HistoryChangeType;
use Mrap\GraphCool\Types\Enums\HistoryColumn;
use Mrap\GraphCool\Types\Enums\JobColumn;
use Mrap\GraphCool\Types\Enums\JobStatus;
use Mrap\GraphCool\Types\Enums\LanguageCode;
use Mrap\GraphCool\Types\Enums\LocaleCode;
use Mrap\GraphCool\Types\Enums\ModelEnum;
use Mrap\GraphCool\Types\Enums\Permission;
use Mrap\GraphCool\Types\Enums\RelationUpdateMode;
use Mrap\GraphCool\Types\Enums\Result;
use Mrap\GraphCool\Types\Enums\SheetFile;
use Mrap\GraphCool\Types\Enums\SortOrder;
use Mrap\GraphCool\Types\Enums\SQLOperator;
use Mrap\GraphCool\Types\Enums\WhereMode;
use Mrap\GraphCool\Types\Inputs\ModelColumnMapping;
use Mrap\GraphCool\Types\Inputs\EdgeColumnMapping;
use Mrap\GraphCool\Types\Inputs\ModelRelation;
use Mrap\GraphCool\Types\Inputs\ModelManyRelation;
use Mrap\GraphCool\Types\Inputs\EdgeOrderByClause;
use Mrap\GraphCool\Types\Inputs\EdgeReducedColumnMapping;
use Mrap\GraphCool\Types\Inputs\EdgeReducedSelector;
use Mrap\GraphCool\Types\Inputs\EdgeSelector;
use Mrap\GraphCool\Types\Inputs\FileType;
use Mrap\GraphCool\Types\Inputs\ModelInput;
use Mrap\GraphCool\Types\Inputs\ModelOrderByClause;
use Mrap\GraphCool\Types\Inputs\WhereConditions;
use Mrap\GraphCool\Types\Objects\ModelEdgePaginator;
use Mrap\GraphCool\Types\Objects\ModelEdge;
use Mrap\GraphCool\Types\Objects\FileExport;
use Mrap\GraphCool\Types\Objects\History;
use Mrap\GraphCool\Types\Objects\ImportError;
use Mrap\GraphCool\Types\Objects\ImportPreview;
use Mrap\GraphCool\Types\Objects\ImportSummary;
use Mrap\GraphCool\Types\Objects\Job;
use Mrap\GraphCool\Types\Objects\ModelObject;
use Mrap\GraphCool\Types\Objects\PaginatorInfo;
use Mrap\GraphCool\Types\Objects\ModelPaginator;
use Mrap\GraphCool\Types\Objects\UpdateManyResult;
use Mrap\GraphCool\Types\Scalars\Date;
use Mrap\GraphCool\Types\Scalars\DateTime;
use Mrap\GraphCool\Types\Scalars\Time;
use Mrap\GraphCool\Types\Scalars\TimezoneOffset;
use Mrap\GraphCool\Types\Scalars\Upload;
use RuntimeException;

abstract class Type extends BaseType implements NullableType
{
    protected static array $types = [];

    public static function get(string $name): NullableType
    {
        if (!isset(static::$types[$name])) {
            throw new RuntimeException('Unknown type:' . $name);
        }
        return static::$types[$name];
    }

    public static function paginatedList(BaseType|string $wrappedType): ModelPaginator
    {
        if (is_string($wrappedType)) {
            $name = $wrappedType;
        } else {
            $name = $wrappedType->name;
        }
        return static::cache(new ModelPaginator($name));
    }

    public static function edge(Relation $relation): ModelEdge|ModelEdgePaginator
    {
        $type = static::cache(new ModelEdge($relation));
        if ($relation->type === Relation::BELONGS_TO_MANY || $relation->type === Relation::HAS_MANY) {
            $type = static::cache(new ModelEdgePaginator($type));
        }
        return $type;
    }

    public static function paginatorInfo(): PaginatorInfo
    {
        return static::cache(new PaginatorInfo());
    }

    public static function sqlOperator(): SQLOperator
    {
        return static::cache(new SQLOperator());
    }

    public static function countryCodeEnum(): CountryCode
    {
        return static::cache(new CountryCode());
    }

    public static function languageEnum(): LanguageCode
    {
        return static::cache(new LanguageCode());
    }

    public static function currencyEnum(): Currency
    {
        return static::cache(new Currency());
    }

    public static function localeEnum(): LocaleCode
    {
        return static::cache(new LocaleCode());
    }

    public static function sortOrderEnum(): SortOrder
    {
        return static::cache(new SortOrder());
    }

    public static function fileExport(): FileExport
    {
        return static::cache(new FileExport());
    }

    public static function sheetFileEnum(): SheetFile
    {
        return static::cache(new SheetFile());
    }

    public static function importSummary(): ImportSummary
    {
        return static::cache(new ImportSummary());
    }

    public static function importError(): ImportError
    {
        return static::cache(new ImportError());
    }

    public static function result(): Result
    {
        return static::cache(new Result());
    }

    public static function dateTime(): DateTime
    {
        return static::cache(new DateTime());
    }

    public static function date(): Date
    {
        return static::cache(new Date());
    }

    public static function time(): Time
    {
        return static::cache(new Time());
    }

    public static function timezoneOffset(): TimezoneOffset
    {
        return static::cache(new TimezoneOffset());
    }

    public static function mixed(): MixedScalar
    {
        return static::cache(new MixedScalar());
    }

    public static function updateManyResult(): UpdateManyResult
    {
        return static::cache(new UpdateManyResult());
    }

    public static function relationUpdateModeEnum(): RelationUpdateMode
    {
        return static::cache(new RelationUpdateMode());
    }

    public static function upload(): Upload
    {
        return static::cache(new Upload());
    }

    public static function file(): FileType
    {
        return static::cache(new FileType());
    }

    public static function permissionEnum(): Permission
    {
        return static::cache(new Permission());
    }

    public static function whereMode(): WhereMode
    {
        return static::cache(new WhereMode());
    }

    public static function modelEnum(): ModelEnum
    {
        return static::cache(new ModelEnum());
    }

    /**
     * @template T
     * @param  class-string<T> $type
     * @return T
     */
    protected static function cache(BaseType $type): BaseType
    {
        if (!isset(static::$types[$type->name])) {
            static::$types[$type->name] = $type;
        }
        return static::$types[$type->name];
    }

    public static function getForField(Field $field, bool $input = false, bool $optional = false): NullableType|NonNull
    {
        $type = match ($field->type) {
            default => Type::string(),
            static::BOOLEAN => Type::boolean(),
            static::FLOAT, Field::DECIMAL => Type::float(),
            static::ID => Type::id(),
            static::INT, Field::AUTO_INCREMENT => Type::int(),
            Field::COUNTRY_CODE => self::countryCodeEnum(),
            Field::CURRENCY_CODE => self::currencyEnum(),
            Field::LANGUAGE_CODE => self::languageEnum(),
            Field::LOCALE_CODE => self::localeEnum(),
            Field::ENUM => self::enum($field),
            Field::DATE_TIME, Field::CREATED_AT, Field::DELETED_AT, Field::UPDATED_AT => self::dateTime(),
            Field::DATE => self::date(),
            Field::TIME => self::time(),
            Field::TIMEZONE_OFFSET => self::timezoneOffset(),
            Field::FILE => $input ? self::file() : self::fileExport(),
        };
        if (
            $field->null === true || $optional === true
            || ($input === true && ($field->default ?? null) !== null)
        ) {
            return $type;
        }
        return Type::nonNull($type);
    }

    public static function input(BaseType|string $wrappedType): ModelInput
    {
        if (is_string($wrappedType)) {
            $name = $wrappedType;
        } else {
            $name = $wrappedType->name;
        }
        return static::cache(new ModelInput($name));
    }

    public static function column(BaseType|string|Relation $wrappedType, bool $reduced = false): ModelColumn|EdgeColumn|EdgeReducedColumn|JobColumn|HistoryColumn
    {
        if ($wrappedType === 'Job_') { // special case, because Job is not a regular model (yet)
            return static::cache(new JobColumn());
        }
        if ($wrappedType === 'History_') {  // special case, because History is not a regular model (yet)
            return static::cache(new HistoryColumn());
        }
        if ($wrappedType instanceof Relation) {
            if ($reduced === true) {
                return static::cache(new EdgeReducedColumn($wrappedType));
            }
            return static::cache(new EdgeColumn($wrappedType));
        }
        if (is_string($wrappedType)) {
            $name = $wrappedType;
        } else {
            $name = $wrappedType->name;
        }
        return static::cache(new ModelColumn($name));
    }

    public static function columnMapping(string|Relation $model, bool $reduced = false): ModelColumnMapping|EdgeColumnMapping|EdgeReducedColumnMapping
    {
        if (is_string($model)) {
            return static::cache(new ModelColumnMapping($model));
        }
        if ($reduced) {
            return static::cache(new EdgeReducedColumnMapping($model));
        }
        return static::cache(new EdgeColumnMapping($model));
    }

    public static function orderByClause(string|Relation $name): ModelOrderByClause|EdgeOrderByClause
    {
        if (is_string($name)) {
            return static::cache(new ModelOrderByClause($name));
        }
        return static::cache(new EdgeOrderByClause($name));
    }

    public static function relation(Relation $relation): ?NullableType
    {
        if ($relation->type === Relation::BELONGS_TO) {
            return static::cache(new ModelRelation($relation));
        }
        if ($relation->type === Relation::BELONGS_TO_MANY) {
            return static::cache(new ModelManyRelation($relation));
        }
        return null;
    }

    public static function edgeSelector(Relation $relation, bool $reduced = false): EdgeSelector|EdgeReducedSelector
    {
        if ($reduced === true) {
            return static::cache(new EdgeReducedSelector($relation));
        }
        return static::cache(new EdgeSelector($relation));
    }

    public static function whereConditions(string|Relation $wrappedType): WhereConditions
    {
        return static::cache(new WhereConditions($wrappedType));
    }

    public static function model(string $name): ModelObject|NullableType
    {
        return static::cache(new ModelObject($name));
    }

    public static function enum(Field $field): DynamicEnum
    {
        return static::cache(new DynamicEnum($field));
    }

    public static function importPreview(string $model): ImportPreview
    {
        return static::cache(new ImportPreview($model));
    }

    public static function entity(): Entity
    {
        return static::cache(new Entity());
    }

    public static function job(string $type): Job
    {
        return match($type) {
            'Import', 'Export' => static::cache(new Job($type)),
            default => throw new RuntimeException('Unknown Job-Type: ' . $type),
        };
    }

    public static function history(): History
    {
        return static::cache(new History());
    }

    public static function historyChangeType(): HistoryChangeType
    {
        return static::cache(new HistoryChangeType());
    }

    public static function jobStatus(): JobStatus
    {
        return static::cache(new JobStatus());
    }

}
