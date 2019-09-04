<?php declare(strict_types=1);
namespace Imbo\BehatApiExtension\Exception;

use Exception;

/**
 * Array contains comparator exception
 */
class ArrayContainsComparatorException extends AssertionFailedException {
    public function __construct(string $message, int $code = 0, Exception $previous = null, $needle = null, $haystack = null) {
        // Reusable line of ='s
        $line = str_repeat('=', 80);

        // Format the error message
        $message .= PHP_EOL . PHP_EOL . sprintf(<<<MESSAGE
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
                json_encode($haystack, JSON_PRETTY_PRINT)
        );

        parent::__construct($message, $code, $previous);
    }
}
