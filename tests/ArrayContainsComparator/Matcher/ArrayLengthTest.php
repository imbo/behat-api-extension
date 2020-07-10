<?php declare(strict_types=1);
namespace Imbo\BehatApiExtension\ArrayContainsComparator\Matcher;

use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

/**
 * @coversDefaultClass Imbo\BehatApiExtension\ArrayContainsComparator\Matcher\ArrayLength
 */
class ArrayLengthTest extends TestCase {
    /** @var ArrayLength */
    private $matcher;

    public function setup() : void {
        $this->matcher = new ArrayLength();
    }

    /**
     * @return array{list: int[], length: int}[]
     */
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

    /**
     * @return array{value: int|string|array<string, string>, message: string}[]
     */
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

    /**
     * @return array{array: int[], maxLength: int, message: string}[]
     */
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
     * @param int[] $array
     */
    public function testCanMatchLengthOfArrays(array $array, int $length) : void {
        $matcher = $this->matcher;

        $this->assertTrue(
            $matcher($array, $length),
            'Matcher is supposed to return true.'
        );
    }

    /**
     * @dataProvider getInvalidValues
     * @covers ::__invoke
     * @param int|string|array<string, string> $value
     */
    public function testThrowsExceptionWhenMatchingLengthAgainstAnythingOtherThanAnArray($value, string $message) : void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        $matcher = $this->matcher;
        /** @psalm-suppress PossiblyInvalidArgument */
        $matcher($value, 123); // @phpstan-ignore-line
    }

    /**
     * @dataProvider getValuesThatFail
     * @covers ::__invoke
     * @param int[] $array
     */
    public function testThrowsExceptionWhenLengthIsNotCorrect(array $array, int $length, string $message) : void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        $matcher = $this->matcher;
        $matcher($array, $length);
    }
}
