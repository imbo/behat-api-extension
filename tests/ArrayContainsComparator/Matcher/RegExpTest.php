<?php declare(strict_types=1);

namespace Imbo\BehatApiExtension\ArrayContainsComparator\Matcher;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(RegExp::class)]
class RegExpTest extends TestCase
{
    private RegExp $matcher;

    protected function setUp(): void
    {
        $this->matcher = new RegExp();
    }

    /**
     * @return array<string,array{subject:float|int|string,pattern:string}>
     */
    public static function getSubjectsAndPatterns(): array
    {
        return [
            'a regular string' => [
                'subject' => 'some string',
                'pattern' => '/^SOME STRING$/i',
            ],
            'an integer' => [
                'subject' => 666,
                'pattern' => '/^666$/',
            ],
            'a float' => [
                'subject' => 3.14,
                'pattern' => '/^3\.14$/',
            ],
        ];
    }

    #[DataProvider('getSubjectsAndPatterns')]
    public function testCanMatchRegularExpressionPatternsAgainst(float|int|string $subject, string $pattern): void
    {
        $matcher = $this->matcher;
        $this->assertTrue($matcher($subject, $pattern));
    }

    public function testThrowsExceptionIfPatternDoesNotMatchSubject(): void
    {
        $matcher = $this->matcher;
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Subject "some string" did not match pattern "/SOME STRING/".');
        $matcher('some string', '/SOME STRING/');
    }
}
