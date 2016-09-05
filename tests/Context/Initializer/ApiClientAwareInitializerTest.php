<?php
namespace Imbo\BehatApiExtension\Context\Initializer;

use Behat\Behat\Context\Context,
    GuzzleHttp\Client,
    PHPUnit_Framework_TestCase,
    RuntimeException;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @covers Imbo\BehatApiExtension\Context\Initializer\ApiClientAwareInitializer
 */
class ApiClientAwareInitializerText extends PHPUnit_Framework_TestCase {
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

    public function testInjectContainerThatCanNotConnectToBaseUri() {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Can not connect to localhost:9999');

        $client = new Client(['base_uri' => 'http://localhost:9999']);
        $initializer = new ApiClientAwareInitializer($client);
    }
}
