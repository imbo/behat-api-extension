<?php declare(strict_types=1);

namespace Imbo\BehatApiExtension\ArrayContainsComparator\Matcher;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(LessThan::class)]
class LessThanTest extends TestCase
{
    private LessThan $matcher;

    protected function setUp(): void
    {
        $this->matcher = new LessThan();
    }

    /**
     * @return array<array{number:int|string,max:int|string|float}>
     */
    public static function getValuesForMatching(): array
    {
        return [
            'integer' => [
                'number' => 1,
                'max' => 2,
            ],
            'float / double' => [
                'number' => 1,
                'max' => 1.1,
            ],
            'string' => [
                'number' => '1',
                'max' => '2',
            ],
        ];
    }

    /**
     * @return array<array{number:int|float|string,max:int|float|string,errorMessage:string}>
     */
    public static function getFailingValues(): array
    {
        return [
            [
                'number' => 123,
                'max' => 123,
                'errorMessage' => '"123" is not less than "123".',
            ],
            [
                'number' => 456,
                'max' => 123,
                'errorMessage' => '"456" is not less than "123".',
            ],
            [
                'number' => 4.56,
                'max' => 1.23,
                'errorMessage' => '"4.56" is not less than "1.23".',
            ],
            [
                'number' => '4.56',
                'max' => '1.23',
                'errorMessage' => '"4.56" is not less than "1.23".',
            ],
        ];
    }

    #[DataProvider('getValuesForMatching')]
    public function testCanCompareValuesOfType(int|string $number, int|string|float $max): void
    {
        $matcher = $this->matcher;
        $this->assertTrue(
            $matcher($number, $max),
            'Matcher is supposed to return true.',
        );
    }

    public function testThrowsExceptionIfNumberIsNotNumeric(): void
    {
        $matcher = $this->matcher;
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"foo" is not numeric.');
        $matcher('foo', 123);
    }

    public function testThrowsExceptionIfMaximumNumberIsNotNumeric(): void
    {
        $matcher = $this->matcher;
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"foo" is not numeric.');
        $matcher(123, 'foo');
    }

    #[DataProvider('getFailingValues')]
    public function testThrowsExceptionWhenComparisonFails(int|float|string $number, int|float|string $max, string $errorMessage): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($errorMessage);
        $matcher = $this->matcher;
        $matcher($number, $max);
    }
}
