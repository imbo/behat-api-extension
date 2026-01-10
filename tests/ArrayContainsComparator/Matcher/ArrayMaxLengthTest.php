<?php declare(strict_types=1);

namespace Imbo\BehatApiExtension\ArrayContainsComparator\Matcher;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(ArrayMaxLength::class)]
class ArrayMaxLengthTest extends TestCase
{
    private ArrayMaxLength $matcher;

    protected function setup(): void
    {
        $this->matcher = new ArrayMaxLength();
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

    #[DataProvider('getArraysAndLengths')]
    public function testCanMatchMaxLengthOfArrays(array $list, int $length): void
    {
        $matcher = $this->matcher;
        $this->assertTrue(
            $matcher($list, $length),
            'Matcher is supposed to return true.',
        );
    }

    public function testThrowsExceptionWhenMatchingAgainstAnythingOtherThanAnArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only numerically indexed arrays are supported, got "object".');
        $matcher = $this->matcher;
        $matcher(['foo' => 'bar'], 123);
    }

    #[DataProvider('getValuesThatFail')]
    public function testThrowsExceptionWhenLengthIsTooShort(array $array, int $maxLength, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        $matcher = $this->matcher;
        $matcher($array, $maxLength);
    }
}
