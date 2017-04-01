<?php
namespace Imbo\BehatApiExtension\ArrayContainsComparator\Matcher;

use InvalidArgumentException;

/**
 * Match the length of an array
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 */
class ArrayLength {
    /**
     * Match the exact length of an array
     *
     * @param array $array An array
     * @param int $length The expected exact length of $array
     * @throws InvalidArgumentException
     * @return void
     */
    public function __invoke($array, $length) {
        // Encode / decode to make sure we have a "list"
        $array = json_decode(json_encode($array));

        if (!is_array($array)) {
            throw new InvalidArgumentException(sprintf(
                'Only numerically indexed arrays are supported, got "%s".',
                gettype($array)
            ));
        }

        $length = (int) $length;
        $actualLength = count($array);

        if ($actualLength !== $length) {
            throw new InvalidArgumentException(sprintf(
                'Expected array to have exactly %d entries, actual length: %d.',
                $length,
                $actualLength
            ));
        }
    }
}
