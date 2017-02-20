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
     * @param array $needle The needle in the comparison
     * @param array $haystack The haystack in the comparison
     * @param array $progress The progress of the comparison
     */
    public function __construct($message, $code = 0, Exception $previous = null, array $needle = [], array $haystack = [], array $progress = []) {
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

================================================================================
= Progress =====================================================================
================================================================================
%s

MESSAGE
            ,
                json_encode($needle, JSON_PRETTY_PRINT),
                json_encode($haystack, JSON_PRETTY_PRINT),
                json_encode($progress, JSON_PRETTY_PRINT)
        );

        parent::__construct($message, $code, $previous);
    }
}
