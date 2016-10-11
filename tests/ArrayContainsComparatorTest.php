<?php
namespace Imbo\BehatApiExtension;

use PHPUnit_Framework_TestCase;
use Closure;
use RuntimeException;

/**
 * @coversDefaultClass Imbo\BehatApiExtension\ArrayContainsComparator
 */
class ArrayContainsComparatorText extends PHPUnit_Framework_TestCase {
    private $comparator;

    public function setUp() {
        $this->comparator = new ArrayContainsComparator();
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Regular expression matching must be used with scalars.
     * @covers ::matchString
     */
    public function testMatchStringWhithNonScalar() {
        $this->comparator->matchString('/foo/', ['foo']);
    }

    /**
     * Data provider: Get patterns, strings and results
     *
     * @return array[]
     */
    public function getPatternsAndStrings() {
        return [
            'success' => [
                'pattern' => '/foo/',
                'string' => 'foo',
                'success' => true,
            ],
            'failure' => [
                'pattern' => '/bar/',
                'string' => 'foo',
                'success' => false,
            ],
        ];
    }

    /**
     * Data provider: Get arrays and their lengths
     *
     * @return array[]
     */
    public function getArraysAndLength() {
        return [
            'success' => [
                'array' => [1, 2, 3],
                'length' => 3,
                'success' => true,
            ],
            'failure' => [
                'array' => [1, 2, 3],
                'length' => 2,
                'success' => false,
            ],
        ];
    }

    /**
     * Data provider: Get arrays and their "at least" lengths
     *
     * @return array[]
     */
    public function getArraysAndAtLeastLength() {
        return [
            'success' => [
                'array' => [1, 2, 3],
                'min' => 3,
                'success' => true,
            ],
            'failure' => [
                'array' => [1, 2, 3],
                'min' => 4,
                'success' => false,
            ],
        ];
    }

    /**
     * Data provider: Get arrays and their "at most" lengths
     *
     * @return array[]
     */
    public function getArraysAndAtMostLength() {
        return [
            'success' => [
                'array' => [1, 2, 3],
                'max' => 3,
                'success' => true,
            ],
            'failure' => [
                'array' => [1, 2, 3],
                'max' => 2,
                'success' => false,
            ],
        ];
    }

    /**
     * Data provider: Get regular needle values for the parseNeedleValue method
     *
     * @return array[]
     */
    public function getRegularNeedleValues() {
        return [
            'integer value' => [
                'value' => 2,
                'parsed' => 2,
            ],
            'float value' => [
                'value' => 1.1,
                'parsed' => 1.1,
            ],
            'boolean value' => [
                'value' => true,
                'parsed' => false,
            ],
            'array value' => [
                'value' => [1, 2, 3],
                'parsed' => [1, 2, 3],
            ],
            'regular string value' => [
                'value' => 'foobar',
                'parsed' => 'foobar',
            ],
        ];
    }

    /**
     * Data provider: Get callback needle values for the parseNeedleValue method
     *
     * @return array[]
     */
    public function getCallbackNeedleValues() {
        return [
            'regular expression' => [
                'needle' => '<re>/foo/</re>',
                'callbackParam' => 'foo',
                'methodName' => 'regular expression',
            ],
            '@length' => [
                'needle' => '@length(1)',
                'callbackParam' => [1],
                'methodName' => '@length',
            ],
            '@atLeast' => [
                'needle' => '@atLeast(1)',
                'callbackParam' => [1],
                'methodName' => '@atLeast',
            ],
            '@atMost' => [
                'needle' => '@atMost(1)',
                'callbackParam' => [1],
                'methodName' => '@atMost',
            ],
        ];
    }

    /**
     * Data provider: Get haystack and needle values to compare
     *
     * @return array[]
     */
    public function getValuesToCompare() {
        return [
            'missing key' => [
                'haystack' => [
                    'foo' => 'bar',
                ],
                'needle' => [
                    'bar' => 'foo',
                ],
                'willFail' => true,
                'exception' => 'OutOfRangeException',
                'exceptionMessage' => 'Key is missing from the haystack: bar',
            ],

            'type mismatch for regular values' => [
                'haystack' => [
                    'foo' => 123,
                ],
                'needle' => [
                    'foo' => '123',
                ],
                'willFail' => true,
                'exception' => 'UnexpectedValueException',
                'exceptionMessage' => 'Type mismatch for haystack key "foo" (haystack type: integer, needle type: string)',
            ],

            'failure for the regular expression callback' => [
                'haystack' => [
                    'foo' => 'foobar',
                ],
                'needle' => [
                    'foo' => '<re>/foo$/</re>',
                ],
                'willFail' => true,
                'exception' => 'InvalidArgumentException',
                'exceptionMessage' => '"regular expression" function failed for the "foo" haystack key',
            ],

            'failure for the @length callback' => [
                'haystack' => [
                    'foo' => [1, 2, 3],
                ],
                'needle' => [
                    'foo' => '@length(2)',
                ],
                'willFail' => true,
                'exception' => 'InvalidArgumentException',
                'exceptionMessage' => '"@length" function failed for the "foo" haystack key',
            ],

            'failure for the @atLeast callback' => [
                'haystack' => [
                    'foo' => [1, 2, 3],
                ],
                'needle' => [
                    'foo' => '@atLeast(4)',
                ],
                'willFail' => true,
                'exception' => 'InvalidArgumentException',
                'exceptionMessage' => '"@atLeast" function failed for the "foo" haystack key',
            ],

            'failure for the @atMost callback' => [
                'haystack' => [
                    'foo' => [1, 2, 3],
                ],
                'needle' => [
                    'foo' => '@atMost(2)',
                ],
                'willFail' => true,
                'exception' => 'InvalidArgumentException',
                'exceptionMessage' => '"@atMost" function failed for the "foo" haystack key',
            ],

            'success for all callbacks' => [
                'haystack' => [
                    'regular expression' => 'some string',
                    '@length' => [1],
                    '@atLeast' => [1, 2],
                    '@atMost' => [1, 2, 3],
                ],
                'needle' => [
                    'regular expression' => '<re>/string/</re>',
                    '@length' => '@length(1)',
                    '@atLeast' => '@atLeast(2)',
                    '@atMost' => '@atMost(3)',
                ],
                'willFail' => false,
            ],

            'scalar match with strings' => [
                'haystack' => [
                    'foo' => 'bar',
                    'bar' => 'foo',
                ],
                'needle' => [
                    'foo' => 'bar',
                    'bar' => 'foo',
                ],
                'willFail' => false,
            ],

            'scalar mis-match with strings' => [
                'haystack' => [
                    'foo' => 'bar',
                ],
                'needle' => [
                    'foo' => 'foo',
                ],
                'willFail' => true,
                'exception' => 'InvalidArgumentException',
                'exceptionMessage' => 'Value mismatch for haystack key "foo": bar != foo',
            ],

            'scalar mis-match with integers' => [
                'haystack' => [
                    'foo' => 1,
                ],
                'needle' => [
                    'foo' => 2,
                ],
                'willFail' => true,
                'exception' => 'InvalidArgumentException',
                'exceptionMessage' => 'Value mismatch for haystack key "foo": 1 != 2',
            ],

            'mismatch for a sub-key' => [
                'haystack' => [
                    'foo' => [
                        'bar' => [
                            'baz' => 'foobar',
                        ],
                    ],
                ],
                'needle' => [
                    'foo' => [
                        'bar' => [
                            'baz' => 'barfoo',
                        ],
                    ],
                ],
                'willFail' => true,
                'exception' => 'InvalidArgumentException',
                'exceptionMessage' => 'Value mismatch for haystack key "foo.bar.baz": foobar != barfoo',
            ],

            'numerically indexed array that succeeds' => [
                'haystack' => [
                    'foo' => [1, 2, 3],
                ],
                'needle' => [
                    'foo' => [1],
                ],
                'willFail' => false,
            ],

            'numerically indexed array that fails' => [
                'haystack' => [
                    'foo' => [1, 2, 3],
                ],
                'needle' => [
                    'foo' => [4],
                ],
                'willFail' => true,
                'exception' => 'InvalidArgumentException',
                'exceptionMessage' => 'The value 4 is not present in the haystack array at key "foo"',
            ],

            'numerically indexed array with non-scalar value' => [
                'haystack' => [
                    'foo' => [[1], ['foo' => 'bar']],
                ],
                'needle' => [
                    'foo' => [[1], ['foo' => 'bar']],
                ],
                'willFail' => false,
            ],

            'match item in array when value is not an array' => [
                'haystack' => [
                    'foo' => 'bar',
                ],
                'needle' => [
                    'foo[0]' => 1,
                ],
                'willFail' => true,
                'exception' => 'UnexpectedValueException',
                'exceptionMessage' => 'Element at haystack key "foo" is not an array.',

            ],

            'match item in array with index out of range' => [
                'haystack' => [
                    'foo' => [
                        'bar' => [
                            'baz' => [1],
                        ],
                    ],
                ],
                'needle' => [
                    'foo' => [
                        'bar' => [
                            'baz[1]' =>'foo',
                        ],
                    ],
                ],
                'willFail' => true,
                'exception' => 'OutOfRangeException',
                'exceptionMessage' => 'Index 1 does not exist in the array at haystack key "foo.bar.baz"',
            ],

            'item in array does not match' => [
                'haystack' => [
                    'foo' => [1],
                ],
                'needle' => [
                    'foo[0]' => 2,
                ],
                'willFail' => true,
                'exception' => 'InvalidArgumentException',
                'exceptionMessage' => 'Item on index 0 in array at haystak key "foo" does not match value 2',
            ],

            'match item in array' => [
                'haystack' => [
                    'foo' => [
                        1,
                        'bar',
                        1.1,
                        true,
                        false,
                        [1],
                        [1, 2],
                        [1, 2, 3],
                        ['foo' => 'bar'],
                        'baz'
                    ],
                ],
                'needle' => [
                    'foo' => [
                        1,
                        'bar',
                        1.1,
                        true,
                        false,
                        [1],
                        [1, 2],
                        [1, 2, 3],
                        ['foo' => 'bar'],
                        'baz'
                    ],
                    'foo[0]' => 1,
                    'foo[1]' => 'bar',
                    'foo[2]' => 1.1,
                    'foo[3]' => true,
                    'foo[4]' => false,
                    'foo[5]' => '@length(1)',
                    'foo[6]' => '@atLeast(2)',
                    'foo[7]' => '@atMost(3)',
                    'foo[8]' => ['foo' => 'bar'],
                    'foo[9]' => '<re>/baz/</re>',
                ],
                'willFail' => false,
            ],

            // @see https://github.com/imbo/behat-api-extension/issues/13
            'match sub-arrays using indexes' => [
                'haystack' => [
                    'foo' => [
                        'bar' => [
                            [
                                'foo' => 'bar',
                                'baz' => 'bat',
                            ],
                            [
                                'foo' => 'bar',
                                'baz' => 'bat',
                            ],
                        ],
                    ],
                ],
                'needle' => [
                    'foo' => [
                        'bar[0]' => [
                            'foo' => 'bar',
                        ],
                        'bar[1]' => [
                            'baz' => 'bat',
                        ]
                    ]
                ],
                'willFail' => false,
            ],

            'MATCH ALL THE THINGS!!!' => [
                'haystack' => [
                    'null' => null,
                    'string' => 'value',
                    'integer' => 123,
                    'float' => 1.23,
                    'boolean true' => true,
                    'boolean false' => true,
                    'regular expression' => 'foobar',
                    'length' => [1, 2, 3],
                    'atLeast' => ['foo', 'bar'],
                    'atMost' => ['foo', 'bar', 'baz'],
                    'sub' => [
                        'string' => 'value',
                        'integer' => 123,
                        'float' => 1.23,
                        'boolean true' => true,
                        'boolean false' => true,
                        'regular expression' => 'foobar',
                        'length' => [1, 2, 3],
                        'atLeast' => ['foo', 'bar'],
                        'atMost' => ['foo', 'bar', 'baz'],
                    ],
                ],
                'needle' => [
                    'null' => null,
                    'string' => 'value',
                    'integer' => 123,
                    'float' => 1.23,
                    'boolean true' => true,
                    'boolean false' => true,
                    'regular expression' => '<re>/foobar/</re>',
                    'length' => '@length(3)',
                    'atLeast' => '@atLeast(2)',
                    'atMost' => '@atMost(3)',
                    'atLeast[0]' => 'foo',
                    'atLeast[1]' => '<re>/bar/</re>',
                    'sub' => [
                        'string' => 'value',
                        'integer' => 123,
                        'float' => 1.23,
                        'boolean true' => true,
                        'boolean false' => true,
                        'regular expression' => '<re>/foobar/</re>',
                        'length' => '@length(3)',
                        'atLeast' => '@atLeast(2)',
                        'atMost' => '@atMost(3)',
                        'atLeast[0]' => 'foo',
                        'atLeast[1]' => '<re>/bar/</re>',
                    ],
                ],
                'willFail' => false,
            ],
        ];
    }

    /**
     * @dataProvider getPatternsAndStrings
     * @covers ::matchString
     */
    public function testMatchString($pattern, $string, $success) {
        $result = $this->comparator->matchString($pattern, $string);
        $this->assertSame('regular expression', $key = key($result));
        $this->assertSame($success, $result[$key]);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage @length function can only be used with arrays.
     * @covers ::arrayLengthIs
     */
    public function testArrayLengthIsWithNonArray() {
        $this->comparator->arrayLengthIs('foo', 3);
    }

    /**
     * @dataProvider getArraysAndLength
     * @covers ::arrayLengthIs
     */
    public function testArrayLengthIs($array, $length, $success) {
        $result = $this->comparator->arrayLengthIs($array, $length);
        $this->assertSame('@length', $key = key($result));
        $this->assertSame($success, $result[$key]);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage @atLeast function can only be used with arrays.
     * @covers ::arrayLengthIsAtLeast
     */
    public function testArrayLengthIsAtLeastWithNonArray() {
        $this->comparator->arrayLengthIsAtLeast('foo', 3);
    }

    /**
     * @dataProvider getArraysAndAtLeastLength
     * @covers ::arrayLengthIsAtLeast
     */
    public function testArrayLengthIsAtLeast($array, $min, $success) {
        $result = $this->comparator->arrayLengthIsAtLeast($array, $min);
        $this->assertSame('@atLeast', $key = key($result));
        $this->assertSame($success, $result[$key]);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage @atMost function can only be used with arrays.
     * @covers ::arrayLengthIsAtMost
     */
    public function testArrayLengthIsAtMostWithNonArray() {
        $this->comparator->arrayLengthIsAtMost('foo', 3);
    }

    /**
     * @dataProvider getArraysAndAtMostLength
     * @covers ::arrayLengthIsAtMost
     */
    public function testArrayLengthIsAtMost($array, $max, $success) {
        $result = $this->comparator->arrayLengthIsAtMost($array, $max);
        $this->assertSame('@atMost', $key = key($result));
        $this->assertSame($success, $result[$key]);
    }

    /**
     * @dataProvider getRegularNeedleValues
     * @covers ::parseNeedleValue
     */
    public function testParseRegularNeedleValue($value) {
        $this->assertSame($this->comparator->parseNeedleValue($value), $value);
    }

    /**
     * @dataProvider getCallbackNeedleValues
     * @covers ::parseNeedleValue
     */
    public function testParseCallbackNeedleValues($needle, $callbackParam, $methodName) {
        $callback = $this->comparator->parseNeedleValue($needle);

        $this->assertInstanceOf('Closure', $callback);

        $result = $callback($callbackParam);
        $this->assertSame($methodName, key($result));
    }

    /**
     * @dataProvider getValuesToCompare
     * @covers ::compare
     * @covers ::compareHaystackValueWithCallback
     */
    public function testCompareValues(array $haystack, array $needle, $willFail = false, $exception = null, $exceptionMessage = null) {
        if ($willFail) {
            $this->expectException($exception);
            $this->expectExceptionMessage($exceptionMessage);
        }

        // This last assert will only be executed when the comparison succeeds as a failure throws
        // an exception
        $this->assertTrue($this->comparator->compare($haystack, $needle));
    }

    /**
     * @covers ::compare
     * @covers ::compareHaystackValueWithCallback
     * @see https://github.com/imbo/behat-api-extension/issues/1
     */
    public function testCompareValuesWithNull() {
        // This last assert will only be executed when the comparison succeeds as a failure throws
        // an exception
        $this->assertTrue($this->comparator->compare(['null' => null], ['null' => null]));
    }
}
