<?php declare(strict_types=1);
namespace Imbo\BehatApiExtension\ArrayContainsComparator\Matcher;

use InvalidArgumentException;

/**
 * Check if the length of an array is at least a given length
 */
class ArrayMinLength
{
    /**
     * Match the min length of an array
     *
     * @param array $array An array
     * @param int|string $minLength The expected minimum length of $array
     * @throws InvalidArgumentException
     */
    public function __invoke($array, $minLength): bool
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

        $minLength = (int) $minLength;
        $actualLength = count($array);

        if ($actualLength < $minLength) {
            throw new InvalidArgumentException(sprintf(
                'Expected array to have more than or equal to %d entries, actual length: %d.',
                $minLength,
                $actualLength,
            ));
        }

        return true;
    }
}
