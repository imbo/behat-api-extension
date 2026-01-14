<?php declare(strict_types=1);

namespace Imbo\BehatApiExtension\ArrayContainsComparator\Matcher;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(ArrayLength::class)]
class ArrayLengthTest extends TestCase
{
    private ArrayLength $matcher;

    protected function setup(): void
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
     * @return array<array{list:array<int>,maxLength:int,message:string}>
     */
    public static function getValuesThatFail(): array
    {
        return [
            [
                'list' => [1, 2],
                'maxLength' => 1,
                'message' => 'Expected array to have exactly 1 entries, actual length: 2.',
            ],
            [
                'list' => [],
                'maxLength' => 2,
                'message' => 'Expected array to have exactly 2 entries, actual length: 0.',
            ],
        ];
    }

    #[DataProvider('getArraysAndLengths')]
    public function testCanMatchLengthOfArrays(array $list, int $length): void
    {
        $matcher = $this->matcher;

        $this->assertTrue(
            $matcher($list, $length),
            'Matcher is supposed to return true.',
        );
    }

    public function testThrowsExceptionWhenMatchingLengthAgainstAnythingOtherThanAnArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only numerically indexed arrays are supported, got "object".');
        $matcher = $this->matcher;
        $matcher(['foo' => 'bar'], 123);
    }

    #[DataProvider('getValuesThatFail')]
    public function testThrowsExceptionWhenLengthIsNotCorrect(array $list, int $maxLength, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        $matcher = $this->matcher;
        $matcher($list, $maxLength);
    }
}
