<?php declare(strict_types=1);
namespace Imbo\BehatApiExtension\ArrayContainsComparator\Matcher;

use InvalidArgumentException;

/**
 * Check if a numeric value is greater than another value
 */
class GreaterThan {
    /**
     * Match a numeric value
     *
     * @param numeric $number A variable
     * @param numeric $min The minimum value of $number
     * @throws InvalidArgumentException
     */
    public function __invoke($number, $min) : void {
        if (!is_numeric($number)) {
            throw new InvalidArgumentException(sprintf(
                '"%s" is not numeric.',
                $number
            ));
        }

        if (!is_numeric($min)) {
            throw new InvalidArgumentException(sprintf(
                '"%s" is not numeric.',
                $min
            ));
        }

        if ($number <= $min) {
            throw new InvalidArgumentException(sprintf(
                '"%s" is not greater than "%s".',
                $number,
                $min
            ));
        }
    }
}
