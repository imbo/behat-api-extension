<?php declare(strict_types=1);
namespace Imbo\BehatApiExtension\Context\Initializer;

use Imbo\BehatApiExtension\Context\ApiClientAwareContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(ApiClientAwareInitializer::class)]
class ApiClientAwareInitializerTest extends TestCase
{
    public function testInjectsClientWhenInitializingContext(): void
    {
        // Set up a socket for the test case, try all ports between 8000 and 8079. If no ports are
        // available the test case will be marked as skipped. This is to get past the base URI
        // validation
        set_error_handler(fn () => true);
        $sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        if (false === $sock) {
            $this->fail('Unable to create socket');
        }

        $result = false;

        for ($port = 8000; $port < 8079; $port++) {
            if ($result = socket_bind($sock, 'localhost', $port)) {
                break;
            }
        }

        restore_error_handler();

        if (!$result) {
            // No port was available
            $this->markTestSkipped('Could not create a socket, skipping test for now.');
        }

        // Listen for connections
        if (!socket_listen($sock)) {
            $this->markTestSkipped('Unable to listen for a connection, skipping test for now.');
        }

        $baseUri = sprintf('http://localhost:%d', $port);

        /** @var MockObject&ApiClientAwareContext */
        $context = $this->createMock(ApiClientAwareContext::class);
        $context
            ->expects($this->once())
            ->method('initializeClient')
            ->with(['base_uri' => $baseUri]);

        $initializer = new ApiClientAwareInitializer([
            'base_uri' => $baseUri,
        ]);
        $initializer->initializeContext($context);

        // Close socket used in test case
        socket_close($sock);
    }
}
