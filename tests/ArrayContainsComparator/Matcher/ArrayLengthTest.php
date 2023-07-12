<?php declare(strict_types=1);
namespace Imbo\BehatApiExtension\ArrayContainsComparator\Matcher;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\BehatApiExtension\ArrayContainsComparator\Matcher\ArrayLength
 */
class ArrayLengthTest extends TestCase
{
    private ArrayLength $matcher;

    public function setup(): void
    {
        $this->matcher = new ArrayLength();
    }

    /**
     * @return array<array{list:array<int>,length:int}>
     */
    public static function getArraysAndLengths(): array
    {
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
     * @return array<array{array:array<int>,maxLength:int,message:string}>
     */
    public static function getValuesThatFail(): array
    {
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
    public function testCanMatchLengthOfArrays(array $array, int $length): void
    {
        $matcher = $this->matcher;

        $this->assertTrue(
            $matcher($array, $length),
            'Matcher is supposed to return true.',
        );
    }

    /**
     * @covers ::__invoke
     */
    public function testThrowsExceptionWhenMatchingLengthAgainstAnythingOtherThanAnArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only numerically indexed arrays are supported, got "object".');
        $matcher = $this->matcher;
        $matcher(['foo' => 'bar'], 123);
    }

    /**
     * @dataProvider getValuesThatFail
     * @covers ::__invoke
     */
    public function testThrowsExceptionWhenLengthIsNotCorrect(array $array, int $length, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        $matcher = $this->matcher;
        $matcher($array, $length);
    }
}
