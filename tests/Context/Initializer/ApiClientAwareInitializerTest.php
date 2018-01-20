<?php
namespace Imbo\BehatApiExtension\Context\Initializer;

use Imbo\BehatApiExtension\Context\ApiClientAwareContext;
use GuzzleHttp\Client;
use PHPUnit_Framework_TestCase;

/**
 * @coversDefaultClass Imbo\BehatApiExtension\Context\Initializer\ApiClientAwareInitializer
 * @testdox Initializer for API Client aware contexts
 */
class ApiClientAwareInitializerTest extends PHPUnit_Framework_TestCase {
    /**
     * @covers ::initializeContext
     * @expectedException RuntimeException
     * @expectedExceptionMessage Can't connect to base_uri: "http://localhost:123".
     */
    public function testThrowsExceptionWhenBaseUriIsNotConnectable() {
        $initializer = new ApiClientAwareInitializer(['base_uri' => 'http://localhost:123']);
        $initializer->initializeContext($this->createMock(ApiClientAwareContext::class));
    }

    /**
     * @covers ::initializeContext
     * @covers ::__construct
     */
    public function testInjectsClientWhenInitializingContext() {
        $context = $this->createMock(ApiClientAwareContext::class);
        $context
            ->expects($this->once())
            ->method('setClient')
            ->with($this->isInstanceOf(Client::class));

        $initializer = new ApiClientAwareInitializer([]); // Don't pass base_uri to skip validation
        $initializer->initializeContext($context);
    }
}
