<?php declare(strict_types=1);
namespace Imbo\BehatApiExtension\ArrayContainsComparator\Matcher;

use InvalidArgumentException;

/**
 * Match a string against a regular expression pattern
 */
class RegExp
{
    /**
     * Match the value of a string against a regular expression
     *
     * @param string|int|float $subject A string, integer or floating point value
     * @param string $pattern A valid regular expression pattern
     * @throws InvalidArgumentException
     */
    public function __invoke(string|int|float $subject, string $pattern): bool
    {
        $subject = (string) $subject;

        if (!preg_match($pattern, $subject)) {
            throw new InvalidArgumentException(sprintf(
                'Subject "%s" did not match pattern "%s".',
                $subject,
                $pattern,
            ));
        }

        return true;
    }
}
