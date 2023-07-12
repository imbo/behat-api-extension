<?php declare(strict_types=1);
namespace Imbo\BehatApiExtension\ArrayContainsComparator\Matcher;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\BehatApiExtension\ArrayContainsComparator\Matcher\GreaterThan
 */
class GreaterThanTest extends TestCase
{
    private GreaterThan $matcher;

    public function setUp(): void
    {
        $this->matcher = new GreaterThan();
    }

    /**
     * @return array<array{number:int|float|string,min:int|float|string}>
     */
    public static function getValuesForMatching(): array
    {
        return [
            'integer' => [
                'number' => 2,
                'min' => 1,
            ],
            'float / double' => [
                'number' => 1.1,
                'min' => 1,
            ],
            'string' => [
                'number' => '2',
                'min' => '1',
            ],
        ];
    }

    /**
     * @return array<array{number:int|string|float,min:int|string|float,errorMessage:string}>
     */
    public static function getFailingValues(): array
    {
        return [
            [
                'number' => 123,
                'min' => 123,
                'errorMessage' => '"123" is not greater than "123".',
            ],
            [
                'number' => 123,
                'min' => 456,
                'errorMessage' => '"123" is not greater than "456".',
            ],
            [
                'number' => 1.23,
                'min' => 4.56,
                'errorMessage' => '"1.23" is not greater than "4.56".',
            ],
            [
                'number' => "1.23",
                'min' => "4.56",
                'errorMessage' => '"1.23" is not greater than "4.56".',
            ],
        ];
    }

    /**
     * @dataProvider getValuesForMatching
     * @covers ::__invoke
     */
    public function testCanCompareValuesOfType(int|float|string $number, int|float|string $min): void
    {
        $matcher = $this->matcher;
        $this->assertTrue(
            $matcher($number, $min),
            'Matcher is supposed to return true.',
        );
    }

    /**
     * @covers ::__invoke
     */
    public function testThrowsExceptionIfNumberIsNotNumeric(): void
    {
        $matcher = $this->matcher;
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"foo" is not numeric.');
        $matcher('foo', 123);
    }

    /**
     * @covers ::__invoke
     */
    public function testThrowsExceptionIfMinimumNumberIsNotNumeric(): void
    {
        $matcher = $this->matcher;
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"foo" is not numeric.');
        $matcher(123, 'foo');
    }

    /**
     * @dataProvider getFailingValues
     * @covers ::__invoke
     */
    public function testThrowsExceptionWhenComparisonFails(int|string|float $number, int|string|float $min, string $errorMessage): void
    {
        $matcher = $this->matcher;
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($errorMessage);
        $matcher($number, $min);
    }
}
