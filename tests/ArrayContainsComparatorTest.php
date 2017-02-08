<?php
namespace Imbo\BehatApiExtension;

use PHPUnit_Framework_TestCase;
use Closure;
use RuntimeException;

/**
 * @coversDefaultClass Imbo\BehatApiExtension\ArrayContainsComparator
 * @testdox Array contains comparator
 */
class ArrayContainsComparatorTest extends PHPUnit_Framework_TestCase {
    /**
     * @var ArrayContainsComparator
     */
    private $comparator;

    /**
     * Set up the SUT
     */
    public function setUp() {
        $this->comparator = new ArrayContainsComparator();
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getDataForInArrayCheck() {
        $scalarHaystack = [1, 2, 1.1, 2.2, 'foo', 'bar', true, false];
        $haystackWithArrayElements = [
            1, 2, 3,
            [1, 2, 3],
            ['foo' => 'bar', 'bar' => 'foo'],
        ];

        return [
            'single value in needle with scalar haystack' => [
                'needle' => [1],
                'haystack' => $scalarHaystack,
            ],
            'multiple mixed values in needle with scalar haystack' => [
                'needle' => [1, 1.1, 'foo', true],
                'haystack' => $scalarHaystack,
            ],
            'all scalar values from haystack present in needle' => [
                'needle' => $scalarHaystack,
                'haystack' => $scalarHaystack,
            ],
            'list as needle' => [
                'needle' => [[1, 2]],
                'haystack' => $haystackWithArrayElements,
            ],
            'complex nested structure' => [
                'needle' => [
                    [
                        [
                            2
                        ],
                        4,
                        [
                            'list' => [
                                [
                                    [
                                        [
                                            'new-list' =>
                                            [
                                                8
                                            ]
                                        ]
                                    ]
                                ],
                                5,
                                [
                                    6
                                ],
                                [
                                    [
                                        7
                                    ]
                                ],
                            ],
                        ],
                    ],
                    3,
                    4
                ],
                'haystack' => [
                    [
                        [
                            1,
                            2
                        ],
                        3,
                        4,
                        [
                            'list' => [
                                5,
                                [
                                    6,
                                    [
                                        7,
                                        [
                                            'new-list' =>
                                            [
                                                8
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                    ],
                    3,
                    4
                ],
            ],
        ];
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getDataForCompareCheck() {
        return [
            'simple key value objects' => [
                'needle' => [
                    'string values' => 'string',
                    'integer value' => 123,
                    'float value' => 1.23,
                    'boolean value' => true,
                    'null value' => null,
                ],
                'haystack' => [
                    'string values' => 'string',
                    'integer value' => 123,
                    'float value' => 1.23,
                    'boolean value' => true,
                    'null value' => null,
                ],
            ],
            'nested key value objects' => [
                'needle' => [
                    'foo' => [
                        'bar' => [
                            'baz' => [
                                'foo' => 'bar',
                            ],
                        ],
                    ],
                ],
                'haystack' => [
                    'foo' => [
                        'bar' => [
                            'baz' => [
                                'foo' => 'bar',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getDataForSpecificKeyInListChecks() {
        return [
            'simple list in haystack key' => [
                'needle' => [
                    'key[0]' => 'index0',
                    'key[1]' => 'index1',
                    'key[2]' => 'index2',
                ],
                'haystack' => [
                    'key' => [
                        'index0',
                        'index1',
                        'index2',
                    ],
                ],
            ],
            'simple list as root node' => [
                'needle' => [
                    '[0]' => 'index0',
                    '[1]' => 'index1',
                    '[2]' => 'index2',
                ],
                'haystack' => [
                    'index0',
                    'index1',
                    'index2',
                ],
            ],
            'list in a deep structure' => [
                'needle' => [
                    'items[0]' => [
                        'key' => 'item1',
                        'sub[0]' => 1,
                        'sub[1]' => 2,
                    ],
                    'items[1]' => [
                        'key' => 'item2',
                        'sub[0]' => 3,
                        'sub[1]' => 4,
                    ],
                    'items[2]' => [
                        'key' => 'item3',
                        'sub[0]' => 5,
                        'sub[1]' => 6,
                    ],
                ],
                'haystack' => [
                    'items' => [
                        [
                            'key' => 'item1',
                            'sub' => [1, 2],
                        ],
                        [
                            'key' => 'item2',
                            'sub' => [3, 4],
                        ],
                        [
                            'key' => 'item3',
                            'sub' => [5, 6],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getDataForSpecificKeyInListChecksWithInvalidData() {
        return [
            'a string' => [
                'needle' => [
                    'foo[0]' => 'bar',
                ],
                'haystack' => [
                    'foo' => 'bar',
                ],
            ],
            'an object' => [
                'needle' => [
                    'foo[0]' => 'bar',
                ],
                'haystack' => [
                    'foo' => [
                        'foo' => 'bar'
                    ],
                ],
            ],
        ];
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The needle is a numerically indexed array, while the haystack is not:
     * @covers ::compare
     */
    public function testThrowsExceptionWhenMatchingANumericallyIndexedArrayAgainstAnAssociativeArray() {
        $this->comparator->compare([1, 2, 3], ['foo' => 'bar']);
    }

    /**
     * @dataProvider getDataForInArrayCheck
     * @covers ::compare
     * @covers ::inArray
     * @covers ::compareValues
     * @covers ::arrayIsList
     * @covers ::arrayIsObject
     *
     * @param array $needle
     * @param array $haystack
     */
    public function testCanRecursivelyDoInArrayChecksWith(array $needle, array $haystack) {
        $this->assertTrue(
            $this->comparator->compare($needle, $haystack),
            'Comparator did not return in a correct manner, should return true'
        );
    }

    /**
     * @covers ::compare
     * @covers ::inArray
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Haystack does not contain any list elements, needle can't be found:
     */
    public function testThrowsExceptionWhenNeedleValueIsAListAndHaystackDoesNotContainAnyLists() {
        $this->comparator->compare([[1]], [1]);
    }

    /**
     * @covers ::compare
     * @covers ::inArray
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Haystack does not contain any object elements, needle can't be found:
     */
    public function testThrowsExceptionWhenNeedleValueIsAnObjectAndHaystackDoesNotContainAnyObjects() {
        $this->comparator->compare([
            ['foo' => 'bar']
        ], [
            1
        ]);
    }

    /**
     * @covers ::compare
     * @covers ::inArray
     * @expectedException InvalidArgumentException
     */
    public function testThrowsExceptionWhenNoValuesInTheListIsPresentInTheHaystack() {
        $this->expectExceptionMessage(<<<'EXCEPTION'
Needle is not present in the haystack:
================================================================================
Needle
================================================================================
3

================================================================================
Haystack
================================================================================
[
    1,
    2,
    4
]
EXCEPTION
        );
        $this->comparator->compare([
            1, 2, 3
        ], [
            1, 2, 4
        ]);
    }

    /**
     * @covers ::compare
     * @covers ::formatExceptionMessage
     * @expectedException InvalidArgumentException
     */
    public function testCanProperlyFormatExceptionMessages() {
        $this->expectExceptionMessage(<<<'EXCEPTION'
The needle is a numerically indexed array, while the haystack is not:
================================================================================
Needle
================================================================================
[
    1,
    2,
    [
        1,
        2
    ]
]

================================================================================
Haystack
================================================================================
{
    "foo": "bar",
    "bar": [
        1,
        2
    ]
}
EXCEPTION
        );
        $this->comparator->compare([
            1,
            2,
            [1, 2]
        ], [
            'foo' => 'bar',
            'bar' => [1, 2]
        ]);
    }

    /**
     * @dataProvider getDataForCompareCheck
     * @covers ::compare
     * @covers ::compareValues
     * @covers ::arrayIsList
     * @covers ::arrayIsObject
     *
     * @param array $needle
     * @param array $haystack
     */
    public function testCanRecursivelyCompareAssociativeArraysWith(array $needle, array $haystack) {
        $this->assertTrue(
            $this->comparator->compare($needle, $haystack),
            'Comparator did not return in a correct manner, should return true'
        );
    }

    /**
     * @covers ::compare
     * @expectedException OutOfRangeException
     * @expectedExceptionMessage Haystack is missing the "bar" key:
     */
    public function testThrowsExceptionWhenComparingObjectsAndKeyIsMissingFromHaystack() {
        $this->comparator->compare([
            'foo' => [
                'bar' => 'baz'
            ]
        ], [
            'foo' => [
                'baz' => 'bar'
        ]]);
    }

    /**
     * @covers ::compare
     * @expectedException InvalidArgumentException
     */
    public function testThrowsExceptionWhenRegularStringKeyValueDoesNotMatch() {
        $this->expectExceptionMessage(<<<'EXCEPTION'
Value mismatch for key "foo":
================================================================================
Needle
================================================================================
{
    "foo": "bar"
}

================================================================================
Haystack
================================================================================
{
    "foo": "baz"
}
EXCEPTION
        );
        $this->comparator->compare([
            'foo' => 'bar'
        ], [
            'foo' => 'baz'
        ]);
    }

    /**
     * @covers ::compare
     */
    public function testCanRecursivelyMatchKeysInObjects() {
        $this->assertTrue(
            $this->comparator->compare([
                'bar' => 'foo',
                'baz' => [
                    'foo' => 'bar',
                    'baz' => [
                        'baz' => 'foobar',
                    ],
                ]
            ], [
                'foo' => 'bar',
                'bar' => 'foo',
                'baz' => [
                    'foo' => 'bar',
                    'bar' => 'foo',
                    'baz' => [
                        'foo' => 'bar',
                        'bar' => 'foo',
                        'baz' => 'foobar',
                    ],
                ],
            ]),
            'Comparator did not return in a correct manner, should return true'
        );
    }

    /**
     * @covers ::compare
     * @expectedException InvalidArgumentException
     */
    public function testThrowsExceptionWhenRegularStringKeyValueInDeepObjectDoesNotMatch() {
        $this->expectExceptionMessage(<<<'EXCEPTION'
Value mismatch for key "foo":
================================================================================
Needle
================================================================================
{
    "bar": "foo",
    "foo": "foobar"
}

================================================================================
Haystack
================================================================================
{
    "bar": "foo",
    "foo": "foo"
}
EXCEPTION
        );
        $this->comparator->compare([
            'foo' => [
                'foo' => [
                    'bar' => 'foo',
                    'foo' => 'foobar'
                ],
            ],
        ], [
            'foo' => [
                'foo' => [
                    'bar' => 'foo',
                    'foo' => 'foo'
                ],
            ],
        ]);
    }

    /**
     * @dataProvider getDataForSpecificKeyInListChecks
     * @covers ::compare
     *
     * @param array $needle
     * @param array $haystack
     */
    public function testCanCompareSpecificIndexesInAListWith(array $needle, array $haystack) {
        $this->assertTrue(
            $this->comparator->compare($needle, $haystack),
            'Comparator did not return in a correct manner, should return true'
        );
    }

    /**
     * @covers ::compare
     * @expectedException OutOfRangeException
     */
    public function testThrowsExceptionWhenTargetingAListIndexWithAKeyThatDoesNotExist() {
        $this->expectExceptionMessage(<<<'EXCEPTION'
Haystack is missing the "foo" key:
================================================================================
Needle
================================================================================
{
    "foo[0]": "bar"
}

================================================================================
Haystack
================================================================================
{
    "bar": "foo"
}
EXCEPTION
        );
        $this->comparator->compare([
            'foo[0]' => 'bar',
        ], [
            'bar' => 'foo',
        ]);
    }

    /**
     * @dataProvider getDataForSpecificKeyInListChecksWithInvalidData
     * @covers ::compare
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The element at key "foo" in the haystack is not a list:
     */
    public function testThrowsExceptionWhenTargetingAListIndexWithAKeyThatContains(array $needle, array $haystack) {
        $this->comparator->compare($needle, $haystack);
    }

    /**
     * @covers ::compare
     * @expectedException OutOfRangeException
     */
    public function testThrowsExceptionWhenTargetingAListIndexThatDoesNotExist() {
        $this->expectExceptionMessage(<<<'EXCEPTION'
The index "2" does not exist in the list:
================================================================================
Needle
================================================================================
{
    "foo[2]": "bar"
}

================================================================================
Haystack
================================================================================
{
    "foo": [
        "foo",
        "bar"
    ]
}
EXCEPTION
        );
        $this->comparator->compare([
            'foo[2]' => 'bar'
        ], [
            'foo' => [
                'foo',
                'bar',
            ],
        ]);
    }
}
