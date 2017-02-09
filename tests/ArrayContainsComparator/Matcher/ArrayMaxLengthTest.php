<?php
namespace Imbo\BehatApiExtension\ArrayContainsComparator\Matcher;

use PHPUnit_Framework_TestCase;

/**
 * @coversDefaultClass Imbo\BehatApiExtension\ArrayContainsComparator\Matcher\ArrayMaxLength
 * @testdox Array max length matcher
 */
class ArrayMaxLengthTest extends PHPUnit_Framework_TestCase {
    /**
     * @var ArrayMaxLength
     */
    private $matcher;

    /**
     * Set up matcher instance
     */
    public function setup() {
        $this->matcher = new ArrayMaxLength();
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
                'message' => '@arrayMaxLength function can only be used with array values, got "integer".',
            ],
            [
                'value' => '123',
                'message' => '@arrayMaxLength function can only be used with array values, got "string".',
            ],
            [
                'value' => ['foo' => 'bar'],
                'message' => '@arrayMaxLength function can only be used with array values, got "object".',
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
                'maxLength' => 0,
                'message' => '@arrayMaxLength: Wrong length for array, max length is 0, the array has a length of 2.',
            ],
            [
                'array' => [1, 2, 3],
                'maxLength' => 2,
                'message' => '@arrayMaxLength: Wrong length for array, max length is 2, the array has a length of 3.',
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
    public function testCanMatchMaxLengthOfArrays(array $array, $length) {
        $this->assertNull(
            $this->matcher->match($array, $length),
            'Matcher is supposed to return null.'
        );
    }

    /**
     * @covers ::getName
     */
    public function testReturnsCorrectName() {
        $this->assertSame('arrayMaxLength', $this->matcher->getName());
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
     * @param int $maxLength
     * @param string $message
     */
    public function testThrowsExceptionWhenLengthIsTooShort(array $array, $maxLength, $message) {
        $this->expectExceptionMessage($message);
        $this->matcher->match($array, $maxLength);
    }
}
