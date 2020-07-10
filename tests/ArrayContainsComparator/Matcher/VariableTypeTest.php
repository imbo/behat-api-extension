<?php declare(strict_types=1);
namespace Imbo\BehatApiExtension\ArrayContainsComparator\Matcher;

use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

/**
 * @coversDefaultClass Imbo\BehatApiExtension\ArrayContainsComparator\Matcher\VariableType
 */
class VariableTypeTest extends TestCase {
    /** @var VariableType */
    private $matcher;

    public function setUp() : void {
        $this->matcher = new VariableType();
    }

    /**
     * @return array<string, array{value: mixed, type: string}>
     */
    public function getValuesAndTypes() : array {
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
            'scalar (boolean true)' => [
                'value' => true,
                'type' => 'scalar',
            ],
            'scalar (boolean false)' => [
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
            'boolean (any)' => [
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
     * @return array{value: mixed, type: string, message: string}[]
     */
    public function getInvalidMatches() : array {
        return [
            [
                'value' => 123,
                'type' => 'string',
                'message' => 'Expected variable type "string", got "integer".'
            ],
            [
                'value' => '123',
                'type' => 'integer',
                'message' => 'Expected variable type "integer", got "string".'
            ],
            [
                'value' => [1, 2, 3],
                'type' => 'object',
                'message' => 'Expected variable type "object", got "array".'
            ],
            [
                'value' => ['foo' => 'bar'],
                'type' => 'array',
                'message' => 'Expected variable type "array", got "object".'
            ],
        ];
    }

    /**
     * @dataProvider getValuesAndTypes
     * @covers ::__invoke
     * @covers ::normalizeTypes
     * @param mixed $value
     */
    public function testCanMatchValuesOfType($value, string $type) : void {
        $matcher = $this->matcher;
        $this->assertTrue(
            $matcher($value, $type),
            'Matcher is supposed to return true.'
        );
    }

    /**
     * @covers ::__invoke
     */
    public function testThrowsExceptionWhenGivenInvalidType() : void {
        $matcher = $this->matcher;
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported variable type: "resource".');
        $matcher('foo', 'resource');
    }

    /**
     * @dataProvider getInvalidMatches
     * @covers ::__invoke
     * @param mixed $value
     */
    public function testThrowsExceptionWhenTypeOfValueDoesNotMatchExpectedType($value, string $type, string $message) : void {
        $matcher = $this->matcher;
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        $matcher($value, $type);
    }
}
