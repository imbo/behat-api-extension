<?php
namespace Imbo\BehatApiExtension\ArrayContainsComparator\Matcher;

use InvalidArgumentException;

/**
 * Check if the length of an array is at most a given length
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 */
class ArrayMaxLength {
    /**
     * Match the max length of an array
     *
     * @param array $array An array
     * @param int $maxLength The expected maximum length of $array
     * @throws InvalidArgumentException
     * @return void
     */
    public function __invoke($array, $maxLength) {
        // Encode / decode to make sure we have a "list"
        $array = json_decode(json_encode($array));

        if (!is_array($array)) {
            throw new InvalidArgumentException(sprintf(
                'Only numerically indexed arrays are supported, got "%s".',
                gettype($array)
            ));
        }

        $maxLength = (int) $maxLength;
        $actualLength = count($array);

        if ($actualLength > $maxLength) {
            throw new InvalidArgumentException(sprintf(
                'Expected array to have less than or equal to %d entries, actual length: %d.',
                $maxLength,
                $actualLength
            ));
        }
    }
}
