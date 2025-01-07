<?php declare(strict_types=1);
namespace Imbo\BehatApiExtension\ArrayContainsComparator\Matcher;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(VariableType::class)]
class VariableTypeTest extends TestCase
{
    private VariableType $matcher;

    public function setUp(): void
    {
        $this->matcher = new VariableType();
    }

    /**
     * @return array<string,array{value:mixed,type:string}>
     */
    public static function getValuesAndTypes(): array
    {
        return [
            'int' => [
                'value' => 1,
                'type' => 'integer',
            ],
            'integer' => [
                'value' => 1,
                'type' => 'int',
            ],
            'string' => [
                'value' => 'some string',
                'type' => 'string',
            ],
            'double' => [
                'value' => 1.1,
                'type' => 'double',
            ],
            'float' => [
                'value' => 1.1,
                'type' => 'float',
            ],
            'boolean (true)' => [
                'value' => true,
                'type' => 'boolean',
            ],
            'boolean (false)' => [
                'value' => false,
                'type' => 'boolean',
            ],
            'bool (true)' => [
                'value' => true,
                'type' => 'bool',
            ],
            'bool (false)' => [
                'value' => false,
                'type' => 'bool',
            ],
            'null' => [
                'value' => null,
                'type' => 'null',
            ],
            'scalar (integer)' => [
                'value' => 123,
                'type' => 'scalar',
            ],
            'scalar (double)' => [
                'value' => 1.1,
                'type' => 'scalar',
            ],
            'scalar (string)' => [
                'value' => '123',
                'type' => 'scalar',
            ],
            'scalar (bool true)' => [
                'value' => true,
                'type' => 'scalar',
            ],
            'scalar (bool false)' => [
                'value' => true,
                'type' => 'scalar',
            ],
            'array (list)' => [
                'value' => [1, 2, 3],
                'type' => 'array',
            ],
            'array (object)' => [
                'value' => ['foo' => 'bar'],
                'type' => 'object',
            ],
            'bool (any)' => [
                'value' => true,
                'type' => 'any',
            ],
            'integer (any)' => [
                'value' => 123,
                'type' => 'any',
            ],
            'double (any)' => [
                'value' => 1.1,
                'type' => 'any',
            ],
            'string (any)' => [
                'value' => 'some string',
                'type' => 'any',
            ],
            'array (any)' => [
                'value' => [1, 2, 3],
                'type' => 'any',
            ],
            'object (any)' => [
                'value' => ['foo' => 'bar'],
                'type' => 'any',
            ],
            'int (multiple)' => [
                'value' => 1,
                'type' => 'string|array|integer',
            ],
            'integer (multiple)' => [
                'value' => 1,
                'type' => 'int|bool|double',
            ],
            'string (multiple)' => [
                'value' => 'some string',
                'type' => 'integer | bool | array | string', // spaces are intentional
            ],
        ];
    }

    /**
     * @return array<array{value:mixed,type:string,message:string}>
     */
    public static function getInvalidMatches(): array
    {
        return [
            [
                'value' => 123,
                'type' => 'string',
                'message' => 'Expected variable type "string", got "integer".',
            ],
            [
                'value' => '123',
                'type' => 'integer',
                'message' => 'Expected variable type "integer", got "string".',
            ],
            [
                'value' => [1, 2, 3],
                'type' => 'object',
                'message' => 'Expected variable type "object", got "array".',
            ],
            [
                'value' => ['foo' => 'bar'],
                'type' => 'array',
                'message' => 'Expected variable type "array", got "object".',
            ],
        ];
    }

    #[DataProvider('getValuesAndTypes')]
    public function testCanMatchValuesOfType(mixed $value, string $type): void
    {
        $matcher = $this->matcher;
        $this->assertTrue(
            $matcher($value, $type),
            'Matcher is supposed to return true.',
        );
    }

    public function testThrowsExceptionWhenGivenInvalidType(): void
    {
        $matcher = $this->matcher;
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported variable type: "resource".');
        $matcher('foo', 'resource');
    }

    #[DataProvider('getInvalidMatches')]
    public function testThrowsExceptionWhenTypeOfValueDoesNotMatchExpectedType(mixed $value, string $type, string $message): void
    {
        $matcher = $this->matcher;
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        $matcher($value, $type);
    }
}
