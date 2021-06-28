<?php


namespace Mrap\GraphCool\Tests\Model;


use Mrap\GraphCool\Model\Relation;
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
            self::assertEquals($type, $relation->type, 'Relation::' . $method . '() produced the wrong type');
            self::assertEquals($classname, $relation->classname);
            self::assertEquals($shortClassname, $relation->name);
        }
        foreach ($this->types as $method => $type) {
            $relation = Relation::$method($shortClassname);
            self::assertEquals($type, $relation->type, 'Relation::' . $method . '() produced the wrong type');
            self::assertEquals($shortClassname, $relation->classname);
            self::assertEquals($shortClassname, $relation->name);
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
            self::assertEquals($relation, $relation2, 'Relation::' . $method . '()->default() does not comply to fluent interface');
        }
    }


}