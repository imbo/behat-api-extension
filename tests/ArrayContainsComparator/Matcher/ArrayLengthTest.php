<?php
namespace Imbo\BehatApiExtension\ArrayContainsComparator\Matcher;

use PHPUnit_Framework_TestCase;

/**
 * @coversDefaultClass Imbo\BehatApiExtension\ArrayContainsComparator\Matcher\ArrayLength
 * @testdox Array length matcher
 */
class ArrayLengthTest extends PHPUnit_Framework_TestCase {
    /**
     * @var ArrayLength
     */
    private $matcher;

    /**
     * Set up matcher instance
     */
    public function setup() {
        $this->matcher = new ArrayLength();
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getArraysAndLengths() {
        return [
            [
                'list' => [],
                'length' => 0,
            ],
            [
                'list' => [1, 2, 3],
                'length' => 3,
            ],
            [
                'list' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
                'length' => 10,
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
                'message' => '@arrayLength function can only be used with array values, got "integer".',
            ],
            [
                'value' => '123',
                'message' => '@arrayLength function can only be used with array values, got "string".',
            ],
            [
                'value' => ['foo' => 'bar'],
                'message' => '@arrayLength function can only be used with array values, got "object".',
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
                'array' => [1, 2],
                'maxLength' => 1,
                'message' => '@arrayLength: Wrong length for array, expected 1, got 2.',
            ],
            [
                'array' => [],
                'maxLength' => 2,
                'message' => '@arrayLength: Wrong length for array, expected 2, got 0.',
            ],
        ];
    }

    /**
     * @dataProvider getArraysAndLengths
     * @covers ::match
     *
     * @param array $array
     * @param int $length
     */
    public function testCanMatchLengthOfArrays(array $array, $length) {
        $this->assertNull(
            $this->matcher->match($array, $length),
            'Matcher is supposed to return null.'
        );
    }

    /**
     * @covers ::getName
     */
    public function testReturnsCorrectName() {
        $this->assertSame('arrayLength', $this->matcher->getName());
    }

    /**
     * @dataProvider getInvalidValues
     * @covers ::match
     * @expectedException InvalidArgumentException
     *
     * @param mixed $value
     * @param string $message
     */
    public function testThrowsExceptionWhenMatchingLengthAgainstAnythingOtherThanAnArray($value, $message) {
        $this->expectExceptionMessage($message);
        $this->matcher->match($value, 123);
    }

    /**
     * @dataProvider getValuesThatFail
     * @covers ::match
     * @expectedException InvalidArgumentException
     *
     * @param array $array
     * @param int $length
     * @param string $message
     */
    public function testThrowsExceptionWhenLengthIsNotCorrect(array $array, $length, $message) {
        $this->expectExceptionMessage($message);
        $this->matcher->match($array, $length);
    }
}
