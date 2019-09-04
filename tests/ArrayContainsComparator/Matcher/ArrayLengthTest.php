<?php declare(strict_types=1);
namespace Imbo\BehatApiExtension\ArrayContainsComparator\Matcher;

use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

/**
 * @coversDefaultClass Imbo\BehatApiExtension\ArrayContainsComparator\Matcher\ArrayLength
 */
class ArrayLengthTest extends TestCase {
    private $matcher;

    public function setup() : void {
        $this->matcher = new ArrayLength();
    }

    public function getArraysAndLengths() : array {
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
     */
    public function testCanMatchLengthOfArrays(array $array, int $length) : void {
        $matcher = $this->matcher;

        $this->assertNull(
            $matcher($array, $length),
            'Matcher is supposed to return null.'
        );
    }

    /**
     * @dataProvider getInvalidValues
     * @covers ::__invoke
     */
    public function testThrowsExceptionWhenMatchingLengthAgainstAnythingOtherThanAnArray($value, string $message) : void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        $matcher = $this->matcher;
        $matcher($value, 123);
    }

    /**
     * @dataProvider getValuesThatFail
     * @covers ::__invoke
     */
    public function testThrowsExceptionWhenLengthIsNotCorrect(array $array, int $length, string $message) : void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        $matcher = $this->matcher;
        $matcher($array, $length);
    }
}
