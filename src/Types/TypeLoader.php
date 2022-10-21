<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Types;

use GraphQL\Type\Definition\NullableType;
use GraphQL\Type\Definition\Type;
use MLL\GraphQLScalars\MixedScalar;
use Mrap\GraphCool\Definition\Field;
use Mrap\GraphCool\Types\Enums\ColumnType;
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
use Mrap\GraphCool\Types\Inputs\ModelInputType;
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

/**
 * @deprecated
 */
class TypeLoader
{
    /** @var string[] */
    protected static array $registry = [];

    /** @var Type[]  */
    protected array $types = [];

    public function __construct()
    {
        self::register('_PaginatorInfo', PaginatorInfoType::class);
        self::register('_SQLOperator', SQLOperatorType::class);
        self::register('_CountryCode', CountryCodeEnumType::class);
        self::register('_LanguageCode', LanguageEnumType::class);
        self::register('_CurrencyCode', CurrencyEnumType::class);
        self::register('_LocaleCode', LocaleEnumType::class);
        self::register('_SortOrder', SortOrderEnumType::class);
        self::register('_FileExport', FileExportType::class);
        self::register('_ExportFile', SheetFileEnumType::class);
        self::register('_ImportSummary', ImportSummaryType::class);
        self::register('_ImportError', ImportErrorType::class);
        self::register('_Result', ResultType::class);
        self::register('_DateTime', DateTime::class);
        self::register('_Date', Date::class);
        self::register('_Time', Time::class);
        self::register('_TimezoneOffset', TimezoneOffset::class);
        self::register('Mixed', MixedScalar::class);
        self::register('_UpdateManyResult', UpdateManyResult::class);
        self::register('_RelationUpdateMode', RelationUpdateModeEnum::class);
        self::register('_Upload', Upload::class);
        self::register('_File', FileType::class);
        self::register('_Job_Column', JobColumnEnumType::class);
        self::register('_Job_Status', JobStatusEnumType::class);
        self::register('_History_Column', HistoryColumnEnumType::class);
        self::register('_History_ChangeType', HistoryChangeTypeEnumType::class);
        self::register('_History', HistoryType::class);
        self::register('_Permission', PermissionEnumType::class);
        self::register('_Entity', EntityEnumType::class);
        self::register('_WhereMode', WhereModeEnumType::class);
        self::register('_Model', ModelEnumType::class);
    }

    public static function register(string $name, string $classname): void
    {
        static::$registry[$name] = $classname;
    }

    public function loadForField(Field $field, string $name = null, bool $input = false): NullableType
    {
        return match ($field->type) {
            default => Type::string(),
            Type::BOOLEAN => Type::boolean(),
            Type::FLOAT, Field::DECIMAL => Type::float(),
            Type::ID => Type::id(),
            Type::INT, Field::AUTO_INCREMENT => Type::int(),
            Field::COUNTRY_CODE => $this->load('_CountryCode')(),
            Field::CURRENCY_CODE => $this->load('_CurrencyCode')(),
            Field::LANGUAGE_CODE => $this->load('_LanguageCode')(),
            Field::LOCALE_CODE => $this->load('_LocaleCode')(),
            Field::ENUM => $this->load('_' . $name . 'Enum')(),
            Field::DATE_TIME, Field::CREATED_AT, Field::DELETED_AT, Field::UPDATED_AT => $this->load('_DateTime')(),
            Field::DATE => $this->load('_Date')(),
            Field::TIME => $this->load('_Time')(),
            Field::TIMEZONE_OFFSET => $this->load('_TimezoneOffset')(),
            Field::FILE => $this->loadFileType($input),
        };
    }

    protected function loadFileType(bool $input = false): Type
    {
        return match($input) {
            true => $this->load('_File')(),
            false => $this->load('_FileExport')()
        };
    }

    public function load(string $name): callable
    {
        return function () use ($name) {
            return \Mrap\GraphCool\Types\Type::get($name);
        };
    }

    public function create(string $name): NullableType
    {
        if (isset(static::$registry[$name])) {
            $classname = static::$registry[$name];
            if ($name === '_ImportSummary' || $name === '_File' || $name === '_History') {
                return new $classname($this);
            }
            return new $classname();
        }
        if (str_starts_with($name, '_')) {
            return $this->createSpecial($name);
        }
        return new ModelType($name, $this);
    }

    protected function createSpecial(string $name): NullableType
    {
        if (str_ends_with($name, 'Paginator')) {
            return new PaginatorType($name, $this);
        }
        if (str_ends_with($name, 'Edges')) {
            return new EdgesType($name, $this);
        }
        if (str_ends_with($name, 'Edge')) {
            return new EdgeType($name, $this);
        }
        if (str_ends_with($name, 'EdgeOrderByClause')) {
            return new EdgeOrderByClauseType($name, $this);
        }
        if (str_ends_with($name, 'EdgeReducedColumn')) {
            return new EdgeReducedColumnType($name, $this);
        }
        if (str_ends_with($name, 'EdgeColumn')) {
            return new EdgeColumnType($name, $this);
        }
        if (str_ends_with($name, 'WhereConditions')) {
            return new WhereInputType($name, $this);
        }
        if (str_ends_with($name, 'OrderByClause')) {
            return new OrderByClauseType($name, $this);
        }
        if (str_ends_with($name, 'EdgeReducedSelector')) {
            return new EdgeReducedSelectorType($name, $this);
        }
        if (str_ends_with($name, 'EdgeSelector')) {
            return new EdgeSelectorType($name, $this);
        }
        if (str_ends_with($name, 'EdgeReducedColumnMapping')) {
            return new EdgeReducedColumnMappingType($name, $this);
        }
        if (str_ends_with($name, 'EdgeColumnMapping')) {
            return new EdgeColumnMappingType($name, $this);
        }
        if (str_ends_with($name, 'ColumnMapping')) {
            return new ColumnMappingType($name);
        }
        if (str_ends_with($name, 'Column')) {
            return new ColumnType($name, $this);
        }
        if (str_ends_with($name, 'ManyRelation')) {
            return new EdgeManyInputType($name, $this);
        }
        if (str_ends_with($name, 'Relation')) {
            return new EdgeInputType($name, $this);
        }
        if (str_ends_with($name, 'Enum')) {
            return new DynamicEnumType($name, $this);
        }
        if (str_ends_with($name, 'Input')) {
            return new ModelInputType($name, $this);
        }
        if (str_ends_with($name, 'Job') && $name !== '_Job') {
            return new JobType($name, $this);
        }
        if (str_ends_with($name, 'ImportPreview')) {
            return new ImportPreviewType($name, $this);
        }
        throw new RuntimeException('unhandled createSpecial: ' . $name);
    }


}