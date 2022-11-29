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
use Mrap\GraphCool\Types\Enums\CountryCodeEnumType;
use Mrap\GraphCool\Types\Enums\CurrencyEnumType;
use Mrap\GraphCool\Types\Enums\DynamicEnumType;
use Mrap\GraphCool\Types\Enums\EdgeColumnType;
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
use Mrap\GraphCool\Types\Inputs\ColumnMappingType;
use Mrap\GraphCool\Types\Inputs\EdgeColumnMappingType;
use Mrap\GraphCool\Types\Inputs\EdgeInputType;
use Mrap\GraphCool\Types\Inputs\EdgeManyInputType;
use Mrap\GraphCool\Types\Inputs\EdgeOrderByClauseType;
use Mrap\GraphCool\Types\Inputs\EdgeReducedColumnMappingType;
use Mrap\GraphCool\Types\Inputs\EdgeReducedSelectorType;
use Mrap\GraphCool\Types\Inputs\EdgeSelectorType;
use Mrap\GraphCool\Types\Inputs\FileType;
use Mrap\GraphCool\Types\Inputs\ModelInput;
use Mrap\GraphCool\Types\Inputs\OrderByClauseType;
use Mrap\GraphCool\Types\Inputs\WhereInputType;
use Mrap\GraphCool\Types\Objects\EdgesType;
use Mrap\GraphCool\Types\Objects\EdgeType;
use Mrap\GraphCool\Types\Objects\FileExportType;
use Mrap\GraphCool\Types\Objects\HistoryType;
use Mrap\GraphCool\Types\Objects\ImportErrorType;
use Mrap\GraphCool\Types\Objects\ImportPreviewType;
use Mrap\GraphCool\Types\Objects\ImportSummaryType;
use Mrap\GraphCool\Types\Objects\JobType;
use Mrap\GraphCool\Types\Objects\ModelType;
use Mrap\GraphCool\Types\Objects\PaginatorInfoType;
use Mrap\GraphCool\Types\Objects\PaginatorType;
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
        if (str_ends_with($name, 'Paginator')) {
            return new PaginatorType($name);
        }
        if (str_ends_with($name, 'Edges')) {
            return new EdgesType($name);
        }
        if (str_ends_with($name, 'Edge')) {
            return new EdgeType($name);
        }
        if (str_ends_with($name, 'EdgeOrderByClause')) {
            return new EdgeOrderByClauseType($name);
        }
        if (str_ends_with($name, 'EdgeReducedColumn')) {
            return new EdgeReducedColumnType($name);
        }
        if (str_ends_with($name, 'EdgeColumn')) {
            return new EdgeColumnType($name);
        }
        if (str_ends_with($name, 'WhereConditions')) {
            return new WhereInputType($name);
        }
        if (str_ends_with($name, 'OrderByClause')) {
            return new OrderByClauseType($name);
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
        if (str_ends_with($name, 'EdgeColumnMapping')) {
            return new EdgeColumnMappingType($name);
        }
        if (str_ends_with($name, 'ColumnMapping')) {
            return new ColumnMappingType($name);
        }

        if (str_ends_with($name, 'Column')) {
            // TODO: remove this once job+history are models
            return static::column(substr($name, 1, -6));
        }

        if (str_ends_with($name, 'ManyRelation')) {
            return new EdgeManyInputType($name);
        }
        if (str_ends_with($name, 'Relation')) {
            return new EdgeInputType($name);
        }
        if (str_ends_with($name, 'Enum')) {
            return new DynamicEnumType($name);
        }
        if (str_ends_with($name, 'Job') && $name !== '_Job') {
            return new JobType($name);
        }
        if (str_ends_with($name, 'ImportPreview')) {
            return new ImportPreviewType($name);
        }
        throw new RuntimeException('Unhandled createDynamic: ' . $name);
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
        $type = new ModelInput($name);
        if (!isset(static::$types[$type->name])) {
            static::$types[$type->name] = $type;
        }
        return static::$types[$type->name];
    }

    public static function column(BaseType|string $wrappedType): ModelColumn|EnumType|NullableType
    {
        if ($wrappedType === 'Job_') {
            // TODO: remove once importjob and exportjob are models
            return static::get('_Job_Column');
        }
        if ($wrappedType === 'History_') {
            // TODO: remove once history is a model
            return static::get('_History_Column');
        }
        if (is_string($wrappedType)) {
            $name = $wrappedType;
        } else {
            $name = $wrappedType->name;
        }
        $type = new ModelColumn($name);
        if (!isset(static::$types[$type->name])) {
            static::$types[$type->name] = $type;
        }
        return static::$types[$type->name];
    }

}
