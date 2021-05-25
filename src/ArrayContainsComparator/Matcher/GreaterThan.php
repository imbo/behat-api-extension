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
     * @param mixed $number A variable
     * @param mixed $min The minimum value of $number
     * @throws InvalidArgumentException
     */
    public function __invoke($number, $min) : bool {
        if (!is_numeric($number)) {
            throw new InvalidArgumentException(sprintf(
                '"%s" is not numeric.',
                (string) $number
            ));
        }

        if (!is_numeric($min)) {
            throw new InvalidArgumentException(sprintf(
                '"%s" is not numeric.',
                (string) $min
            ));
        }

        if ($number <= $min) {
            throw new InvalidArgumentException(sprintf(
                '"%s" is not greater than "%s".',
                $number,
                $min
            ));
        }

        return true;
    }
}
