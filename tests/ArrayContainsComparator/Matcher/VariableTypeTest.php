<?php
namespace Imbo\BehatApiExtension\ArrayContainsComparator\Matcher;

use PHPUnit_Framework_TestCase;

/**
 * @coversDefaultClass Imbo\BehatApiExtension\ArrayContainsComparator\Matcher\VariableType
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
     * @covers ::normalizeType
     *
     * @param mixed $value
     * @param string $type
     */
    public function testCanMatchValuesOfType($value, $type) {
        $matcher = $this->matcher;
        $this->assertNull(
            $matcher($value, $type),
            'Matcher is supposed to return null.'
        );
    }

    /**
     * @covers ::__invoke
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Unsupported variable type: "resource".
     */
    public function testThrowsExceptionWhenGivenInvalidType() {
        $matcher = $this->matcher;
        $matcher('foo', 'resource');
    }

    /**
     * @dataProvider getInvalidMatches
     * @covers ::__invoke
     * @expectedException InvalidArgumentException
     *
     * @param mixed $value
     * @param string $type
     * @param string $message
     */
    public function testThrowsExceptionWhenTypeOfValueDoesNotMatchExpectedType($value, $type, $message) {
        $this->expectExceptionMessage($message);
        $matcher = $this->matcher;
        $matcher($value, $type);
    }
}
