<?php
namespace Imbo\BehatApiExtension;

use Imbo\BehatApiException\ArrayContainsComparator\Matcher;
use InvalidArgumentException;
use LengthException;
use LogicException;
use OutOfRangeException;
use RuntimeException;
use UnexpectedValueException;
use Closure;

/**
 * Comparator class used for the response body comparisons
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 */
class ArrayContainsComparator {
    /**
     * Recursively loop over the $haystack array and make sure all the items in $needle exists
     *
     * To clarify, the method (and other methods in the class) refers to "lists" and "objects". A
     * "list" is a numerically indexed array, and an "object" is an associative array.
     *
     * @param array $needle The needle array
     * @param array $haystack The haystack array
     * @throws Exception Throws an exception on error
     * @return boolean
     */
    public function compare(array $needle, array $haystack) {
        $needleIsList = $this->arrayIsList($needle);
        $haystackIsList = $this->arrayIsList($haystack);

        // If the needle is a numerically indexed array, the haystack needs to be one as well
        if ($needleIsList && !$haystackIsList) {
            throw new InvalidArgumentException($this->formatExceptionMessage(
                'The needle is a numerically indexed array, while the haystack is not:',
                $needle,
                $haystack
            ));
        } else if ($needleIsList && $haystackIsList) {
            // Both arrays are numerically indexed arrays
            return $this->inArray($needle, $haystack);
        }

        // Loop over all key => value pairs in the needle array and make sure they match the
        // haystack array
        foreach ($needle as $key => $value) {
            // Check if the needle key refers to a specific array key in the haystack
            $match = [];

            if (preg_match('/^(?<key>.*?)\[(?<index>[\d]+)\]$/', $key, $match)) {
                $realKey = $match['key'] ?: null;
                $index = (int) $match['index'];

                if ($realKey && !array_key_exists($realKey, $haystack)) {
                    // The key does not exist in the haystack
                    throw new OutOfRangeException($this->formatExceptionMessage(
                        sprintf(
                            'Haystack is missing the "%s" key:',
                            $realKey
                        ),
                        $needle,
                        $haystack
                    ));
                }

                // If a key has been specified, use that part of the haystack to compare against,
                // if no key exists, simply use the haystack as-is.
                $subHaystack = $realKey ? $haystack[$realKey] : $haystack;

                if (!is_array($subHaystack) || !$this->arrayIsList($subHaystack)) {
                    // The sub haystack is not a list, so we can't really target indexes
                    throw new InvalidArgumentException($this->formatExceptionMessage(
                        sprintf(
                            'The element at key "%s" in the haystack is not a list:',
                            $realKey
                        ),
                        $needle,
                        $haystack
                    ));
                } else if (!array_key_exists($index, $subHaystack)) {
                    // The index does not exist in the haystack
                    throw new OutOfRangeException($this->formatExceptionMessage(
                        sprintf(
                            'The index "%d" does not exist in the list:',
                            $index
                        ),
                        $needle,
                        $haystack
                    ));
                }

                if (is_array($value)) {
                    // The value is an array, do a recursive check
                    $this->compare($value, $subHaystack[$index]);
                } else {
                    // Regular value, compare
                    $this->compareValues($value, $subHaystack[$index]);
                }
            } else {
                // Use array_key_exists instead of isset as the value of the key can be null, which
                // causes isset to return false
                if (!array_key_exists($key, $haystack)) {
                    // The key does not exist in the haystack
                    throw new OutOfRangeException($this->formatExceptionMessage(
                        sprintf(
                            'Haystack is missing the "%s" key:',
                            $key
                        ),
                        $needle,
                        $haystack
                    ));
                }

                if (is_array($value)) {
                    // If the value is an array, recurse
                    $this->compare($value, $haystack[$key]);
                } else if (!$this->compareValues($value, $haystack[$key])) {
                    // Comparison of values failed
                    throw new InvalidArgumentException($this->formatExceptionMessage(
                        sprintf('Value mismatch for key "%s":', $key),
                        $needle,
                        $haystack
                    ));
                }
            }
        }

        return true;
    }

    /**
     * Compare a value from a needle with a value from the haystack
     *
     * @param mixed $needleValue
     * @param mixed $haystackValue
     * @return boolean
     */
    protected function compareValues($needleValue, $haystackValue) {
        return $needleValue === $haystackValue;
    }

    /**
     * Make sure all values in the $needle array is present in the $haystack array
     *
     * @param array $needle
     * @param array $haystack
     * @throws InvalidArgumentException
     * @return boolean
     */
    protected function inArray(array $needle, array $haystack) {
        // Loop over all the values in the needle array, and make sure each and every one is in some
        // way present in the haystack, in a recursive manner.
        foreach ($needle as $value) {
            if (is_array($value)) {
                // If the value is an array we need to do a recursive compare / inArray check
                if ($this->arrayIsList($value)) {
                    $listElements = array_filter($haystack, function($element) {
                        return is_array($element) && $this->arrayIsList($element);
                    });

                    if (empty($listElements)) {
                        // The haystack does not contain any list elements
                        throw new InvalidArgumentException($this->formatExceptionMessage(
                            'Haystack does not contain any list elements, needle can\'t be found:',
                            $value,
                            $haystack
                        ));
                    }

                    array_map(function ($haystack) use ($value) {
                        return $this->inArray($value, $haystack);
                    }, $listElements);
                } else {
                    $objectElements = array_filter($haystack, function($element) {
                        return is_array($element) && $this->arrayIsObject($element);
                    });

                    if (empty($objectElements)) {
                        // The haystack does not contain any object elements
                        throw new InvalidArgumentException($this->formatExceptionMessage(
                            'Haystack does not contain any object elements, needle can\'t be found:',
                            $value,
                            $haystack
                        ));
                    }

                    array_map(function ($haystack) use ($value) {
                        return $this->compare($value, $haystack);
                    }, $objectElements);
                }
            } else {
                $result = array_map(function($haystackElement) use ($value) {
                    return $this->compareValues($value, $haystackElement);
                }, $haystack);

                if (empty(array_filter($result))) {
                    throw new InvalidArgumentException($this->formatExceptionMessage(
                        'Needle is not present in the haystack:',
                        $value,
                        $haystack
                    ));
                }
            }
        }

        // All's right with the world!
        return true;
    }

    /**
     * See if a PHP array is a JSON array
     *
     * @param array $array The array to check
     * @return boolean True if the array is a numerically indexed array
     */
    protected function arrayIsList(array $array) {
        return json_encode($array)[0] === '[';
    }

    /**
     * See if a PHP array is a JSON object
     *
     * @param array $array The array to check
     * @return boolean True if the array is an associative array
     */
    protected function arrayIsObject(array $array) {
        return json_encode($array)[0] === '{';
    }

    /**
     * Format an exception message with needle and haystack encoded as pretty-printed JSON
     *
     * @param string $message
     * @param mixed $needle
     * @param mixed $haystack
     * @return string
     */
    protected function formatExceptionMessage($message, $needle, $haystack) {
        $line = str_repeat('=', 80);

        return
            $message . PHP_EOL .
            $line . PHP_EOL .
            'Needle' . PHP_EOL .
            $line . PHP_EOL .
            json_encode($needle, JSON_PRETTY_PRINT) . PHP_EOL . PHP_EOL .
            $line . PHP_EOL .
            'Haystack' . PHP_EOL .
            $line . PHP_EOL .
            json_encode($haystack, JSON_PRETTY_PRINT);
    }
}
