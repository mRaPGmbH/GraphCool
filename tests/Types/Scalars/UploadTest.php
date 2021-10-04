<?php


namespace Mrap\GraphCool\Tests\Types\Scalars;


use GraphQL\Error\Error;
use GraphQL\Language\AST\NullValueNode;
use Mrap\GraphCool\Types\Scalars\Upload;
use Mrap\GraphCool\Tests\TestCase;

class UploadTest extends TestCase
{
    public function testSerialize(): void
    {
        $this->expectException(Error::class);
        $upload = new Upload();
        $upload->serialize('anything');
    }

    public function testParseValue(): void
    {
        $upload = new Upload();
        self::assertSame('anything', $upload->parseValue('anything'));
        self::assertNull($upload->parseValue(null));
    }

    public function testParseLiteral(): void
    {
        $upload = new Upload();
        $node = new NullValueNode([]);
        self::assertNull($upload->parseLiteral($node));
    }

}