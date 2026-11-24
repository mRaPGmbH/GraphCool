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

    protected function tearDown(): void
    {
        Upload::$isUploadedFile = 'is_uploaded_file';
        parent::tearDown();
    }

    public function testParseValue(): void
    {
        $file = ['tmp_name' => '/tmp/phpUpload', 'name' => 'x.txt', 'size' => 1, 'error' => 0, 'type' => 'text/plain'];
        Upload::$isUploadedFile = static fn(string $path): bool => $path === '/tmp/phpUpload';
        $upload = new Upload();
        self::assertSame($file, $upload->parseValue($file));
        self::assertNull($upload->parseValue(null));
    }

    public function testParseValueRejectsServerPathFromJsonVariables(): void
    {
        $this->expectException(Error::class);
        $upload = new Upload();
        $upload->parseValue(['tmp_name' => '/etc/hostname', 'name' => 'x', 'size' => 1, 'error' => 0, 'type' => 'text/plain']);
    }

    public function testParseValueRejectsNonFileValues(): void
    {
        $upload = new Upload();
        foreach (['anything', ['name' => 'x'], ['tmp_name' => ['/a', '/b']]] as $value) {
            try {
                $upload->parseValue($value);
                self::fail('Expected an Error for ' . json_encode($value));
            } catch (Error) {
                self::addToAssertionCount(1);
            }
        }
    }

    public function testParseLiteral(): void
    {
        $upload = new Upload();
        $node = new NullValueNode([]);
        self::assertNull($upload->parseLiteral($node));
    }

}