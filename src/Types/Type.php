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
use Mrap\GraphCool\Types\Inputs\EdgeWhereConditions;
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
use function Mrap\GraphCool\model;

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

    protected static function create(string $name): NullableType
    {
        return match ($name) {
            '_PaginatorInfo' => static::paginatorInfo(),
            '_SQLOperator' => static::sqlOperator(),
            '_CountryCode' => static::countryCodeEnum(),
            '_LanguageCode' => static::languageEnum(),
            '_CurrencyCode' => static::currencyEnum(),
            '_LocaleCode' => static::localeEnum(),
            '_SortOrder' => static::sortOrderEnum(),
            '_FileExport' => static::fileExport(),
            '_ExportFile' => static::sheetFileEnum(),
            '_ImportSummary' => static::importSummary(),
            '_ImportError' => static::importError(),
            '_Result' => static::result(),
            '_DateTime' => static::dateTime(),
            '_Date' => static::date(),
            '_Time' => static::time(),
            '_TimezoneOffset' => static::timezoneOffset(),
            'Mixed' => static::mixed(),
            '_UpdateManyResult' => static::updateManyResult(),
            '_RelationUpdateMode' => static::relationUpdateModeEnum(),
            '_Upload' => static::upload(),
            '_File' => static::file(),
            '_Job_Column' => static::column('Job_'),
            '_Job_Status' => static::jobStatus(),
            '_History_Column' => static::column('History_'),
            '_History_ChangeType' => static::historyChangeType(),
            '_History' => static::history(),
            '_Permission' => static::permissionEnum(),
            '_Entity' => static::entity(),
            '_WhereMode' => static::whereMode(),
            '_Model' => static::modelEnum(),
            default => static::createDynamic($name),
        };
    }

    protected static function createDynamic(string $name): NullableType
    {
        // WARNING: order of matching is partially important below!
        // p.ex.: _<something>EdgeColumn has to be matched before _<something>Column!

        if (str_contains($name, '__')) { // if it does not, skip all these checks

            // _<model>__<field>Enum || _<model>__<relation>__<field>Enum
            if (DynamicEnum::nameMatches($name)) {
                return static::enum(static::getField(DynamicEnum::getStrippedName($name)));
            }

            // _<model>__<relation>Edges
            if (ModelEdgePaginator::nameMatches($name)) {
                return static::edge(static::getRelation(ModelEdgePaginator::getStrippedName($name)));
            }

            // _<model>__<relation>Edge
            if (ModelEdge::nameMatches($name)) {
                return static::edge(static::getRelation(ModelEdge::getStrippedName($name)));
            }

            // _<model>__<relation>EdgeOrderByClause
            if (EdgeOrderByClause::nameMatches($name)) {
                return static::orderByClause(static::getRelation(EdgeOrderByClause::getStrippedName($name)));
            }

            // _<model>__<relation>EdgeReducedColumn
            if (EdgeReducedColumn::nameMatches($name)) {
                return static::column(static::getRelation(EdgeReducedColumn::getStrippedName($name)), true);
            }

            // _<model>__<relation>EdgeColumn
            if (EdgeColumn::nameMatches($name)) {
                return static::column(static::getRelation(EdgeColumn::getStrippedName($name)));
            }

            // _<model>__<relation>EdgeWhereConditions
            if (EdgeWhereConditions::nameMatches($name)) {
                return static::whereConditions(static::getRelation(EdgeWhereConditions::getStrippedName($name)));
            }

            // _<model>__<relation>EdgeReducedSelector
            if (EdgeReducedSelector::nameMatches($name)) {
                return static::edgeSelector(static::getRelation(EdgeReducedSelector::getStrippedName($name)), true);
            }

            // _<model>__<relation>EdgeSelector
            if (EdgeSelector::nameMatches($name)) {
                return static::edgeSelector(static::getRelation(EdgeSelector::getStrippedName($name)));
            }

            // _<model>__<relation>EdgeReducedColumnMapping
            if (EdgeReducedColumnMapping::nameMatches($name)) {
                return static::columnMapping(static::getRelation(EdgeReducedColumnMapping::getStrippedName($name)), true);
            }

            // _<model>__<relation>EdgeColumnMapping
            if (EdgeColumnMapping::nameMatches($name)) {
                return static::columnMapping(static::getRelation(EdgeColumnMapping::getStrippedName($name)));
            }

            // _<model>__<relation>ManyRelation
            if (ModelManyRelation::nameMatches($name)) {
                return static::relation(static::getRelation(ModelManyRelation::getStrippedName($name)));
            }

            // _<model>__<relation>Relation
            if (ModelRelation::nameMatches($name)) {
                return static::relation(static::getRelation(ModelRelation::getStrippedName($name)));
            }

        }

        // _<model>Paginator
        if (ModelPaginator::nameMatches($name)) {
            return static::paginatedList(ModelPaginator::getStrippedName($name));
        }

        // _<model>WhereConditions
        if (WhereConditions::nameMatches($name)) {
            return self::whereConditions(WhereConditions::getStrippedName($name));
        }

        // _<model>OrderByClause
        if (ModelOrderByClause::nameMatches($name)) {
            return static::orderByClause(ModelOrderByClause::getStrippedName($name));
        }

        // _<model>ColumnMapping
        if (ModelColumnMapping::nameMatches($name)) {
            return static::columnMapping(ModelColumnMapping::getStrippedName($name));
        }

        // _<model>Column
        if (ModelColumn::nameMatches($name)) {
            return static::column(ModelColumn::getStrippedName($name));
        }

        // _<model>Input
        if (ModelInput::nameMatches($name)) {
            return static::input(ModelInput::getStrippedName($name));
        }

        // _<entity>Job
        if (Job::nameMatches($name)) {
            return static::job(Job::getStrippedName($name));
        }

        // _<model>ImportPreview
        if (ImportPreview::nameMatches($name)) {
            return static::importPreview(ImportPreview::getStrippedName($name));
        }

        return static::model($name);
    }

    protected static function getRelation(string $name): Relation
    {
        $parts = explode('__', $name, 2);
        $relation = model($parts[0])->{$parts[1]} ?? null;
        if (!$relation instanceof Relation) {
            throw new RuntimeException('Unknown relation: ' . $name);
        }
        return $relation;
    }

    protected static function getField(string $name): Field
    {
        $parts = explode('__', $name, 3);
        if (count($parts) === 3) {
            $field = static::getRelation($parts[0] . '__' . $parts[1])->{$parts[2]} ?? null;
        } else {
            $field = model($parts[0])->{$parts[1]} ?? null;
        }
        if (!$field instanceof Field) {
            throw new RuntimeException('Unknown Field: ' . $name);
        }
        return $field;
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

    public static function whereConditions(string|Relation $wrappedType): WhereConditions|EdgeWhereConditions
    {
        if (is_string($wrappedType)) {
            return static::cache(new WhereConditions($wrappedType));
        }
        return static::cache(new EdgeWhereConditions($wrappedType));
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
            'Import', 'Export', 'Delete' => static::cache(new Job($type)),
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
