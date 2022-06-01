<?php


namespace Mrap\GraphCool\Tests\Definition;


use Mrap\GraphCool\Definition\Relation;
use Mrap\GraphCool\Tests\TestCase;

class RelationTest extends TestCase
{

    protected $types = [
        'belongsTo' => Relation::BELONGS_TO,
        'belongsToMany' => Relation::BELONGS_TO_MANY,
        'hasMany' => Relation::HAS_MANY,
        'hasOne' => Relation::HAS_ONE,
    ];

    public function testConstructors(): void
    {
        $classname = __CLASS__;
        $shortClassname = 'RelationTest';
        foreach ($this->types as $method => $type) {
            $relation = Relation::$method($classname);
            self::assertSame($type, $relation->type, 'Relation::' . $method . '() produced the wrong type');
            self::assertSame($classname, $relation->classname);
            self::assertSame($shortClassname, $relation->name);
        }
        foreach ($this->types as $method => $type) {
            $relation = Relation::$method($shortClassname);
            self::assertSame($type, $relation->type, 'Relation::' . $method . '() produced the wrong type');
            self::assertSame($shortClassname, $relation->classname);
            self::assertSame($shortClassname, $relation->name);
        }
    }

    public function testNullable(): void
    {
        foreach ($this->types as $method => $type) {
            /** @var Relation $relation */
            $relation = Relation::$method(__CLASS__);
            self::assertFalse($relation->null, 'Relation::' . $method . '() was nullable by default');
            $relation2 = $relation->nullable();
            self::assertTrue($relation->null, 'Relation::' . $method . '()->nullable() didn\'t set nullable');
            self::assertSame($relation, $relation2, 'Relation::' . $method . '()->default() does not comply to fluent interface');
        }
    }


}