<?php declare(strict_types=1);
namespace Imbo\BehatApiExtension\ArrayContainsComparator\Matcher;

use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

/**
 * @coversDefaultClass Imbo\BehatApiExtension\ArrayContainsComparator\Matcher\ArrayMinLength
 */
class ArrayMinLengthTest extends TestCase {
    private $matcher;

    public function setup() : void {
        $this->matcher = new ArrayMinLength();
    }

    public function getArraysAndMinLengths() : array {
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

    public function getInvalidValues() : array {
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

    public function getValuesThatFail() : array {
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
     */
    public function testCanMatchMinLengthOfArrays(array $array, int $min) : void {
        $matcher = $this->matcher;
        $this->assertNull(
            $matcher($array, $min),
            'Matcher is supposed to return null.'
        );
    }

    /**
     * @dataProvider getInvalidValues
     * @covers ::__invoke
     */
    public function testThrowsExceptionWhenMatchingAgainstAnythingOtherThanAnArray($value, string $message) : void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        $matcher = $this->matcher;
        $matcher($value, 123);
    }

    /**
     * @dataProvider getValuesThatFail
     * @covers ::__invoke
     */
    public function testThrowsExceptionWhenLengthIsTooLong(array $array, int $minLength, string $message) : void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        $matcher = $this->matcher;
        $matcher($array, $minLength);
    }
}
