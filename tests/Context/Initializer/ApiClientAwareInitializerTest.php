<?php declare(strict_types=1);
namespace Imbo\BehatApiExtension\Context\Initializer;

use Imbo\BehatApiExtension\Context\ApiClientAwareContext;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @coversDefaultClass Imbo\BehatApiExtension\Context\Initializer\ApiClientAwareInitializer
 */
class ApiClientAwareInitializerTest extends TestCase {
    /**
     * @covers ::initializeContext
     * @covers ::validateConnection
     */
    public function testThrowsExceptionWhenBaseUriIsNotConnectable() : void {
        $initializer = new ApiClientAwareInitializer(['base_uri' => 'http://localhost:123'], 'HS256', null);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Can\'t connect to base_uri: "http://localhost:123".');
        $initializer->initializeContext($this->createMock(ApiClientAwareContext::class));
    }

    /**
     * @covers ::initializeContext
     * @covers ::validateConnection
     * @covers ::__construct
     */
    public function testInjectsClientWhenInitializingContext() : void {
        // Set up a socket for the test case, try all ports between 8000 and 8079. If no ports are
        // available the test case will be marked as skipped. This is to get past the base URI
        // validation
        set_error_handler(function() { return true; });
        $sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

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
        socket_listen($sock);

        $context = $this->createMock(ApiClientAwareContext::class);
        $context
            ->expects($this->once())
            ->method('setClient')
            ->with($this->isInstanceOf(Client::class));

        $initializer = new ApiClientAwareInitializer([
            'base_uri' => sprintf('http://localhost:%d', $port),
        ], 'HS256', null);
        $initializer->initializeContext($context);

        // Close socket used in test case
        socket_close($sock);
    }
}
