<?php declare(strict_types=1);
namespace Imbo\BehatApiExtension\ArrayContainsComparator\Matcher;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\BehatApiExtension\ArrayContainsComparator\Matcher\ArrayMinLength
 */
class ArrayMinLengthTest extends TestCase
{
    /** @var ArrayMinLength */
    private $matcher;

    public function setup(): void
    {
        $this->matcher = new ArrayMinLength();
    }

    /**
     * @return array{list: int[], min: int}[]
     */
    public static function getArraysAndMinLengths(): array
    {
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
     * @return array{array: int[], minLength: int, message: string}[]
     */
    public static function getValuesThatFail(): array
    {
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
     * @param int[] $array
     */
    public function testCanMatchMinLengthOfArrays(array $array, int $min): void
    {
        $matcher = $this->matcher;
        $this->assertTrue(
            $matcher($array, $min),
            'Matcher is supposed to return true.',
        );
    }

    /**
     * @covers ::__invoke
     */
    public function testThrowsExceptionWhenMatchingAgainstAnythingOtherThanAnArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only numerically indexed arrays are supported, got "object".');
        $matcher = $this->matcher;
        $matcher(['foo' => 'bar'], 123);
    }

    /**
     * @dataProvider getValuesThatFail
     * @covers ::__invoke
     * @param int[] $array
     */
    public function testThrowsExceptionWhenLengthIsTooLong(array $array, int $minLength, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        $matcher = $this->matcher;
        $matcher($array, $minLength);
    }
}
