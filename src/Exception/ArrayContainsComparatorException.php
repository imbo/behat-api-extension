<?php
namespace Imbo\BehatApiExtension\Exception;

use Exception;

/**
 * Array contains comparator exception
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 */
class ArrayContainsComparatorException extends AssertionFailedException {
    /**
     * Class constructor
     *
     * @param string $message Exception message
     * @param int $code Exception code
     * @param Exception $previous Previous exception in the stack
     * @param mixed $needle The needle in the comparison
     * @param mixed $haystack The haystack in the comparison
     */
    public function __construct($message, $code = 0, Exception $previous = null, $needle = null, $haystack = null) {
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
