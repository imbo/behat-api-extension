<?php
namespace Imbo\BehatApiExtension\ArrayContainsComparator\Matcher;

use InvalidArgumentException;

/**
 * Check if a numeric value is less than another value
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 */
class LessThan {
    /**
     * Match a numeric value
     *
     * @param numeric $number A variable
     * @param numeric $max The max value of $number
     * @throws InvalidArgumentException
     * @return void
     */
    public function __invoke($number, $max) {
        if (!is_numeric($number)) {
            throw new InvalidArgumentException(sprintf(
                '"%s" is not numeric.',
                $number
            ));
        }

        if (!is_numeric($max)) {
            throw new InvalidArgumentException(sprintf(
                '"%s" is not numeric.',
                $max
            ));
        }

        if ($number >= $max) {
            throw new InvalidArgumentException(sprintf(
                '"%s" is not less than "%s".',
                $number,
                $max
            ));
        }
    }
}
