<?php declare(strict_types=1);
namespace Imbo\BehatApiExtension\ArrayContainsComparator\Matcher;

use InvalidArgumentException;

/**
 * Match the type of a value
 */
class VariableType
{
    /**
     * Valid types
     *
     * @var array<string>
     */
    protected array $validTypes = [
        'int', 'integer',
        'bool', 'boolean',
        'float', 'double',
        'string',
        'array',
        'object',
        'null',
        'scalar',
        'any',
    ];

    /**
     * Match a variable type
     *
     * @param mixed $variable A variable
     * @param string $expectedTypes The expected types of $variable, separated by |
     * @throws InvalidArgumentException
     */
    public function __invoke(mixed $variable, string $expectedTypes): bool
    {
        $expectedTypes = $this->normalizeTypes($expectedTypes);

        foreach ($expectedTypes as $expectedType) {
            if (!in_array($expectedType, $this->validTypes)) {
                throw new InvalidArgumentException(sprintf(
                    'Unsupported variable type: "%s".',
                    $expectedType,
                ));
            }
        }

        if (in_array('any', $expectedTypes)) {
            return true;
        }

        // Encode / decode the value to easier check for objects
        /** @var mixed */
        $variable = json_decode((string) json_encode($variable));

        // Get the actual type of the value
        $actualType = strtolower(gettype($variable));

        foreach ($expectedTypes as $expectedType) {
            if (
                ($expectedType === 'scalar' && is_scalar($variable)) ||
                $expectedType === $actualType
            ) {
                return true;
            }
        }

        throw new InvalidArgumentException(sprintf(
            'Expected variable type "%s", got "%s".',
            join('|', $expectedTypes),
            $actualType,
        ));
    }

    /**
     * Normalize the type
     *
     * @param string $types The types from the scenario
     * @return array<string> Returns an array of normalized types
     */
    protected function normalizeTypes(string $types): array
    {
        $types = array_map(
            fn (string $type): string => trim(strtolower($type)),
            explode('|', $types),
        );

        /** @var array<string> */
        return preg_replace(
            ['/^bool$/i', '/^int$/i', '/^float$/i'],
            ['boolean', 'integer', 'double'],
            $types,
        );
    }
}
