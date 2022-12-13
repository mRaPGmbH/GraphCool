<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types;

use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\NullableType;
use GraphQL\Type\Definition\Type as BaseType;
use MLL\GraphQLScalars\MixedScalar;
use Mrap\GraphCool\Definition\Field;
use Mrap\GraphCool\Types\Enums\ModelColumn;
use Mrap\GraphCool\Definition\Relation;
use Mrap\GraphCool\Types\Enums\CountryCodeEnumType;
use Mrap\GraphCool\Types\Enums\CurrencyEnumType;
use Mrap\GraphCool\Types\Enums\DynamicEnum;
use Mrap\GraphCool\Types\Enums\EdgeColumn;
use Mrap\GraphCool\Types\Enums\EdgeReducedColumn;
use Mrap\GraphCool\Types\Enums\EntityEnum;
use Mrap\GraphCool\Types\Enums\HistoryChangeTypeEnumType;
use Mrap\GraphCool\Types\Enums\HistoryColumnEnumType;
use Mrap\GraphCool\Types\Enums\JobColumnEnumType;
use Mrap\GraphCool\Types\Enums\JobStatusEnumType;
use Mrap\GraphCool\Types\Enums\LanguageEnumType;
use Mrap\GraphCool\Types\Enums\LocaleEnumType;
use Mrap\GraphCool\Types\Enums\ModelEnumType;
use Mrap\GraphCool\Types\Enums\PermissionEnumType;
use Mrap\GraphCool\Types\Enums\RelationUpdateModeEnum;
use Mrap\GraphCool\Types\Enums\ResultType;
use Mrap\GraphCool\Types\Enums\SheetFileEnumType;
use Mrap\GraphCool\Types\Enums\SortOrderEnumType;
use Mrap\GraphCool\Types\Enums\SQLOperatorType;
use Mrap\GraphCool\Types\Enums\WhereModeEnumType;
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
use Mrap\GraphCool\Types\Objects\FileExportType;
use Mrap\GraphCool\Types\Objects\HistoryType;
use Mrap\GraphCool\Types\Objects\ImportErrorType;
use Mrap\GraphCool\Types\Objects\ImportPreview;
use Mrap\GraphCool\Types\Objects\ImportSummaryType;
use Mrap\GraphCool\Types\Objects\JobType;
use Mrap\GraphCool\Types\Objects\ModelType;
use Mrap\GraphCool\Types\Objects\PaginatorInfoType;
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
            static::$types[$name] = static::create($name);
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

    public static function paginatorInfo(): PaginatorInfoType
    {
        return static::cache(new PaginatorInfoType());
    }

    public static function sqlOperator(): SQLOperatorType
    {
        return static::cache(new SQLOperatorType());
    }

    public static function countryCodeEnum(): CountryCodeEnumType
    {
        return static::cache(new CountryCodeEnumType());
    }

    public static function languageEnum(): LanguageEnumType
    {
        return static::cache(new LanguageEnumType());
    }

    public static function currencyEnum(): CurrencyEnumType
    {
        return static::cache(new CurrencyEnumType());
    }

    public static function localeEnum(): LocaleEnumType
    {
        return static::cache(new LocaleEnumType());
    }

    public static function sortOrderEnum(): SortOrderEnumType
    {
        return static::cache(new SortOrderEnumType());
    }

    public static function fileExport(): FileExportType
    {
        return static::cache(new FileExportType());
    }

    public static function sheetFileEnum(): SheetFileEnumType
    {
        return static::cache(new SheetFileEnumType());
    }

    public static function importSummary(): ImportSummaryType
    {
        return static::cache(new ImportSummaryType());
    }

    public static function importError(): ImportErrorType
    {
        return static::cache(new ImportErrorType());
    }

    public static function result(): ResultType
    {
        return static::cache(new ResultType());
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

    public static function relationUpdateModeEnum(): RelationUpdateModeEnum
    {
        return static::cache(new RelationUpdateModeEnum());
    }

    public static function upload(): Upload
    {
        return static::cache(new Upload());
    }

    public static function file(): FileType
    {
        return static::cache(new FileType());
    }

    public static function permissionEnum(): PermissionEnumType
    {
        return static::cache(new PermissionEnumType());
    }

    public static function whereMode(): WhereModeEnumType
    {
        return static::cache(new WhereModeEnumType());
    }

    public static function modelEnum(): ModelEnumType
    {
        return static::cache(new ModelEnumType());
    }


    protected static function create(string $name): NullableType
    {
        return match($name) {
            '_Job_Column' => new JobColumnEnumType(),
            '_Job_Status' => new JobStatusEnumType(),
            '_History_Column' => new HistoryColumnEnumType(),
            '_History_ChangeType' => new HistoryChangeTypeEnumType(),
            '_History' => new HistoryType(),
            default => static::createDynamic($name),
        };
    }

    protected static function createDynamic(string $name): NullableType
    {

        // TODO: probably can be removed after importjob, exportjob and history are models
        if (str_ends_with($name, 'Paginator')) {
            return new ModelPaginator(substr($name, 1, -9));
        }

        // TODO: probably can be removed after importjob, exportjob and history are models
        if (str_ends_with($name, 'WhereConditions')) {
            return new WhereConditions(substr($name, 1, -15));
        }

        // TODO: probably can be removed after importjob, exportjob and history are models
        if (str_ends_with($name, 'OrderByClause')) {
            return new ModelOrderByClause(substr($name, 1, -13));
        }

        if (str_ends_with($name, 'Column')) {
            // TODO: remove this once job+history are models
            return static::column(substr($name, 1, -6));
        }

        if ($name !== '_Job' && str_ends_with($name, 'Job')) {
            return new JobType($name);
        }
        throw new RuntimeException('Unhandled createDynamic: ' . $name);
    }

    /**
     * @template T
     * @param  class-string<T> $type
     * @return T
     */
    public static function cache(BaseType $type): BaseType // TODO: make protected when job and history are models
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

    public static function column(BaseType|string|Relation $wrappedType, bool $reduced = false): ModelColumn|EnumType|NullableType|EdgeColumn
    {
        if ($wrappedType === 'Job_') {
            // TODO: remove once importjob and exportjob are models
            return static::get('_Job_Column');
        }
        if ($wrappedType === 'History_') {
            // TODO: remove once history is a model
            return static::get('_History_Column');
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

    public static function model(string $name): ModelType|NullableType
    {
        if (str_starts_with($name, '_')) {
            // TODO: remove this after jobs+history are models
            return static::cache(static::create($name));
        }

        return static::cache(new ModelType($name));
    }

    public static function enum(Field $field): DynamicEnum
    {
        return static::cache(new DynamicEnum($field));
    }

    public static function importPreview(string $model): ImportPreview
    {
        return static::cache(new ImportPreview($model));
    }

    public static function entity(): EntityEnum
    {
        return static::cache(new EntityEnum());
    }

}
