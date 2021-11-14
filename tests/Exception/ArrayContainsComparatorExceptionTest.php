<?php declare(strict_types=1);
namespace Imbo\BehatApiExtension\Exception;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\BehatApiExtension\Exception\ArrayContainsComparatorException
 */
class ArrayContainsComparatorExceptionTest extends TestCase
{
    /**
     * @return array<array-key, array{message: string, needle: array<string, string>, haystack: array<string, string>, formattedMessage: string}>
     */
    public function getExceptionData(): array
    {
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
                'needle' => ['needle' => 'value'],
                'haystack' => ['haystack' => 'value'],
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
     * @covers ::__construct
     * @param string $message
     * @param array<string, string> $needle
     * @param array<string, string> $haystack
     * @param string $formattedMessage
     */
    public function testCanProperlyFormatErrorMessages(string $message, array $needle, array $haystack, string $formattedMessage): void
    {
        $this->expectException(ArrayContainsComparatorException::class);
        $this->expectExceptionMessage($formattedMessage);
        throw new ArrayContainsComparatorException($message, 0, null, $needle, $haystack);
    }
}
