<?php
namespace Imbo\BehatApiExtension\ArrayContainsComparator\Matcher;

use InvalidArgumentException;

/**
 * Match the type of a value
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 */
class VariableType implements Matcher {
    /**
     * Valid types
     *
     * @var string[]
     */
    protected $validTypes = [
        'int', 'integer',
        'bool', 'boolean',
        'float', 'double',
        'string',
        'array',
        'object',
        'null',
        'scalar',
    ];

    /**
     * {@inheritdoc}
     */
    public function match($value, $something) {
        $expectedType = $this->normalizeType($something);

        if (!in_array($expectedType, $this->validTypes)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid type for the @%s matcher: "%s"',
                $this->getName(),
                $expectedType
            ));
        }

        if ($expectedType === 'scalar' && is_scalar($value)) {
            return;
        }

        // Encode / decode the value to easier check for objects
        $value = json_decode(json_encode($value));

        // Get the actual type of the value
        $actualType = strtolower(gettype($value));

        if ($expectedType !== $actualType) {
            throw new InvalidArgumentException(sprintf(
                '@%s: Expected type "%s", got "%s".',
                $this->getName(),
                $expectedType,
                $actualType
            ));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName() {
        return 'variableType';
    }

    /**
     * Normalize the type
     *
     * @param string $type The type from the scenario
     * @return string Returns a normalized type
     */
    protected function normalizeType($type) {
        return strtolower(preg_replace(
            ['/^bool$/i', '/^int$/i', '/^float$/i'],
            ['boolean', 'integer', 'double'],
            $type
        ));
    }
}
