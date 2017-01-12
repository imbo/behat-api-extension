<?php
namespace Imbo\BehatApiExtension;

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
     * Recursively look over an array and make sure all the items in $needle exists
     *
     * @param array $haystack The haystack array
     * @param array $needle The needle array
     * @param string $path The current array key "path" to use in exception messages
     * @throws Exception Throws an exception on error
     */
    public function compare(array $haystack, array $needle, $path = null) {
        // Loop over all key => value pairs in the needle array and make sure they match the
        // haystack array
        foreach ($needle as $key => $value) {
            // Update the key path to use in exception messages
            $keyPath = preg_replace('/\[[\d+]\]/', '', ltrim(sprintf('%s.%s', $path, $key), '.'));

            // Parse the value
            $value = $this->parseNeedleValue($value);
            $valueIsCallback = $value instanceof Closure;

            // See if the key refers to an element in an array
            $match = [];

            if (preg_match('/^\[(\d+)\]$/', $key, $match)) {
                // The matcher refers to an index in a numerical array
                $index = (int) $match[1];

                if (!isset($haystack[$index])) {
                    throw new OutOfRangeException(sprintf(
                        'Index %d does not exist in the haystack array',
                        $index
                    ));
                }

                if ($valueIsCallback) {
                    $this->compareHaystackValueWithCallback($haystack[$index], $value, $keyPath);
                    continue;
                }

                if (is_array($value) && is_array($haystack[$index])) {
                    // Recursively compare the haystack against the needle
                    $this->compare($haystack[$index], $value);
                    continue;
                }

                if ($value !== $haystack[$index]) {
                    throw new InvalidArgumentException(sprintf(
                        'Item on index %d in array at haystack key "%s" does not match value %s',
                        $index,
                        $keyPath ?: '<root>',
                        $value
                    ));
                }

                continue;
            } else if (preg_match('/^(.*?)\[([\d+])\]$/', $key, $match)) {
                $key = $match[1];
                $index = (int) $match[2];

                if (!is_array($haystack[$key])) {
                    throw new UnexpectedValueException(sprintf(
                        'Element at haystack key "%s" is not an array.',
                        $keyPath
                    ));
                }

                if (!isset($haystack[$key][$index])) {
                    throw new OutOfRangeException(sprintf(
                        'Index %d does not exist in the array at haystack key "%s"',
                        $index,
                        $keyPath
                    ));
                }

                if ($valueIsCallback) {
                    $this->compareHaystackValueWithCallback($haystack[$key][$index], $value, $keyPath);
                    continue;
                }

                if (is_array($value) && is_array($haystack[$key][$index])) {
                    // Recursively compare the haystack against the needle
                    $this->compare($haystack[$key][$index], $value);
                    continue;
                }

                if ($value !== $haystack[$key][$index]) {
                    $valueStr = (is_array($value)) ? var_export($value, true) : $value;
                    throw new InvalidArgumentException(sprintf(
                        'Item on index %d in array at haystack key "%s" does not match value %s',
                        $index,
                        $keyPath,
                        $valueStr
                    ));
                }

                continue;
            }

            if (!array_key_exists($key, $haystack)) {
                throw new OutOfRangeException(sprintf(
                    'Key is missing from the haystack: %s',
                    $keyPath
                ));
            }

            // Match types
            $haystackValueType = gettype($haystack[$key]);
            $needleValueType   = gettype($value);

            if ($valueIsCallback) {
                $this->compareHaystackValueWithCallback($haystack[$key], $value, $keyPath);
                continue;
            }

            if ($haystackValueType !== $needleValueType) {
                throw new UnexpectedValueException(sprintf(
                    'Type mismatch for haystack key "%s" (haystack type: %s, needle type: %s)',
                    $keyPath,
                    $haystackValueType,
                    $needleValueType
                ));
            }

            if (is_scalar($value) || is_null($value)) {
                if ($haystack[$key] !== $value) {
                    throw new InvalidArgumentException(sprintf(
                        'Value mismatch for haystack key "%s": %s != %s',
                        $keyPath,
                        $haystack[$key],
                        $value
                    ));
                }

                continue;
            }

            if (is_array($value)) {
                if (key($value) === 0) {
                    // Numerically indexed array. Loop over all values and see if they are in the
                    // haystack value
                    foreach ($value as $v) {
                        if (!in_array($v, $haystack[$key])) {
                            throw new InvalidArgumentException(sprintf(
                                'The value %s is not present in the haystack array at key "%s"',
                                $v,
                                $keyPath
                            ));
                        }
                    }
                } else {
                    // Associative array, recurse
                    $this->compare($haystack[$key], $value, $keyPath);
                }

                continue;
            }

            // @codeCoverageIgnoreStart
            throw new LogicException(sprintf(
                'Value has not been matched for key: %s. This should never happen, so please file an issue.',
                $keyPath
            ));
            // @codeCoverageIgnoreEnd
        }

        return true;
    }

    /**
     * Compare a hay stack value with a callback
     *
     * @param mixed $value The value to compare
     * @param callable $callback The callback to use
     * @param string $keyPath The path to the array key
     * @throws InvalidArgumentException Throws an exception if the result from the callback is not
     *                                  a success.
     */
    private function compareHaystackValueWithCallback($value, $callback, $keyPath) {
        $result = $callback($value);
        $function = key($result);
        $success = $result[$function];

        if (!$success) {
            throw new InvalidArgumentException(sprintf(
                '"%s" function failed for the "%s" haystack key',
                $function,
                $keyPath
            ));
        }
    }

    /**
     * Parse a value from a needle to see if it represents a callback
     *
     * If the $value looks like any of the following patterns, callback will be returned:
     *
     * <re>/pattern/</re>
     * @length(num)
     * @atLeast(num)
     * @atMost(num)
     *
     * @param mixed $value The value to parse. If the value is not a string, it will be returned as
     *                     is.
     * @return mixed|callback Returns the value as is, or a callback.
     */
    public function parseNeedleValue($value) {
        if (!is_string($value)) {
            return $value;
        }

        if (preg_match('|^<re>(.*?)</re>$|', $value, $match)) {
            $pattern = $match[1]; // The actual regular expression

            return function($value) use ($pattern) {
                return $this->matchString($pattern, $value);
            };
        } else if (preg_match('/^@length\(([\d]+)\)$/', $value, $match)) {
            $length = (int) $match[1]; // The length to match

            return function($value) use ($length) {
                return $this->arrayLengthIs($value, $length);
            };
        } else if (preg_match('/^@atLeast\(([\d]+)\)$/', $value, $match)) {
            $min = (int) $match[1];

            return function($value) use ($min) {
                return $this->arrayLengthIsAtLeast($value, $min);
            };
        } else if (preg_match('/^@atMost\(([\d]+)\)$/', $value, $match)) {
            $max = (int) $match[1];

            return function($value) use ($max) {
                return $this->arrayLengthIsAtMost($value, $max);
            };
        }

        return $value;
    }

    /**
     * Match pattern against a scalar value
     *
     * @param string $pattern A valid regular expression pattern
     * @param scalar $value The value to match the pattern against. If the value is not a scalar an
     *                      InvalidArgumentException exception will be thrown. The value is cast to
     *                      a string before the match occurs.
     * @return array Returns an array with the key being the function name, and the value being a
     *               boolean representing if $value matched $pattern or not.
     * @throws InvalidArgumentException
     */
    public function matchString($pattern, $value) {
        if (!is_scalar($value)) {
            throw new InvalidArgumentException('Regular expression matching must be used with scalars.');
        }

        return ['regular expression' => (boolean) preg_match($pattern, (string) $value)];
    }

    /**
     * Check that an array is of a specific length
     *
     * @param mixed $array The array to check
     * @param int $length The length we want to array to be
     * @return array Returns an array with the key being the function name, and the value being a
     *               boolean representing if $array is of length $length or not
     * @throws InvalidArgumentException If $array is not an array an exception will be thrown
     */
    public function arrayLengthIs($array, $length) {
        if (!is_array($array)) {
            throw new InvalidArgumentException('@length function can only be used with arrays.');
        }

        return ['@length' => count($array) === $length];
    }

    /**
     * Check that an array is at least of a specific length
     *
     * @param mixed $array The array to check
     * @param int $min The minimum length of the array
     * @return array Returns an array with the key being the function name, and the value being a
     *               boolean representing if $array is at least $min length of not
     * @throws InvalidArgumentException If $array is not an array an exception will be thrown
     */
    public function arrayLengthIsAtLeast($array, $min) {
        if (!is_array($array)) {
            throw new InvalidArgumentException('@atLeast function can only be used with arrays.');
        }

        return ['@atLeast' => count($array) >= $min];
    }

    /**
     * Check that an array is at most of a specific length
     *
     * @param mixed $array The array to check
     * @param int $max The maximum length of the array
     * @return array Returns an array with the key being the function name, and the value being a
     *               boolean representing if $array is at most $max length of not
     * @throws InvalidArgumentException If $array is not an array an exception will be thrown
     */
    public function arrayLengthIsAtMost($array, $max) {
        if (!is_array($array)) {
            throw new InvalidArgumentException('@atMost function can only be used with arrays.');
        }

        return ['@atMost' => count($array) <= $max];
    }
}
