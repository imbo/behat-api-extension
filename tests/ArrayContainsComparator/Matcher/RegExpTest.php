<?php
namespace Imbo\BehatApiExtension\ArrayContainsComparator\Matcher;

use PHPUnit_Framework_TestCase;

/**
 * @coversDefaultClass Imbo\BehatApiExtension\ArrayContainsComparator\Matcher\RegExp
 */
class RegExpTest extends PHPUnit_Framework_TestCase {
    /**
     * @var RegExp
     */
    private $matcher;

    /**
     * Set up matcher instance
     */
    public function setup() {
        $this->matcher = new RegExp();
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getSubjectsAndPatterns() {
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
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Regular expression matching can only be applied to strings, integers or doubles, got "boolean".
     * @covers ::__invoke
     */
    public function testThrowsExceptionIfSubjectIsNotASupportedVariableType() {
        $matcher = $this->matcher;
        $matcher(true, '/true/');
    }

    /**
     * @dataProvider getSubjectsAndPatterns
     * @covers ::__invoke
     * @param scalar $subject
     * @param string $pattern
     */
    public function testCanMatchRegularExpressionPatternsAgainst($subject, $pattern) {
        $matcher = $this->matcher;
        $matcher($subject, $pattern);
    }

    /**
     * @covers ::__invoke
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Subject "some string" did not match pattern "/SOME STRING/".
     */
    public function testThrowsExceptionIfPatternDoesNotMatchSubject() {
        $matcher = $this->matcher;
        $matcher('some string', '/SOME STRING/');
    }
}
