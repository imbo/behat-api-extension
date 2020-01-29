<?php declare(strict_types=1);
namespace Imbo\BehatApiExtension\ArrayContainsComparator\Matcher;

use InvalidArgumentException;

/**
 * Check if a numeric value is less than another value
 */
class LessThan {
    /**
     * Match a numeric value
     *
     * @param mixed $number A variable
     * @param mixed $max The max value of $number
     * @throws InvalidArgumentException
     */
    public function __invoke($number, $max) : void {
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
