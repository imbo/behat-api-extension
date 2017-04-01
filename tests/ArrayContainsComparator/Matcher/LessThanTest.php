<?php
namespace Imbo\BehatApiExtension\ArrayContainsComparator\Matcher;

use PHPUnit_Framework_TestCase;

/**
 * @coversDefaultClass Imbo\BehatApiExtension\ArrayContainsComparator\Matcher\LessThan
 * @testdox Numeric less than matcher
 */
class LessThanTest extends PHPUnit_Framework_TestCase {
    /**
     * @var LessThan
     */
    private $matcher;

    /**
     * Set up matcher instance
     */
    public function setup() {
        $this->matcher = new LessThan();
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getValuesForMatching() {
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
     * Data provider
     *
     * @return array[]
     */
    public function getFailingValues() {
        return [
            [
                'number' => 123,
                'max' => 123,
                'errorMessage' => '"123" is not less than "123".'
            ],
            [
                'number' => 456,
                'max' => 123,
                'errorMessage' => '"456" is not less than "123".'
            ],
            [
                'number' => 4.56,
                'max' => 1.23,
                'errorMessage' => '"4.56" is not less than "1.23".'
            ],
            [
                'number' => "4.56",
                'max' => "1.23",
                'errorMessage' => '"4.56" is not less than "1.23".'
            ],
        ];
    }

    /**
     * @dataProvider getValuesForMatching
     * @covers ::__invoke
     *
     * @param numeric $number
     * @param numeric $max
     */
    public function testCanCompareValuesOfType($number, $max) {
        $matcher = $this->matcher;
        $this->assertNull(
            $matcher($number, $max),
            'Matcher is supposed to return null.'
        );
    }

    /**
     * @covers ::__invoke
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage "foo" is not numeric.
     */
    public function testThrowsExceptionIfNumberIsNotNumeric() {
        $matcher = $this->matcher;
        $matcher('foo', 123);
    }

    /**
     * @covers ::__invoke
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage "foo" is not numeric.
     */
    public function testThrowsExceptionIfMaximumNumberIsNotNumeric() {
        $matcher = $this->matcher;
        $matcher(123, 'foo');
    }

    /**
     * @dataProvider getFailingValues
     * @covers ::__invoke
     * @expectedException InvalidArgumentException
     *
     * @param numeric $number
     * @param numeric $max
     * @param string $errorMessage
     */
    public function testThrowsExceptionWhenComparisonFails($number, $max, $errorMessage) {
        $this->expectExceptionMessage($errorMessage);
        $matcher = $this->matcher;
        $matcher($number, $max);
    }
}
