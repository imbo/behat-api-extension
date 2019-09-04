<?php
namespace Imbo\BehatApiExtension\ArrayContainsComparator\Matcher;

use PHPUnit_Framework_TestCase;

/**
 * @coversDefaultClass Imbo\BehatApiExtension\ArrayContainsComparator\Matcher\GreaterThan
 */
class GreaterThanTest extends PHPUnit_Framework_TestCase {
    /**
     * @var GreaterThan
     */
    private $matcher;

    /**
     * Set up matcher instance
     */
    public function setup() {
        $this->matcher = new GreaterThan();
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getValuesForMatching() {
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
     * Data provider
     *
     * @return array[]
     */
    public function getFailingValues() {
        return [
            [
                'number' => 123,
                'min' => 123,
                'errorMessage' => '"123" is not greater than "123".'
            ],
            [
                'number' => 123,
                'min' => 456,
                'errorMessage' => '"123" is not greater than "456".'
            ],
            [
                'number' => 1.23,
                'min' => 4.56,
                'errorMessage' => '"1.23" is not greater than "4.56".'
            ],
            [
                'number' => "1.23",
                'min' => "4.56",
                'errorMessage' => '"1.23" is not greater than "4.56".'
            ],
        ];
    }

    /**
     * @dataProvider getValuesForMatching
     * @covers ::__invoke
     *
     * @param numeric $number
     * @param numeric $min
     */
    public function testCanCompareValuesOfType($number, $min) {
        $matcher = $this->matcher;
        $this->assertNull(
            $matcher($number, $min),
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
    public function testThrowsExceptionIfMinimumNumberIsNotNumeric() {
        $matcher = $this->matcher;
        $matcher(123, 'foo');
    }

    /**
     * @dataProvider getFailingValues
     * @covers ::__invoke
     * @expectedException InvalidArgumentException
     *
     * @param numeric $number
     * @param numeric $min
     * @param string $errorMessage
     */
    public function testThrowsExceptionWhenComparisonFails($number, $min, $errorMessage) {
        $this->expectExceptionMessage($errorMessage);
        $matcher = $this->matcher;
        $matcher($number, $min);
    }
}
