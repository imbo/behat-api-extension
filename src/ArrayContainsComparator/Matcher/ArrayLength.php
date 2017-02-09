<?php
namespace Imbo\BehatApiExtension\ArrayContainsComparator\Matcher;

use InvalidArgumentException;

/**
 * Match the length of an array
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 */
class ArrayLength implements Matcher {
    /**
     * {@inheritdoc}
     */
    public function match($value, $something) {
        // Encode / decode to fix "objects"
        $value = json_decode(json_encode($value));

        if (!is_array($value)) {
            throw new InvalidArgumentException(sprintf(
                '@%s function can only be used with array values, got "%s".',
                $this->getName(),
                gettype($value)
            ));
        }

        $expectedLength = (int) $something;
        $actualLength = count($value);

        if ($actualLength !== $expectedLength) {
            throw new InvalidArgumentException(sprintf(
                '@%s: Wrong length for array, expected %d, got %d.',
                $this->getName(),
                $expectedLength,
                $actualLength
            ));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName() {
        return 'arrayLength';
    }
}
