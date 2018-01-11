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
        $baseUri = 'http://localhost:8080';
        $context = $this->createMock('Imbo\BehatApiExtension\Context\ApiClientAwareContext');
        $context
            ->expects($this->once())
            ->method('setClient')
            ->with($this->callback(function($client) use ($baseUri) {
                return (string) $client->getConfig('base_uri') === $baseUri;
            }));

        $initializer = new ApiClientAwareInitializer(['base_uri' => $baseUri]);
        $initializer->initializeContext($context);
    }
}
