<?php declare(strict_types=1);
namespace Imbo\BehatApiExtension;

use Imbo\BehatApiExtension\ArrayContainsComparator\Matcher;
use Imbo\BehatApiExtension\Exception\ArrayContainsComparatorException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\BehatApiExtension\ArrayContainsComparator
 */
class ArrayContainsComparatorTest extends TestCase
{
    private ArrayContainsComparator $comparator;

    public function setUp(): void
    {
        $this->comparator = new ArrayContainsComparator();
    }

    /**
     * @return array<string,array{needle:array,haystack:array}>
     */
    public static function getDataForInArrayCheck(): array
    {
        $scalarHaystack = [1, 2, 1.1, 2.2, 'foo', 'bar', true, false];
        $haystackWithArrayElements = [
            1, 2, 3,
            [4, 5, 6],
            [7, 8, 9],
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
            'lists as needles' => [
                'needle' => [[4, 6], [8, 9]],
                'haystack' => $haystackWithArrayElements,
            ],
            'complex nested structure' => [
                'needle' => [
                    [
                        [
                            2,
                        ],
                        4,
                        [
                            'list' => [
                                [
                                    [
                                        [
                                            'new-list' =>
                                            [
                                                8,
                                            ],
                                        ],
                                    ],
                                ],
                                5,
                                [
                                    6,
                                ],
                                [
                                    [
                                        7,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    3,
                    4,
                ],
                'haystack' => [
                    [
                        [
                            1,
                            2,
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
                                                8,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    3,
                    4,
                ],
            ],
            'lists with elements in random order' => [
                'needle' => [
                    3, 2, 1,
                ],
                'haystack' => [
                    1, 2, 3,
                ],
            ],
            'objects in random order' => [
                'needle' => [
                    [
                        'id' => 2,
                    ],
                    [
                        'id' => 1,
                        'gameId' => 1,
                        'someKey' => 1,
                    ],
                    [
                        'someKey' => 4,
                    ],
                    [
                        'gameId' => 3,
                        'someKey' => 3,
                    ],
                ],
                'haystack' => [
                    [
                        'id' => 1,
                        'gameId' => 1,
                        'someKey' => 1,
                    ],
                    [
                        'id' => 2,
                        'gameId' => 2,
                        'someKey' => 2,
                    ],
                    [
                        'id' => 3,
                        'gameId' => 3,
                        'someKey' => 3,
                    ],
                    [
                        'id' => 4,
                        'gameId' => 4,
                        'someKey' => 4,
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string,array{needle:array,haystack:array}>
     */
    public static function getDataForCompareCheck(): array
    {
        return [
            'simple key value objects' => [
                'needle' => [
                    'string values' => 'string',
                    'integer value' => 123,
                    'float value' => 1.23,
                    'bool value' => true,
                    'null value' => null,
                ],
                'haystack' => [
                    'string values' => 'string',
                    'integer value' => 123,
                    'float value' => 1.23,
                    'bool value' => true,
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
     * @return array<array{needle:array,haystack:array}>
     */
    public static function getDataForSpecificKeyInListChecks(): array
    {
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
     * @return array<array{needle:array<string,string>,haystack:array<mixed>,exceptionMessage:string}>
     */
    public static function getDataForSpecificKeyInListChecksWithInvalidData(): array
    {
        return [
            'a string' => [
                'needle' => [
                    'foo[0]' => 'bar',
                ],
                'haystack' => [
                    'foo' => 'bar',
                ],
                'exceptionMessage' => <<<'EXCEPTION'
The element at key "foo" in the haystack object is not a list.

================================================================================
= Needle =======================================================================
================================================================================
{
    "foo[0]": "bar"
}

================================================================================
= Haystack =====================================================================
================================================================================
{
    "foo": "bar"
}
EXCEPTION
            ],
            'an object' => [
                'needle' => [
                    'foo[0]' => 'bar',
                ],
                'haystack' => [
                    'foo' => [
                        'foo' => 'bar',
                    ],
                ],
                'exceptionMessage' => <<<'EXCEPTION'
The element at key "foo" in the haystack object is not a list.

================================================================================
= Needle =======================================================================
================================================================================
{
    "foo[0]": "bar"
}

================================================================================
= Haystack =====================================================================
================================================================================
{
    "foo": {
        "foo": "bar"
    }
}
EXCEPTION
            ],
        ];
    }

    /**
     * @return array<array{function:string,callback:callable,needle:array{key:string},haystack:array{key:string|array<int>}}>
     */
    public static function getCustomFunctionsAndData(): array
    {
        return [
            '@arrayLength' => [
                'function' => 'arrayLength',
                'callback' => new Matcher\ArrayLength(),
                'needle' => [
                    'key' => '@arrayLength(3)',
                ],
                'haystack' => [
                    'key' => [1, 2, 3],
                ],
            ],
            '@arrayMaxLength' => [
                'function' => 'arrayMaxLength',
                'callback' => new Matcher\ArrayMaxLength(),
                'needle' => [
                    'key' => '@arrayMaxLength(3)',
                ],
                'haystack' => [
                    'key' => [1, 2, 3],
                ],
            ],
            '@arrayMinLength' => [
                'function' => 'arrayMinLength',
                'callback' => new Matcher\ArrayMinLength(),
                'needle' => [
                    'key' => '@arrayMinLength(3)',
                ],
                'haystack' => [
                    'key' => [1, 2, 3],
                ],
            ],
            '@variableType' => [
                'function' => 'variableType',
                'callback' => new Matcher\VariableType(),
                'needle' => [
                    'key' => '@variableType(array)',
                ],
                'haystack' => [
                    'key' => [1, 2, 3],
                ],
            ],
            '@regExp' => [
                'function' => 'regExp',
                'callback' => new Matcher\RegExp(),
                'needle' => [
                    'key' => '@regExp(/foo/i)',
                ],
                'haystack' => [
                    'key' => 'FOO',
                ],
            ],
            '@jwt' => [
                'function' => 'jwt',
                'callback' => (new Matcher\JWT(new ArrayContainsComparator()))->addToken('my jwt', ['some' => 'data'], 'secret'),
                'needle' => [
                    'key' => '@jwt(my jwt)',
                ],
                'haystack' => [
                    'key' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzb21lIjoiZGF0YSJ9.g1aG08iQyPPwCTJHCxRrkKoYmLiHbBNdarcBQkCPMG4',
                ],
            ],
            '@customFunction' => [
                'function' => 'customFunction',
                'callback' => fn (string $subject, string $param): bool => strtoupper($subject) === $param,
                'needle' => [
                    'key' => '@customFunction(BAR)',
                ],
                'haystack' => [
                    'key' => 'bar',
                ],
            ],
        ];
    }

    /**
     * @return array<array{function:string,callback:callable,needle:array<string,string>,haystack:array<string,mixed>,errorMessage:string}>
     */
    public static function getCustomFunctionsAndDataThatWillFail(): array
    {
        return [
            // @arrayLength
            [
                'function' => 'arrayLength',
                'callback' => new Matcher\ArrayLength(),
                'needle' => [
                    'key' => '@arrayLength(3)',
                ],
                'haystack' => [
                    'key' => [1],
                ],
                'errorMessage' => 'Function "arrayLength" failed with error message: "Expected array to have exactly 3 entries, actual length: 1.".',
            ],

            // @arrayMaxLength
            [
                'function' => 'arrayMaxLength',
                'callback' => new Matcher\ArrayMaxLength(),
                'needle' => [
                    'key' => '@arrayMaxLength(3)',
                ],
                'haystack' => [
                    'key' => [1, 2, 3, 4],
                ],
                'errorMessage' => 'Function "arrayMaxLength" failed with error message: "Expected array to have less than or equal to 3 entries, actual length: 4."',
            ],

            // @arrayMinLength
            [
                'function' => 'arrayMinLength',
                'callback' => new Matcher\ArrayMinLength(),
                'needle' => [
                    'key' => '@arrayMinLength(3)',
                ],
                'haystack' => [
                    'key' => [1, 2],
                ],
                'errorMessage' => 'Function "arrayMinLength" failed with error message: "Expected array to have more than or equal to 3 entries, actual length: 2.".',
            ],

            // @variableType
            [
                'function' => 'variableType',
                'callback' => new Matcher\VariableType(),
                'needle' => [
                    'key' => '@variableType(array)',
                ],
                'haystack' => [
                    'key' => 'some string value',
                ],
                'errorMessage' => 'Function "variableType" failed with error message: "Expected variable type "array", got "string".".',
            ],

            // @regExp
            [
                'function' => 'regExp',
                'callback' => new Matcher\RegExp(),
                'needle' => [
                    'key' => '@regExp(/foo/)',
                ],
                'haystack' => [
                    'key' => 'FOO',
                ],
                'errorMessage' => 'Function "regExp" failed with error message: "Subject "FOO" did not match pattern "/foo/".".',
            ],

            // @customFunction
            [
                'function' => 'customFunction',
                'callback' => function (): void {
                    throw new InvalidArgumentException('Some custom error message');
                },
                'needle' => [
                    'key' => '@customFunction(BAR)',
                ],
                'haystack' => [
                    'key' => 'foo',
                ],
                'errorMessage' => 'Function "customFunction" failed with error message: "Some custom error message"',
            ],
        ];
    }

    /**
     * @covers ::compare
     */
    public function testThrowsExceptionWhenMatchingANumericallyIndexedArrayAgainstAnAssociativeArray(): void
    {
        $this->expectException(ArrayContainsComparatorException::class);
        $this->expectExceptionMessage(
            <<<'EXCEPTION'
The needle is a list, while the haystack is not.

================================================================================
= Needle =======================================================================
================================================================================
[
    1,
    2,
    3
]

================================================================================
= Haystack =====================================================================
================================================================================
{
    "foo": "bar"
}
EXCEPTION
        );
        $this->comparator->compare([1, 2, 3], ['foo' => 'bar']);
    }

    /**
     * @dataProvider getDataForInArrayCheck
     * @covers ::compare
     * @covers ::inArray
     * @covers ::compareValues
     * @covers ::arrayIsList
     * @covers ::arrayIsObject
     */
    public function testCanRecursivelyDoInArrayChecksWith(array $needle, array $haystack): void
    {
        $this->assertTrue(
            $this->comparator->compare($needle, $haystack),
            'Comparator did not return in a correct manner, should return true',
        );
    }

    /**
     * @covers ::compare
     * @covers ::inArray
     */
    public function testThrowsExceptionWhenNeedleValueIsAListAndHaystackDoesNotContainAnyLists(): void
    {
        $this->expectException(ArrayContainsComparatorException::class);
        $this->expectExceptionMessage(
            <<<'EXCEPTION'
Haystack does not contain any list elements, needle can't be found.

================================================================================
= Needle =======================================================================
================================================================================
[
    1
]

================================================================================
= Haystack =====================================================================
================================================================================
[
    1
]
EXCEPTION
        );
        $this->comparator->compare([[1]], [1]);
    }

    /**
     * @covers ::compare
     * @covers ::inArray
     */
    public function testThrowsExceptionWhenNeedleValueIsAnObjectAndHaystackDoesNotContainAnyObjects(): void
    {
        $this->expectException(ArrayContainsComparatorException::class);
        $this->expectExceptionMessage(
            <<<'EXCEPTION'
Haystack does not contain any object elements, needle can't be found.

================================================================================
= Needle =======================================================================
================================================================================
{
    "foo": "bar"
}

================================================================================
= Haystack =====================================================================
================================================================================
[
    [
        1,
        2,
        3
    ]
]
EXCEPTION
        );
        $this->comparator->compare([
            ['foo' => 'bar'],
        ], [
            [1, 2, 3],
        ]);
    }

    /**
     * @covers ::compare
     * @covers ::inArray
     */
    public function testThrowsExceptionWhenHaystackListIsMissingValuesFromNeedleList(): void
    {
        $this->expectException(ArrayContainsComparatorException::class);
        $this->expectExceptionMessage(
            <<<'EXCEPTION'
The list in needle was not found in the list elements in the haystack.

================================================================================
= Needle =======================================================================
================================================================================
[
    1,
    3
]

================================================================================
= Haystack =====================================================================
================================================================================
[
    [
        1,
        2
    ],
    [
        3,
        4
    ],
    [
        5,
        6
    ]
]
EXCEPTION
        );
        $this->comparator->compare([
            [1, 3],
        ], [
            [1, 2],
            [3, 4],
            [5, 6],
        ]);
    }

    /**
     * @covers ::compare
     * @covers ::inArray
     */
    public function testThrowsExceptionWhenHaystackObjectIsMissingValuesFromNeedleObject(): void
    {
        $this->expectException(ArrayContainsComparatorException::class);
        $this->expectExceptionMessage(
            <<<'EXCEPTION'
The object in needle was not found in the object elements in the haystack.

================================================================================
= Needle =======================================================================
================================================================================
{
    "id": 2,
    "gameId": 3
}

================================================================================
= Haystack =====================================================================
================================================================================
[
    {
        "id": 1,
        "gameId": 1,
        "value": 1
    },
    {
        "id": 2,
        "gameId": 2,
        "value": 2
    },
    {
        "id": 3,
        "gameId": 3,
        "value": 3
    }
]
EXCEPTION
        );
        $this->comparator->compare([
            [
                'id' => 1,
                'gameId' => 1,
            ],
            [
                'id' => 2,
                'gameId' => 3,
            ],
        ], [
            [
                'id' => 1,
                'gameId' => 1,
                'value' => 1,
            ], [
                'id' => 2,
                'gameId' => 2,
                'value' => 2,
            ], [
                'id' => 3,
                'gameId' => 3,
                'value' => 3,
            ],
        ]);
    }

    /**
     * @dataProvider getDataForCompareCheck
     * @covers ::compare
     * @covers ::compareValues
     * @covers ::arrayIsList
     * @covers ::arrayIsObject
     */
    public function testCanRecursivelyCompareAssociativeArraysWith(array $needle, array $haystack): void
    {
        $this->assertTrue(
            $this->comparator->compare($needle, $haystack),
            'Comparator did not return in a correct manner, should return true',
        );
    }

    /**
     * @covers ::compare
     */
    public function testThrowsExceptionWhenComparingObjectsAndKeyIsMissingFromHaystack(): void
    {
        $this->expectException(ArrayContainsComparatorException::class);
        $this->expectExceptionMessage(
            <<<'EXCEPTION'
Haystack object is missing the "bar" key.

================================================================================
= Needle =======================================================================
================================================================================
{
    "bar": "baz"
}

================================================================================
= Haystack =====================================================================
================================================================================
{
    "baz": "bar"
}
EXCEPTION
        );
        $this->comparator->compare([
            'foo' => [
                'bar' => 'baz',
            ],
        ], [
            'foo' => [
                'baz' => 'bar',
            ], ]);
    }

    /**
     * @covers ::compare
     */
    public function testThrowsExceptionWhenRegularStringKeyValueDoesNotMatch(): void
    {
        $this->expectException(ArrayContainsComparatorException::class);
        $this->expectExceptionMessage(
            <<<'EXCEPTION'
Value mismatch for key "foo" in haystack object.

================================================================================
= Needle =======================================================================
================================================================================
{
    "foo": "bar"
}

================================================================================
= Haystack =====================================================================
================================================================================
{
    "foo": "baz"
}
EXCEPTION
        );
        $this->comparator->compare([
            'foo' => 'bar',
        ], [
            'foo' => 'baz',
        ]);
    }

    /**
     * @covers ::compare
     * @covers ::compareValues
     */
    public function testCanRecursivelyMatchKeysInObjects(): void
    {
        $this->assertTrue(
            $this->comparator->compare([
                'bar' => 'foo',
                'baz' => [
                    'foo' => 'bar',
                    'baz' => [
                        'baz' => 'foobar',
                    ],
                ],
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
            'Comparator did not return in a correct manner, should return true',
        );
    }

    /**
     * @covers ::compare
     */
    public function testThrowsExceptionWhenRegularStringKeyValueInDeepObjectDoesNotMatch(): void
    {
        $this->expectException(ArrayContainsComparatorException::class);
        $this->expectExceptionMessage(
            <<<'EXCEPTION'
Value mismatch for key "foo" in haystack object.

================================================================================
= Needle =======================================================================
================================================================================
{
    "bar": "foo",
    "foo": "foobar"
}

================================================================================
= Haystack =====================================================================
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
                    'foo' => 'foobar',
                ],
            ],
        ], [
            'foo' => [
                'foo' => [
                    'bar' => 'foo',
                    'foo' => 'foo',
                ],
            ],
        ]);
    }

    /**
     * @dataProvider getDataForSpecificKeyInListChecks
     * @covers ::compare
     * @covers ::compareValues
     */
    public function testCanCompareSpecificIndexesInAListWith(array $needle, array $haystack): void
    {
        $this->assertTrue(
            $this->comparator->compare($needle, $haystack),
            'Comparator did not return in a correct manner, should return true',
        );
    }

    /**
     * @covers ::compare
     */
    public function testThrowsExceptionWhenTargetingAListIndexWithAKeyThatDoesNotExist(): void
    {
        $this->expectException(ArrayContainsComparatorException::class);
        $this->expectExceptionMessage(
            <<<'EXCEPTION'
Haystack object is missing the "foo" key.

================================================================================
= Needle =======================================================================
================================================================================
{
    "foo[0]": "bar"
}

================================================================================
= Haystack =====================================================================
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
     */
    public function testThrowsExceptionWhenTargetingAListIndexWithAKeyThatContains(array $needle, array $haystack, string $exceptionMessage): void
    {
        $this->expectException(ArrayContainsComparatorException::class);
        $this->expectExceptionMessage($exceptionMessage);
        $this->comparator->compare($needle, $haystack);
    }

    /**
     * @covers ::compare
     */
    public function testThrowsExceptionWhenTargetingAListIndexThatDoesNotExist(): void
    {
        $this->expectException(ArrayContainsComparatorException::class);
        $this->expectExceptionMessage(
            <<<'EXCEPTION'
The index "2" does not exist in the haystack list.

================================================================================
= Needle =======================================================================
================================================================================
{
    "foo[2]": "bar"
}

================================================================================
= Haystack =====================================================================
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
            'foo[2]' => 'bar',
        ], [
            'foo' => [
                'foo',
                'bar',
            ],
        ]);
    }

    /**
     * @covers ::compare
     */
    public function testThrowsExceptionOnValueMismatchWhenTargetingSpecificIndexInList(): void
    {
        $this->expectException(ArrayContainsComparatorException::class);
        $this->expectExceptionMessage(
            <<<'EXCEPTION'
Value mismatch for index "0" in haystack list.

================================================================================
= Needle =======================================================================
================================================================================
"foo"

================================================================================
= Haystack =====================================================================
================================================================================
"foobar"
EXCEPTION
        );
        $this->comparator->compare([
            '[0]' => 'foo',
        ], [
            'foobar',
        ]);
    }

    /**
     * @dataProvider getCustomFunctionsAndData
     * @covers ::addFunction
     * @covers ::compare
     * @covers ::compareValues
     */
    public function testCanUseCustomFunctionMatcher(string $function, callable $callback, array $needle, array $haystack): void
    {
        $this->assertTrue(
            $this->comparator
                ->addFunction($function, $callback)
                ->compare($needle, $haystack),
            'Comparator did not return in a correct manner, should return true',
        );
    }

    /**
     * @covers ::compare
     * @covers ::compareValues
     */
    public function testPerformsARegularStringComparisonWhenSpecifiedCustomFunctionMatcherDoesNotExist(): void
    {
        $this->expectException(ArrayContainsComparatorException::class);
        $this->expectExceptionMessage(
            <<<'EXCEPTION'
Value mismatch for key "key" in haystack object.

================================================================================
= Needle =======================================================================
================================================================================
{
    "key": "@foo(123)"
}

================================================================================
= Haystack =====================================================================
================================================================================
{
    "key": "some value"
}
EXCEPTION
        );
        $this->comparator->compare(
            ['key' => '@foo(123)'],
            ['key' => 'some value'],
        );
    }

    /**
     * @dataProvider getCustomFunctionsAndDataThatWillFail
     * @covers ::addFunction
     * @covers ::compare
     * @covers ::compareValues
     */
    public function testThrowsExceptionWhenCustomFunctionMatcherFails(string $function, callable $callback, array $needle, array $haystack, string $errorMessage): void
    {
        $this->expectException(ArrayContainsComparatorException::class);
        $this->expectExceptionMessage($errorMessage);
        $this->assertTrue(
            $this->comparator
                ->addFunction($function, $callback)
                ->compare($needle, $haystack),
            'Comparator did not return in a correct manner, should return true',
        );
    }

    /**
     * @covers ::compare
     * @covers ::inArray
     */
    public function testSupportsInArrayCheckWhenListsAreInADeepStructure(): void
    {
        $needle = [
            'foo' => [
                'bar' => 'baz',
                'listOfLists' => [
                    [1],
                    [1, 2],
                    [1, 2, 3],
                    [1, 2, 3, 4],
                    [1, 3, 4],
                    [1, 4],
                    [
                        'key1' => 1,
                    ],
                    [
                        'key1' => 1,
                        'key2' => 2,
                    ],
                    [
                        'key1' => 1,
                        'key2' => 2,
                        'key3' => 3,
                    ],
                    [
                        'key1' => 1,
                        'key3' => 3,
                    ],
                ],
            ],
        ];
        $haystack = [
            'foo' => [
                'bar' => 'baz',
                'listOfLists' => [
                    [1, 2, 3],
                    [1, 2, 3, 4],
                    [
                        'key1' => 1,
                    ],
                    [
                        'key1' => 1,
                        'key2' => 2,
                    ],
                    [
                        'key1' => 1,
                        'key2' => 2,
                        'key3' => 3,
                    ],
                ],
            ],
        ];
        $this->assertTrue($this->comparator->compare($needle, $haystack));
    }

    /**
     * @covers ::getMatcherFunction
     */
    public function testCanReturnRegisteredMatcherFunction(): void
    {
        $f = fn (): bool => true;
        $this->comparator->addFunction('function', $f);
        $this->assertSame(
            $f,
            $this->comparator->getMatcherFunction('function'),
            'Incorrect matcher function returned',
        );
    }

    /**
     * @covers ::getMatcherFunction
     */
    public function testThrowsExceptionWhenGettingFunctionThatDoesNotExist(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No matcher function registered for "function".');
        $this->comparator->getMatcherFunction('function');
    }
}
