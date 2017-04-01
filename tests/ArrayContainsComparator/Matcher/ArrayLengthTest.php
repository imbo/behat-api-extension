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
                'message' => 'Only numerically indexed arrays are supported, got "integer".',
            ],
            [
                'value' => '123',
                'message' => 'Only numerically indexed arrays are supported, got "string".',
            ],
            [
                'value' => ['foo' => 'bar'],
                'message' => 'Only numerically indexed arrays are supported, got "object".',
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
                'message' => 'Expected array to have exactly 1 entries, actual length: 2.',
            ],
            [
                'array' => [],
                'maxLength' => 2,
                'message' => 'Expected array to have exactly 2 entries, actual length: 0.',
            ],
        ];
    }

    /**
     * @dataProvider getArraysAndLengths
     * @covers ::__invoke
     *
     * @param array $array
     * @param int $length
     */
    public function testCanMatchLengthOfArrays(array $array, $length) {
        $matcher = $this->matcher;

        $this->assertNull(
            $matcher($array, $length),
            'Matcher is supposed to return null.'
        );
    }

    /**
     * @dataProvider getInvalidValues
     * @covers ::__invoke
     * @expectedException InvalidArgumentException
     *
     * @param mixed $value
     * @param string $message
     */
    public function testThrowsExceptionWhenMatchingLengthAgainstAnythingOtherThanAnArray($value, $message) {
        $this->expectExceptionMessage($message);
        $matcher = $this->matcher;
        $matcher($value, 123);
    }

    /**
     * @dataProvider getValuesThatFail
     * @covers ::__invoke
     * @expectedException InvalidArgumentException
     *
     * @param array $array
     * @param int $length
     * @param string $message
     */
    public function testThrowsExceptionWhenLengthIsNotCorrect(array $array, $length, $message) {
        $this->expectExceptionMessage($message);
        $matcher = $this->matcher;
        $matcher($array, $length);
    }
}
