<?php declare(strict_types=1);
namespace Imbo\BehatApiExtension;

use Exception;
use Imbo\BehatApiExtension\Exception\ArrayContainsComparatorException;
use InvalidArgumentException;

/**
 * Comparator class used for the response body comparisons
 */
class ArrayContainsComparator
{
    /**
     * Custom value matching functions
     *
     * Keys are the names of the functions, and the values represent an invokable piece of code, be
     * it a function name or the name of an invokable class.
     *
     * @var array<string,callable>
     */
    protected array $functions = [];

    /**
     * Add a custom matcher function
     *
     * If an existing function exists with the same name it will be replaced
     */
    public function addFunction(string $name, callable $callback): self
    {
        $this->functions[$name] = $callback;

        return $this;
    }

    /**
     * Get a matcher function by name
     */
    public function getMatcherFunction(string $name): callable
    {
        if (!isset($this->functions[$name])) {
            throw new InvalidArgumentException(sprintf(
                'No matcher function registered for "%s".',
                $name,
            ));
        }

        return $this->functions[$name];
    }

    /**
     * Recursively loop over the $haystack array and make sure all the items in $needle exists
     *
     * To clarify, the method (and other methods in the class) refers to "lists" and "objects". A
     * "list" is a numerically indexed array, and an "object" is an associative array.
     *
     * @param array<scalar|array> $needle
     * @param array<mixed> $haystack
     */
    public function compare(array $needle, array $haystack): bool
    {
        $needleIsList   = $this->arrayIsList($needle);
        $haystackIsList = $this->arrayIsList($haystack);

        // If the needle is a numerically indexed array, the haystack needs to be one as well
        if ($needleIsList && !$haystackIsList) {
            throw new ArrayContainsComparatorException(
                'The needle is a list, while the haystack is not.',
                0,
                null,
                $needle,
                $haystack,
            );
        } elseif ($needleIsList) {
            // Both arrays are numerically indexed arrays
            return $this->inArray($needle, $haystack);
        }

        // Loop over all key => value pairs in the needle array and make sure they match the
        // haystack array
        foreach ($needle as $key => $value) {
            // Check if the needle key refers to a specific array key in the haystack
            $match = [];

            if (preg_match('/^(?<key>.*?)\[(?<index>[\d]+)\]$/', (string) $key, $match)) {
                $realKey = $match['key'];
                $index   = (int) $match['index'];

                if (!empty($realKey) && !array_key_exists($realKey, $haystack)) {
                    // The key does not exist in the haystack
                    throw new ArrayContainsComparatorException(
                        sprintf('Haystack object is missing the "%s" key.', $realKey),
                        0,
                        null,
                        $needle,
                        $haystack,
                    );
                }

                // If a key has been specified, use that part of the haystack to compare against,
                // if no key exists, simply use the haystack as-is.
                /** @var mixed */
                $subHaystack = $realKey ? $haystack[$realKey] : $haystack;

                if (!is_array($subHaystack) || !$this->arrayIsList($subHaystack)) {
                    // The sub haystack is not a list, so we can't really target indexes
                    throw new ArrayContainsComparatorException(
                        sprintf('The element at key "%s" in the haystack object is not a list.', $realKey),
                        0,
                        null,
                        $needle,
                        $haystack,
                    );
                } elseif (!array_key_exists($index, $subHaystack)) {
                    // The index does not exist in the haystack
                    throw new ArrayContainsComparatorException(
                        sprintf('The index "%d" does not exist in the haystack list.', $index),
                        0,
                        null,
                        $needle,
                        $haystack,
                    );
                }

                if (is_array($value)) {
                    if (!is_array($subHaystack[$index])) {
                        throw new ArrayContainsComparatorException(
                            sprintf('Expected haystack element on index "%d" to be an array, got "%s".', $index, gettype($subHaystack[$index])),
                            0,
                            null,
                            $needle,
                            $haystack,
                        );
                    }

                    // The value is an array, do a recursive check
                    $this->compare($value, $subHaystack[$index]);
                } elseif (!$this->compareValues($value, $subHaystack[$index])) {
                    // Comparison of values failed
                    throw new ArrayContainsComparatorException(
                        sprintf('Value mismatch for index "%d" in haystack list.', $index),
                        0,
                        null,
                        $value,
                        $subHaystack[$index],
                    );
                }
            } else {
                // Use array_key_exists instead of isset as the value of the key can be null, which
                // causes isset to return false
                if (!array_key_exists($key, $haystack)) {
                    // The key does not exist in the haystack
                    throw new ArrayContainsComparatorException(
                        sprintf('Haystack object is missing the "%s" key.', $key),
                        0,
                        null,
                        $needle,
                        $haystack,
                    );
                }

                if (is_array($value)) {
                    if (!is_array($haystack[$key])) {
                        throw new ArrayContainsComparatorException(
                            sprintf('Expected haystack value with key "%s" to be an array, got "%s".', $key, gettype($haystack[$key])),
                            0,
                            null,
                            $needle,
                            $haystack,
                        );
                    }

                    // If the value is an array, recurse
                    $this->compare($value, $haystack[$key]);
                } elseif (!$this->compareValues($value, $haystack[$key])) {
                    // Comparison of values failed
                    throw new ArrayContainsComparatorException(
                        sprintf('Value mismatch for key "%s" in haystack object.', $key),
                        0,
                        null,
                        $needle,
                        $haystack,
                    );
                }
            }
        }

        return true;
    }

    /**
     * Compare a value from a needle with a value from the haystack
     *
     * Based on the value of the needle, this method will perform a regular value comparison, or a
     * custom function match.
     */
    protected function compareValues(mixed $needleValue, mixed $haystackValue): bool
    {
        $match = [];

        // List of available function names
        $functions = array_map(function ($value) {
            return preg_quote($value, '/');
        }, array_keys($this->functions));

        // Dynamic pattern, based on the keys in the functions list
        $pattern = sprintf(
            '/^@(?<function>%s)\((?<params>.*?)\)$/',
            implode('|', $functions),
        );

        if (is_string($needleValue) && $functions && preg_match($pattern, $needleValue, $match)) {
            // Custom function matching
            $function = $match['function'];
            $params = $match['params'];

            try {
                $this->functions[$function]($haystackValue, $params);
                return true;
            } catch (Exception $e) {
                throw new ArrayContainsComparatorException(
                    sprintf(
                        'Function "%s" failed with error message: "%s".',
                        $function,
                        $e->getMessage(),
                    ),
                    0,
                    $e,
                    $needleValue,
                    $haystackValue,
                );
            }
        }

        // Regular value matching
        return $needleValue === $haystackValue;
    }

    /**
     * Make sure all values in the $needle array is present in the $haystack array
     *
     * @param array<array|scalar> $needle
     * @param array $haystack
     */
    protected function inArray(array $needle, array $haystack): bool
    {
        // Loop over all the values in the needle array, and make sure each and every one is in some
        // way present in the haystack, in a recursive manner.
        foreach ($needle as $needleValue) {
            if (is_array($needleValue)) {
                // If the value is an array we need to do a recursive compare / inArray check
                if ($this->arrayIsList($needleValue)) {
                    // The needle value is a list, so we only want to compare it to lists in the
                    // haystack
                    $listElementsInHaystack = array_filter($haystack, function ($element) {
                        return is_array($element) && $this->arrayIsList($element);
                    });

                    if (empty($listElementsInHaystack)) {
                        throw new ArrayContainsComparatorException(
                            'Haystack does not contain any list elements, needle can\'t be found.',
                            0,
                            null,
                            $needleValue,
                            $haystack,
                        );
                    }

                    $result = array_filter($listElementsInHaystack, function (array $haystackListElement) use ($needleValue): bool {
                        try {
                            return $this->inArray($needleValue, $haystackListElement);
                        } catch (ArrayContainsComparatorException $e) {
                            // If any error occurs, swallow it and return false to mark this as a
                            // failure
                            return false;
                        }
                    });

                    // Result is empty, which means the needle was not found in the haystack
                    if (empty($result)) {
                        throw new ArrayContainsComparatorException(
                            'The list in needle was not found in the list elements in the haystack.',
                            0,
                            null,
                            $needleValue,
                            $haystack,
                        );
                    }
                } else {
                    // The needle value is an object, so we only want to compare it to objects in
                    // the haystack
                    $objectElementsInHaystack = array_filter($haystack, function ($element): bool {
                        return is_array($element) && $this->arrayIsObject($element);
                    });

                    if (empty($objectElementsInHaystack)) {
                        throw new ArrayContainsComparatorException(
                            'Haystack does not contain any object elements, needle can\'t be found.',
                            0,
                            null,
                            $needleValue,
                            $haystack,
                        );
                    }

                    $result = array_filter($objectElementsInHaystack, function (array $haystackObjectElement) use ($needleValue) {
                        try {
                            return $this->compare($needleValue, $haystackObjectElement);
                        } catch (ArrayContainsComparatorException $e) {
                            // If any error occurs, swallow it and return false to mark this as a
                            // failure
                            return false;
                        }
                    });

                    // Result is empty, which means the needle was not found in the haystack
                    if (empty($result)) {
                        throw new ArrayContainsComparatorException(
                            'The object in needle was not found in the object elements in the haystack.',
                            0,
                            null,
                            $needleValue,
                            $haystack,
                        );
                    }
                }
            } else {
                $result = array_map(function ($haystackElement) use ($needleValue) {
                    return $this->compareValues($needleValue, $haystackElement);
                }, $haystack);

                if (empty(array_filter($result))) {
                    throw new ArrayContainsComparatorException(
                        'Needle is not present in the haystack.',
                        0,
                        null,
                        $needleValue,
                        $haystack,
                    );
                }
            }
        }

        // All's right with the world!
        return true;
    }

    /**
     * See if a PHP array is a JSON array
     */
    protected function arrayIsList(array $array): bool
    {
        $encoded = json_encode($array);

        return false !== $encoded && $encoded[0] === '[';
    }

    /**
     * See if a PHP array is a JSON object
     */
    protected function arrayIsObject(array $array): bool
    {
        $encoded = json_encode($array);

        return false !== $encoded && $encoded[0] === '{';
    }
}
