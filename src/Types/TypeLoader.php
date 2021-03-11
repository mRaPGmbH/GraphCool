<?php
declare(strict_types=1);

namespace Mrap\GraphCool\Types;

use GraphQL\Type\Definition\Type;
use Mrap\GraphCool\Model\Field;
use Mrap\GraphCool\Types\Enums\CurrencyEnumType;
use Mrap\GraphCool\Types\Enums\DynamicEnumType;
use Mrap\GraphCool\Types\Enums\CountryCodeEnumType;
use Mrap\GraphCool\Types\Enums\EdgeColumnType;
use Mrap\GraphCool\Types\Enums\LanguageEnumType;
use Mrap\GraphCool\Types\Enums\LocaleEnumType;
use Mrap\GraphCool\Types\Enums\ResultType;
use Mrap\GraphCool\Types\Enums\SheetFileEnumType;
use Mrap\GraphCool\Types\Enums\SortOrderEnumType;
use Mrap\GraphCool\Types\Enums\ColumnType;
use Mrap\GraphCool\Types\Inputs\EdgeExportColumnType;
use Mrap\GraphCool\Types\Inputs\EdgeInputType;
use Mrap\GraphCool\Types\Inputs\EdgeOrderByClauseType;
use Mrap\GraphCool\Types\Inputs\EdgeSelectorType;
use Mrap\GraphCool\Types\Inputs\ExportColumnType;
use Mrap\GraphCool\Types\Inputs\OrderByClauseType;
use Mrap\GraphCool\Types\Enums\SQLOperatorType;
use Mrap\GraphCool\Types\Inputs\WhereInputType;
use Mrap\GraphCool\Types\Objects\EdgesType;
use Mrap\GraphCool\Types\Objects\EdgeType;
use Mrap\GraphCool\Types\Objects\FileExportType;
use Mrap\GraphCool\Types\Objects\ImportSummaryType;
use Mrap\GraphCool\Types\Objects\ModelType;
use Mrap\GraphCool\Types\Objects\PaginatorInfoType;
use Mrap\GraphCool\Types\Objects\PaginatorType;
use Mrap\GraphCool\Types\Scalars\MixedType;

class TypeLoader
{
    protected array $types = [];
    protected static array $registry = [];

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
        self::register('_Result', ResultType::class);
    }

    public function load(string $name, ?ModelType $subType = null, ?ModelType $parentType = null): callable
    {
        return function() use ($name) {
            if (!isset($this->types[$name])) {
                $this->types[$name] = $this->create($name);
            }
            return $this->types[$name];
        };
    }

    public function loadForField(Field $field, string $name = null): Type
    {
        return match ($field->type) {
            default => Type::string(),
            Type::BOOLEAN => Type::boolean(),
            Type::FLOAT, Field::DECIMAL => Type::float(),
            Type::ID => Type::id(),
            Type::INT => Type::int(),
            Field::COUNTRY_CODE => $this->load('_CountryCode')(),
            Field::CURRENCY_CODE => $this->load('_CurrencyCode')(),
            Field::LANGUAGE_CODE => $this->load('_LanguageCode')(),
            Field::LOCALE_CODE => $this->load('_LocaleCode')(),
            Field::ENUM => $this->loadEnumType($name, $field),
        };
    }

    protected function loadEnumType(string $name, Field $field): DynamicEnumType
    {
        $key = '_' . $name . 'Enum';
        if (!isset($this->types[$key])) {
            $this->types[$key] = new DynamicEnumType($key, $field);
        }
        return $this->types[$key];
    }

    public static function register($name, $classname): void
    {
        static::$registry[$name] = $classname;
    }


    protected function create(string $name, ?ModelType $subType = null, ?ModelType $parentType = null): Type
    {
        if (isset(static::$registry[$name])) {
            $classname = static::$registry[$name];
            return new $classname();
        }
        if (str_starts_with($name, '_')) {
            return $this->createSpecial($name);
        }
        return new ModelType($name, $this);
    }

    protected function createSpecial(string $name, ?ModelType $subType = null, ?ModelType $parentType = null): Type
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
        if (str_ends_with($name, 'EdgeColumn')) {
            return new EdgeColumnType($name, $this);
        }
        if (str_ends_with($name, 'WhereConditions')) {
            return new WhereInputType($name, $this);
        }
        if (str_ends_with($name, 'OrderByClause')) {
            return new OrderByClauseType($name, $this);
        }
        if (str_ends_with($name, 'EdgeSelector')) {
            return new EdgeSelectorType($name, $this);
        }
        if (str_ends_with($name, 'EdgeExportColumn')) {
            return new EdgeExportColumnType($name, $this);
        }
        if (str_ends_with($name, 'ExportColumn')) {
            return new ExportColumnType($name, $this);
        }
        if (str_ends_with($name, 'Column')) {
            return new ColumnType($name, $this);
        }
        if (substr($name, -8) === 'Relation') {
            return new EdgeInputType($name, $this);
        }

        throw new \Exception('unhandled createSpecial: '.$name);
    }


}