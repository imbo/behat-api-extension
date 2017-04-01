<?php
namespace Imbo\BehatApiExtension\ArrayContainsComparator\Matcher;

use InvalidArgumentException;

/**
 * Check if the length of an array is at least a given length
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 */
class ArrayMinLength {
    /**
     * Match the min length of an array
     *
     * @param array $array An array
     * @param int $minLength The expected minimum length of $array
     * @throws InvalidArgumentException
     * @return void
     */
    public function __invoke($array, $minLength) {
        // Encode / decode to make sure we have a "list"
        $array = json_decode(json_encode($array));

        if (!is_array($array)) {
            throw new InvalidArgumentException(sprintf(
                'Only numerically indexed arrays are supported, got "%s".',
                gettype($array)
            ));
        }

        $minLength = (int) $minLength;
        $actualLength = count($array);

        if ($actualLength < $minLength) {
            throw new InvalidArgumentException(sprintf(
                'Expected array to have more than or equal to %d entries, actual length: %d.',
                $minLength,
                $actualLength
            ));
        }
    }
}
