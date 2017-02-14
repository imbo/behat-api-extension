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
                'array' => [],
                'minLength' => 2,
                'message' => 'Expected array to have more than or equal to 2 entries, actual length: 0.',
            ],
            [
                'array' => [1, 2, 3],
                'minLength' => 4,
                'message' => 'Expected array to have more than or equal to 4 entries, actual length: 3.',
            ],
        ];
    }

    /**
     * @dataProvider getArraysAndMinLengths
     * @covers ::__invoke
     *
     * @param array $array
     * @param int $min
     */
    public function testCanMatchMinLengthOfArrays(array $array, $min) {
        $matcher = $this->matcher;
        $this->assertNull(
            $matcher($array, $min),
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
    public function testThrowsExceptionWhenMatchingAgainstAnythingOtherThanAnArray($value, $message) {
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
     * @param int $minLength
     * @param string $message
     */
    public function testThrowsExceptionWhenLengthIsTooLong(array $array, $minLength, $message) {
        $this->expectExceptionMessage($message);
        $matcher = $this->matcher;
        $matcher($array, $minLength);
    }
}
