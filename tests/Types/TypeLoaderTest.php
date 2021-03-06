<?php


namespace Types;

use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\Type;
use MLL\GraphQLScalars\MixedScalar;
use Mrap\GraphCool\Model\Field;
use Mrap\GraphCool\Tests\TestCase;
use Mrap\GraphCool\Types\Enums\ColumnType;
use Mrap\GraphCool\Types\Enums\DynamicEnumType;
use Mrap\GraphCool\Types\Enums\EdgeColumnType;
use Mrap\GraphCool\Types\Enums\EdgeReducedColumnType;
use Mrap\GraphCool\Types\Inputs\ColumnMappingType;
use Mrap\GraphCool\Types\Inputs\EdgeColumnMappingType;
use Mrap\GraphCool\Types\Inputs\EdgeInputType;
use Mrap\GraphCool\Types\Inputs\EdgeManyInputType;
use Mrap\GraphCool\Types\Inputs\EdgeOrderByClauseType;
use Mrap\GraphCool\Types\Inputs\EdgeReducedColumnMappingType;
use Mrap\GraphCool\Types\Inputs\EdgeReducedSelectorType;
use Mrap\GraphCool\Types\Inputs\EdgeSelectorType;
use Mrap\GraphCool\Types\Inputs\ModelInputType;
use Mrap\GraphCool\Types\Inputs\OrderByClauseType;
use Mrap\GraphCool\Types\Inputs\WhereInputType;
use Mrap\GraphCool\Types\Objects\EdgesType;
use Mrap\GraphCool\Types\Objects\EdgeType;
use Mrap\GraphCool\Types\Objects\PaginatorType;
use Mrap\GraphCool\Types\TypeLoader;

class TypeLoaderTest extends TestCase
{

    public function testConstructor(): void
    {
        $typeLoader = new TypeLoader();
        self::assertInstanceOf(TypeLoader::class, $typeLoader);
    }

    public function testLoad(): void
    {
        $typeLoader = new TypeLoader();
        $mixed = $typeLoader->load('Mixed')();
        self::assertInstanceOf(MixedScalar::class, $mixed);
    }

    public function testLoadForBoolean(): void
    {
        $typeLoader = new TypeLoader();
        $result = $typeLoader->loadForField(Field::bool());
        self::assertInstanceOf(ScalarType::class, $result);
    }

    public function testLoadForFloat(): void
    {
        $typeLoader = new TypeLoader();
        $result = $typeLoader->loadForField(Field::float());
        self::assertInstanceOf(ScalarType::class, $result);
    }

    public function testLoadForDecimal(): void
    {
        $typeLoader = new TypeLoader();
        $result = $typeLoader->loadForField(Field::decimal(2));
        self::assertInstanceOf(ScalarType::class, $result);
    }

    public function testLoadForId(): void
    {
        $typeLoader = new TypeLoader();
        $result = $typeLoader->loadForField(Field::id());
        self::assertInstanceOf(ScalarType::class, $result);
    }

    public function testLoadForInt(): void
    {
        $typeLoader = new TypeLoader();
        $result = $typeLoader->loadForField(Field::int());
        self::assertInstanceOf(ScalarType::class, $result);
    }

    public function testLoadForCountryCode(): void
    {
        $typeLoader = new TypeLoader();
        $result = $typeLoader->loadForField(Field::countryCode());
        self::assertInstanceOf(EnumType::class, $result);
    }

    public function testLoadForCurrencyCode(): void
    {
        $typeLoader = new TypeLoader();
        $result = $typeLoader->loadForField(Field::currencyCode());
        self::assertInstanceOf(EnumType::class, $result);
    }

    public function testLoadForLanguageCode(): void
    {
        $typeLoader = new TypeLoader();
        $result = $typeLoader->loadForField(Field::languageCode());
        self::assertInstanceOf(EnumType::class, $result);
    }

    public function testLoadForLocaleCode(): void
    {
        $typeLoader = new TypeLoader();
        $result = $typeLoader->loadForField(Field::localeCode());
        self::assertInstanceOf(EnumType::class, $result);
    }

    public function testLoadForEnum(): void
    {
        $typeLoader = new TypeLoader();
        $result = $typeLoader->loadForField(Field::enum(['x']), 'DummyModel__enum');
        self::assertInstanceOf(EnumType::class, $result);
    }

    public function testCreatePaginator(): void
    {
        $typeLoader = new TypeLoader();
        $result = $typeLoader->load('_DummyModelPaginator')();
        self::assertInstanceOf(PaginatorType::class, $result);
    }

    public function testCreateEdges(): void
    {
        $typeLoader = new TypeLoader();
        $result = $typeLoader->load('_DummyModel__belongs_to_manyEdges')();
        self::assertInstanceOf(EdgesType::class, $result);
    }

    public function testCreateEdge(): void
    {
        $typeLoader = new TypeLoader();
        $result = $typeLoader->load('_DummyModel__belongs_to_manyEdge')();
        self::assertInstanceOf(EdgeType::class, $result);
    }

    public function testCreateEdgeOrderByClause(): void
    {
        $typeLoader = new TypeLoader();
        $result = $typeLoader->load('_DummyModel__belongs_to_manyEdgeOrderByClause')();
        self::assertInstanceOf(EdgeOrderByClauseType::class, $result);
    }

    public function testCreateEdgeReducedColumn(): void
    {
        $typeLoader = new TypeLoader();
        $result = $typeLoader->load('_DummyModel__belongs_to_manyEdgeReducedColumn')();
        self::assertInstanceOf(EdgeReducedColumnType::class, $result);
    }

    public function testCreateEdgeColumn(): void
    {
        $typeLoader = new TypeLoader();
        $result = $typeLoader->load('_DummyModel__belongs_to_manyEdgeColumn')();
        self::assertInstanceOf(EdgeColumnType::class, $result);
    }

    public function testCreateWhereConditions(): void
    {
        $typeLoader = new TypeLoader();
        $result = $typeLoader->load('_DummyModelWhereConditions')();
        self::assertInstanceOf(WhereInputType::class, $result);
    }

    public function testCreateOrderByClause(): void
    {
        $typeLoader = new TypeLoader();
        $result = $typeLoader->load('_DummyModelOrderByClause')();
        self::assertInstanceOf(OrderByClauseType::class, $result);
    }

    public function testCreateEdgeReducedSelector(): void
    {
        $typeLoader = new TypeLoader();
        $result = $typeLoader->load('_DummyModel__belongs_to_manyEdgeReducedSelector')();
        self::assertInstanceOf(EdgeReducedSelectorType::class, $result);
    }

    public function testCreateEdgeSelector(): void
    {
        $typeLoader = new TypeLoader();
        $result = $typeLoader->load('_DummyModel__belongs_to_manyEdgeSelector')();
        self::assertInstanceOf(EdgeSelectorType::class, $result);
    }

    public function testCreateEdgeReducedColumnMapping(): void
    {
        $typeLoader = new TypeLoader();
        $result = $typeLoader->load('_DummyModel__belongs_to_manyEdgeReducedColumnMapping')();
        self::assertInstanceOf(EdgeReducedColumnMappingType::class, $result);
    }

    public function testCreateEdgeColumnMapping(): void
    {
        $typeLoader = new TypeLoader();
        $result = $typeLoader->load('_DummyModel__belongs_to_manyEdgeColumnMapping')();
        self::assertInstanceOf(EdgeColumnMappingType::class, $result);
    }

    public function testCreateColumnMapping(): void
    {
        $typeLoader = new TypeLoader();
        $result = $typeLoader->load('_DummyModelColumnMapping')();
        self::assertInstanceOf(ColumnMappingType::class, $result);
    }

    public function testCreateColumn(): void
    {
        $typeLoader = new TypeLoader();
        $result = $typeLoader->load('_DummyModelColumn')();
        self::assertInstanceOf(ColumnType::class, $result);
    }

    public function testCreateManyRelation(): void
    {
        $typeLoader = new TypeLoader();
        $result = $typeLoader->load('_DummyModel__belongs_to_manyManyRelation')();
        self::assertInstanceOf(EdgeManyInputType::class, $result);
    }

    public function testCreateRelation(): void
    {
        $typeLoader = new TypeLoader();
        $result = $typeLoader->load('_DummyModel__belongs_toRelation')();
        self::assertInstanceOf(EdgeInputType::class, $result);
    }

    public function testCreateEnum(): void
    {
        $typeLoader = new TypeLoader();
        $result = $typeLoader->load('_DummyModel__enumEnum')();
        self::assertInstanceOf(DynamicEnumType::class, $result);
    }

    public function testCreateInput(): void
    {
        $typeLoader = new TypeLoader();
        $result = $typeLoader->load('_DummyModelInput')();
        self::assertInstanceOf(ModelInputType::class, $result);
    }

    public function testCreateError(): void
    {
        $this->expectException(\Exception::class);
        $typeLoader = new TypeLoader();
        $typeLoader->load('_does_not_exist')();
    }



}