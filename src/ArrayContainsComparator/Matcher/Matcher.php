<?php
namespace Imbo\BehatApiExtension\ArrayContainsComparator\Matcher;

/**
 * Interface for matchers
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 */
interface Matcher {
    /**
     * Match a value against something
     *
     * @param mixed $value The value to match. What it should be matched against should be set in
     *                     the constructor of the implementation.
     * @param mixed $something What to match the value against.
     * @throws InvalidArgumentException If there is no match, the method should throw an exception
     * @return void Nothing needs to be returned. If no exception is thrown, the match was a success
     */
    function match($value, $something);

    /**
     * Get the function name of the matcher as used in the Gherkin steps. Should not be prefixed
     * with "@". See existing implementations for examples.
     *
     * @return string
     */
    function getName();
}
