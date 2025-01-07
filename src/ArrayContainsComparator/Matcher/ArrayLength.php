<?php declare(strict_types=1);
namespace Imbo\BehatApiExtension\ArrayContainsComparator\Matcher;

use InvalidArgumentException;

/**
 * Match the length of an array
 */
class ArrayLength
{
    /**
     * Match the exact length of an array
     *
     * @param array<mixed> $array An array
     * @param int|string $length The expected exact length of $array
     * @throws InvalidArgumentException
     */
    public function __invoke(array $array, int|string $length): bool
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

        $length = (int) $length;
        $actualLength = count($array);

        if ($actualLength !== $length) {
            throw new InvalidArgumentException(sprintf(
                'Expected array to have exactly %d entries, actual length: %d.',
                $length,
                $actualLength,
            ));
        }

        return true;
    }
}
