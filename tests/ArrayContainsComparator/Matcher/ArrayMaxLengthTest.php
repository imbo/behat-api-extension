<?php declare(strict_types=1);
namespace Imbo\BehatApiExtension\ArrayContainsComparator\Matcher;

use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

/**
 * @coversDefaultClass Imbo\BehatApiExtension\ArrayContainsComparator\Matcher\ArrayMaxLength
 */
class ArrayMaxLengthTest extends TestCase {
    private $matcher;

    public function setup() : void {
        $this->matcher = new ArrayMaxLength();
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
                'maxLength' => 0,
                'message' => 'Expected array to have less than or equal to 0 entries, actual length: 2.',
            ],
            [
                'array' => [1, 2, 3],
                'maxLength' => 2,
                'message' => 'Expected array to have less than or equal to 2 entries, actual length: 3.',
            ],
        ];
    }

    /**
     * @dataProvider getArraysAndLengths
     * @covers ::__invoke
     */
    public function testCanMatchMaxLengthOfArrays(array $array, int $length) : void {
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
    public function testThrowsExceptionWhenLengthIsTooShort(array $array, int $maxLength, string $message) : void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        $matcher = $this->matcher;
        $matcher($array, $maxLength);
    }
}
