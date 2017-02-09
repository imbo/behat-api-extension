<?php
namespace Imbo\BehatApiExtension\ArrayContainsComparator\Matcher;

use PHPUnit_Framework_TestCase;

/**
 * @coversDefaultClass Imbo\BehatApiExtension\ArrayContainsComparator\Matcher\VariableType
 * @testdox Variable type matcher
 */
class VariableTypeTest extends PHPUnit_Framework_TestCase {
    /**
     * @var VariableType
     */
    private $matcher;

    /**
     * Set up matcher instance
     */
    public function setup() {
        $this->matcher = new VariableType();
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getValuesAndTypes() {
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
        ];
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getInvalidMatches() {
        return [
            [
                'value' => 123,
                'type' => 'string',
                'message' => 'Expected type "string", got "integer".'
            ],
            [
                'value' => '123',
                'type' => 'integer',
                'message' => 'Expected type "integer", got "string".'
            ],
            [
                'value' => [1, 2, 3],
                'type' => 'object',
                'message' => 'Expected type "object", got "array".'
            ],
            [
                'value' => ['foo' => 'bar'],
                'type' => 'array',
                'message' => 'Expected type "array", got "object".'
            ],
        ];
    }

    /**
     * @dataProvider getValuesAndTypes
     * @covers ::match
     * @covers ::normalizeType
     *
     * @param mixed $value
     * @param string $type
     */
    public function testCanMatchValuesOfType($value, $type) {
        $this->assertNull(
            $this->matcher->match($value, $type),
            'Matcher is supposed to return null.'
        );
    }

    /**
     * @covers ::getName
     */
    public function testReturnsCorrectName() {
        $this->assertSame('variableType', $this->matcher->getName());
    }

    /**
     * @covers ::match
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid type for the @variableType matcher: "resource"
     */
    public function testThrowsExceptionWhenGivenInvalidType() {
        $this->matcher->match('foo', 'resource');
    }

    /**
     * @dataProvider getInvalidMatches
     * @covers ::match
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Expected type "%s", got "%s".
     *
     * @param mixed $value
     * @param string $type
     * @param string $message
     */
    public function testThrowsExceptionWhenTypeOfValueDoesNotMatchExpectedType($value, $type, $message) {
        $this->expectExceptionMessage($message);
        $this->matcher->match($value, $type);
    }
}
