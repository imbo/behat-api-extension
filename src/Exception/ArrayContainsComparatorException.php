<?php declare(strict_types=1);
namespace Imbo\BehatApiExtension\Exception;

use Throwable;

/**
 * Array contains comparator exception
 */
class ArrayContainsComparatorException extends AssertionFailedException
{
    public function __construct(string $message, int $code = 0, ?Throwable $previous = null, mixed $needle = null, mixed $haystack = null)
    {
        $message .= "\n\n" . sprintf(
            <<<MESSAGE
================================================================================
= Needle =======================================================================
================================================================================
%s

================================================================================
= Haystack =====================================================================
================================================================================
%s

MESSAGE
            ,
            json_encode($needle, JSON_PRETTY_PRINT),
            json_encode($haystack, JSON_PRETTY_PRINT),
        );

        parent::__construct($message, $code, $previous);
    }
}
