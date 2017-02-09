<?php
namespace Imbo\BehatApiExtension\ArrayContainsComparator\Matcher;

use PHPUnit_Framework_TestCase;

/**
 * @coversDefaultClass Imbo\BehatApiExtension\ArrayContainsComparator\Matcher\ArrayMinLength
 * @testdox Array min length matcher
 */
class ArrayMinLengthTest extends PHPUnit_Framework_TestCase {
    /**
     * @var ArrayMinLength
     */
    private $matcher;

    /**
     * Set up matcher instance
     */
    public function setup() {
        $this->matcher = new ArrayMinLength();
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getArraysAndMinLengths() {
        return [
            [
                'list' => [],
                'min' => 0,
            ],
            [
                'list' => [1],
                'min' => 0,
            ],
            [
                'list' => [1, 2],
                'min' => 2,
            ],
        ];
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getInvalidValues() {
        return [
            [
                'value' => 123,
                'message' => '@arrayMinLength function can only be used with array values, got "integer".',
            ],
            [
                'value' => '123',
                'message' => '@arrayMinLength function can only be used with array values, got "string".',
            ],
            [
                'value' => ['foo' => 'bar'],
                'message' => '@arrayMinLength function can only be used with array values, got "object".',
            ],
        ];
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getValuesThatFail() {
        return [
            [
                'array' => [],
                'minLength' => 2,
                'message' => '@arrayMinLength: Wrong length for array, min length is 2, the array has a length of 0.',
            ],
            [
                'array' => [1, 2, 3],
                'minLength' => 4,
                'message' => '@arrayMinLength: Wrong length for array, min length is 4, the array has a length of 3.',
            ],
        ];
    }

    /**
     * @dataProvider getArraysAndMinLengths
     * @covers ::match
     *
     * @param array $array
     * @param int $min
     */
    public function testCanMatchMinLengthOfArrays(array $array, $min) {
        $this->assertNull(
            $this->matcher->match($array, $min),
            'Matcher is supposed to return null.'
        );
    }

    /**
     * @covers ::getName
     */
    public function testReturnsCorrectName() {
        $this->assertSame('arrayMinLength', $this->matcher->getName());
    }

    /**
     * @dataProvider getInvalidValues
     * @covers ::match
     * @expectedException InvalidArgumentException
     *
     * @param mixed $value
     * @param string $message
     */
    public function testThrowsExceptionWhenMatchingAgainstAnythingOtherThanAnArray($value, $message) {
        $this->expectExceptionMessage($message);
        $this->matcher->match($value, 123);
    }

    /**
     * @dataProvider getValuesThatFail
     * @covers ::match
     * @expectedException InvalidArgumentException
     *
     * @param array $array
     * @param int $minLength
     * @param string $message
     */
    public function testThrowsExceptionWhenLengthIsTooLong(array $array, $minLength, $message) {
        $this->expectExceptionMessage($message);
        $this->matcher->match($array, $minLength);
    }
}
