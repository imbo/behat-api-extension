<?php declare(strict_types=1);
namespace Imbo\BehatApiExtension\ArrayContainsComparator\Matcher;

use InvalidArgumentException;

/**
 * Match the type of a value
 */
class VariableType {
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
     * Match a variable type
     *
     * @param mixed $variable A variable
     * @param string $expectedType The expected type of $variable
     * @throws InvalidArgumentException
     */
    public function __invoke($variable, string $expectedType) : void {
        $expectedType = $this->normalizeType($expectedType);

        if (!in_array($expectedType, $this->validTypes)) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported variable type: "%s".',
                $expectedType
            ));
        }

        if ($expectedType === 'scalar' && is_scalar($variable)) {
            return;
        }

        // Encode / decode the value to easier check for objects
        $variable = json_decode(json_encode($variable));

        // Get the actual type of the value
        $actualType = strtolower(gettype($variable));

        if ($expectedType !== $actualType) {
            throw new InvalidArgumentException(sprintf(
                'Expected variable type "%s", got "%s".',
                $expectedType,
                $actualType
            ));
        }
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
