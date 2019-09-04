<?php declare(strict_types=1);
namespace Imbo\BehatApiExtension\ArrayContainsComparator\Matcher;

use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

/**
 * @coversDefaultClass Imbo\BehatApiExtension\ArrayContainsComparator\Matcher\RegExp
 */
class RegExpTest extends TestCase {
    private $matcher;

    public function setUp() : void {
        $this->matcher = new RegExp();
    }

    public function getSubjectsAndPatterns() : array {
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

    /**
     * @covers ::__invoke
     */
    public function testThrowsExceptionIfSubjectIsNotASupportedVariableType() : void {
        $matcher = $this->matcher;
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Regular expression matching can only be applied to strings, integers or doubles, got "boolean".');
        $matcher(true, '/true/');
    }

    /**
     * @dataProvider getSubjectsAndPatterns
     * @covers ::__invoke
     */
    public function testCanMatchRegularExpressionPatternsAgainst($subject, string $pattern) : void {
        $matcher = $this->matcher;
        $this->assertNull($matcher($subject, $pattern));
    }

    /**
     * @covers ::__invoke
     */
    public function testThrowsExceptionIfPatternDoesNotMatchSubject() : void {
        $matcher = $this->matcher;
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Subject "some string" did not match pattern "/SOME STRING/".');
        $matcher('some string', '/SOME STRING/');
    }
}
