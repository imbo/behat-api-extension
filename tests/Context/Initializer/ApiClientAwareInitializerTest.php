<?php
namespace Imbo\BehatApiExtension\Context\Initializer;

use Behat\Behat\Context\Context;
use GuzzleHttp\Client;
use PHPUnit_Framework_TestCase;
use RuntimeException;

/**
 * @coversDefaultClass Imbo\BehatApiExtension\Context\Initializer\ApiClientAwareInitializer
 * @testdox Initializer for API Client aware contexts
 */
class ApiClientAwareInitializerTest extends PHPUnit_Framework_TestCase {
    /**
     * @covers ::initializeContext
     * @covers ::__construct
     */
    public function testInjectsClientWhenInitializingContext() {
        // Create a socket on localhost:9999 to not have the constructor throw an exception
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_bind($socket, 'localhost', 9999);
        socket_listen($socket);

        $client = new Client(['base_uri' => 'http://localhost:9999']);
        $context = $this->createMock('Imbo\BehatApiExtension\Context\ApiClientAwareContext');
        $context->expects($this->once())->method('setClient')->with($client);
        $initializer = new ApiClientAwareInitializer($client);
        $initializer->initializeContext($context);

        // Close the socket
        socket_close($socket);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Can not connect to localhost:9999
     * @covers ::__construct
     */
    public function testThrowsExceptionWhenClientCanNotConnectToBaseUri() {
        $initializer = new ApiClientAwareInitializer(
            new Client(['base_uri' => 'http://localhost:9999'])
        );
    }
}
