<?php declare(strict_types=1);
namespace Imbo\BehatApiExtension\ArrayContainsComparator\Matcher;

use InvalidArgumentException;

/**
 * Check if the length of an array is at most a given length
 */
class ArrayMaxLength
{
    /**
     * Match the max length of an array
     *
     * @param array $array An array
     * @param int|string $maxLength The expected maximum length of $array
     * @throws InvalidArgumentException
     */
    public function __invoke(array $array, int|string $maxLength): bool
    {
        // Encode / decode to make sure we have a "list"
        /** @var mixed */
        $array = json_decode((string) json_encode($array));

        if (!is_array($array)) {
            throw new InvalidArgumentException(sprintf(
                'Only numerically indexed arrays are supported, got "%s".',
                gettype($array),
            ));
        }

        $maxLength = (int) $maxLength;
        $actualLength = count($array);

        if ($actualLength > $maxLength) {
            throw new InvalidArgumentException(sprintf(
                'Expected array to have less than or equal to %d entries, actual length: %d.',
                $maxLength,
                $actualLength,
            ));
        }

        return true;
    }
}
