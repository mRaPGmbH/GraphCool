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
use Mrap\GraphCool\Types\Enums\DynamicEnumType;
use Mrap\GraphCool\Types\Enums\EdgeColumn;
use Mrap\GraphCool\Types\Enums\EdgeReducedColumnType;
use Mrap\GraphCool\Types\Enums\EntityEnumType;
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
use Mrap\GraphCool\Types\Inputs\EdgeReducedColumnMappingType;
use Mrap\GraphCool\Types\Inputs\EdgeReducedSelectorType;
use Mrap\GraphCool\Types\Inputs\EdgeSelectorType;
use Mrap\GraphCool\Types\Inputs\FileType;
use Mrap\GraphCool\Types\Inputs\ModelInput;
use Mrap\GraphCool\Types\Inputs\OrderByClause;
use Mrap\GraphCool\Types\Inputs\WhereConditions;
use Mrap\GraphCool\Types\Objects\ModelEdgePaginator;
use Mrap\GraphCool\Types\Objects\ModelEdge;
use Mrap\GraphCool\Types\Objects\FileExportType;
use Mrap\GraphCool\Types\Objects\HistoryType;
use Mrap\GraphCool\Types\Objects\ImportErrorType;
use Mrap\GraphCool\Types\Objects\ImportPreviewType;
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

    public static function paginatedList(BaseType|string $wrappedType): ModelPaginator
    {
        if (is_string($wrappedType)) {
            $name = $wrappedType;
        } else {
            $name = $wrappedType->name;
        }
        /** @var ModelPaginator $type */
        $type = static::cache(new ModelPaginator($name));
        return $type;
    }

    public static function edge(Relation $relation): ModelEdge|ModelEdgePaginator
    {
        /** @var ModelEdge $type */
        $type = static::cache(new ModelEdge($relation));
        if ($relation->type === Relation::BELONGS_TO_MANY || $relation->type === Relation::HAS_MANY) {
            $type = static::cache(new ModelEdgePaginator($type));
            /** @var ModelEdgePaginator $type */
        }
        return $type;
    }


    protected static function create(string $name): NullableType
    {
        return match($name) {
            '_PaginatorInfo' => new PaginatorInfoType(),
            '_SQLOperator' => new SQLOperatorType(),
            '_CountryCode' => new CountryCodeEnumType(),
            '_LanguageCode' => new LanguageEnumType(),
            '_CurrencyCode' => new CurrencyEnumType(),
            '_LocaleCode' => new LocaleEnumType(),
            '_SortOrder' => new SortOrderEnumType(),
            '_FileExport' => new FileExportType(),
            '_ExportFile' => new SheetFileEnumType(),
            '_ImportSummary' => new ImportSummaryType(),
            '_ImportError' => new ImportErrorType(),
            '_Result' => new ResultType(),
            '_DateTime' => new DateTime(),
            '_Date' => new Date(),
            '_Time' => new Time(),
            '_TimezoneOffset' => new TimezoneOffset(),
            'Mixed' => new MixedScalar(),
            '_UpdateManyResult' => new UpdateManyResult(),
            '_RelationUpdateMode' => new RelationUpdateModeEnum(),
            '_Upload' => new Upload(),
            '_File'=> new FileType(),
            '_Job_Column' => new JobColumnEnumType(),
            '_Job_Status' => new JobStatusEnumType(),
            '_History_Column' => new HistoryColumnEnumType(),
            '_History_ChangeType' => new HistoryChangeTypeEnumType(),
            '_History' => new HistoryType(),
            '_Permission' => new PermissionEnumType(),
            '_Entity' => new EntityEnumType(),
            '_WhereMode' => new WhereModeEnumType(),
            '_Model' => new ModelEnumType(),
            default => static::createDynamic($name),
        };
    }

    protected static function createDynamic(string $name): NullableType
    {
        // TODO: this could probably be wrapped types?

        if (!str_starts_with($name, '_')) {
            return new ModelType($name);
        }

        // TODO: probably can be removed after importjob, exportjob and history are models
        if (str_ends_with($name, 'Paginator')) {
            return new ModelPaginator(substr($name, 1, -9));
        }

        /*
        if (str_ends_with($name, 'EdgeOrderByClause')) {
            return new EdgeOrderByClause($name);
        }*/

        if (str_ends_with($name, 'EdgeReducedColumn')) {
            return new EdgeReducedColumnType($name);
        }

        // TODO: remove this once EdgeSelector has been refactored
        if (str_ends_with($name, 'EdgeColumn')) {
            $nameParts = explode('__', substr($name, 1, -10), 2);
            $model = model($nameParts[0]);
            return static::column($model->{$nameParts[1]});
        }

        if (str_ends_with($name, 'WhereConditions')) {
            return new WhereConditions(substr($name, 1, -15));
        }

        // TODO: probably can be removed after importjob, exportjob and history are models
        if (str_ends_with($name, 'OrderByClause')) {
            return new OrderByClause(substr($name, 1, -13));
        }

        if (str_ends_with($name, 'EdgeReducedSelector')) {
            return new EdgeReducedSelectorType($name);
        }
        if (str_ends_with($name, 'EdgeSelector')) {
            return new EdgeSelectorType($name);
        }
        if (str_ends_with($name, 'EdgeReducedColumnMapping')) {
            return new EdgeReducedColumnMappingType($name);
        }


        // TODO: this can be removed after EdgeSelector has been refactored
        if (str_ends_with($name, 'EdgeColumnMapping')) {
            $nameParts = explode('__', substr($name, 1, -17), 2);
            $model = model($nameParts[0]);
            return static::columnMapping($model->{$nameParts[1]});
        }

        if (str_ends_with($name, 'Column')) {
            // TODO: remove this once job+history are models
            return static::column(substr($name, 1, -6));
        }

        if (str_ends_with($name, 'Enum')) {
            return new DynamicEnumType($name);
        }
        if ($name !== '_Job' && str_ends_with($name, 'Job')) {
            return new JobType($name);
        }
        if (str_ends_with($name, 'ImportPreview')) {
            return new ImportPreviewType($name);
        }
        throw new RuntimeException('Unhandled createDynamic: ' . $name);
    }

    /**
     * @param BaseType $type
     * @return BaseType
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
            Field::COUNTRY_CODE => self::get('_CountryCode'),
            Field::CURRENCY_CODE => self::get('_CurrencyCode'),
            Field::LANGUAGE_CODE => self::get('_LanguageCode'),
            Field::LOCALE_CODE => self::get('_LocaleCode'),
            Field::ENUM => self::get('_' . $field->namekey . 'Enum'),
            Field::DATE_TIME, Field::CREATED_AT, Field::DELETED_AT, Field::UPDATED_AT => self::get('_DateTime'),
            Field::DATE => self::get('_Date'),
            Field::TIME => self::get('_Time'),
            Field::TIMEZONE_OFFSET => self::get('_TimezoneOffset'),
            Field::FILE => $input ? self::get('_File') : self::get('_FileExport'),
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
        /** @var ModelInput $type */
        $type = static::cache(new ModelInput($name));
        return $type;
    }

    public static function column(BaseType|string|Relation $wrappedType): ModelColumn|EnumType|NullableType|EdgeColumn
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
            /** @var EdgeColumn $type */
            $type = static::cache(new EdgeColumn($wrappedType));
            return $type;
        }
        if (is_string($wrappedType)) {
            $name = $wrappedType;
        } else {
            $name = $wrappedType->name;
        }
        /** @var ModelColumn $type */
        $type = static::cache(new ModelColumn($name));
        return $type;
    }

    public static function columnMapping(string|Relation $model): ModelColumnMapping|EdgeColumnMapping
    {
        if (is_string($model)) {
            /** @var ModelColumnMapping $type */
            $type = static::cache(new ModelColumnMapping($model));
            return $type;
        }
        /** @var EdgeColumnMapping $type */
        $type = static::cache(new EdgeColumnMapping($model));
        return $type;
    }

    public static function orderByClause(string|Relation $name): OrderByClause|EdgeOrderByClause
    {
        if (is_string($name)) {
            /** @var OrderByClause $type */
            $type = static::cache(new OrderByClause($name));
            return $type;
        }
        /** @var EdgeOrderByClause $type */
        $type = static::cache(new EdgeOrderByClause($name));
        return $type;
    }

    public static function relation(Relation $relation): ?NullableType
    {
        if ($relation->type === Relation::BELONGS_TO) {
            /** @var ModelRelation $type */
            $type = static::cache(new ModelRelation($relation));
            return $type;
        }
        if ($relation->type === Relation::BELONGS_TO_MANY) {
            /** @var ModelManyRelation $type */
            $type = static::cache(new ModelManyRelation($relation));
            return $type;
        }
        return null;
    }

}
