<?php
namespace Imbo\BehatApiExtension\Exception;

use PHPUnit_Framework_TestCase;

/**
 * @coversDefaultClass Imbo\BehatApiExtension\Exception\ArrayContainsComparatorException
 */
class ArrayContainsComparatorExceptionTest extends PHPUnit_Framework_TestCase {
    /**
     * Data provider
     *
     * @return array[]
     */
    public function getExceptionData() {
        return [
            'with no needle / haystack' => [
                'message' => $someMessage = 'some message',
                'needle' => [],
                'haystack' => [],
                'formattedMessage' => <<<MESSAGE
{$someMessage}

================================================================================
= Needle =======================================================================
================================================================================
[]

================================================================================
= Haystack =====================================================================
================================================================================
[]
MESSAGE
            ],
            'with needle and haystack' => [
                'message' => $someMessage = 'some message',
                'needle' => $needle = ['needle' => 'value'],
                'haystack' => $haystack = ['haystack' => 'value'],
                'formattedMessage' => <<<MESSAGE
{$someMessage}

================================================================================
= Needle =======================================================================
================================================================================
{
    "needle": "value"
}

================================================================================
= Haystack =====================================================================
================================================================================
{
    "haystack": "value"
}
MESSAGE
            ],
        ];
    }

    /**
     * @dataProvider getExceptionData
     * @expectedException Imbo\BehatApiExtension\Exception\ArrayContainsComparatorException
     * @covers ::__construct
     *
     * @param string $message
     * @param array $needle
     * @param array $haystack
     * @param string $formattedMessage
     */
    public function testCanProperlyFormatErrorMessages($message, array $needle, array $haystack, $formattedMessage) {
        $this->expectExceptionMessage($formattedMessage);
        throw new ArrayContainsComparatorException($message, 0, null, $needle, $haystack);
    }
}
