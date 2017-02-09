<?php
namespace Imbo\BehatApiExtension\ArrayContainsComparator\Matcher;

use InvalidArgumentException;

/**
 * Check if the length of an array is at least a given length
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 */
class ArrayMinLength implements Matcher {
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

        $minLength = (int) $something;
        $actualLength = count($value);

        if ($actualLength < $minLength) {
            throw new InvalidArgumentException(sprintf(
                '@%s: Wrong length for array, min length is %d, the array has a length of %d.',
                $this->getName(),
                $minLength,
                $actualLength
            ));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName() {
        return 'arrayMinLength';
    }
}
