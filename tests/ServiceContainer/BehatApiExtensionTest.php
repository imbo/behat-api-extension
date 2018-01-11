<?php
namespace Imbo\BehatApiExtension\ServiceContainer;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;
use PHPUnit_Framework_TestCase;

/**
 * @coversDefaultClass Imbo\BehatApiExtension\ServiceContainer\BehatApiExtension
 * @testdox Extension
 */
class BehatApiExtensionTest extends PHPUnit_Framework_TestCase {
    /**
     * @var BehatApiExtension
     */
    private $extension;

    /**
     * Set up the SUT
     */
    public function setUp() {
        $this->extension = new BehatApiExtension();
    }

    /**
     * @covers ::getConfigKey
     * @covers ::configure
     */
    public function testCanBuildConfiguration() {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root($this->extension->getConfigKey());

        // Configure the root node builder
        $this->extension->configure($rootNode);

        // Process the configuration
        $config = (new Processor())->process($rootNode->getNode(true), []);

        $this->assertSame([
            'apiClient' => [
                'base_uri' => 'http://localhost:8080'
            ],
        ], $config);
    }

    /**
     * @covers ::configure
     * @expectedException Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Invalid configuration for path "api_extension.apiClient.base_uri": Can't connect to base_uri: "http://localhost:123".
     */
    public function testThrowsExceptionWhenBuildingConfigurationAndBaseUriIsNotConnectable() {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root($this->extension->getConfigKey());

        $this->extension->configure($rootNode);

        (new Processor())->process($rootNode->getNode(true), [
            'api_extension' => [
                'apiClient' => [
                    'base_uri' => 'http://localhost:123',
                ],
            ],
        ]);
    }

    /**
     * @covers ::configure
     */
    public function testCanOverrideDefaultValuesWhenBuildingConfiguration() {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root($this->extension->getConfigKey());

        // Configure the root node builder
        $this->extension->configure($rootNode);

        // Set up a socket for the test case, try all ports between 8000 and 8079. If no ports are
        // available the test case will be marked as skipped
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

        $baseUri = sprintf('http://localhost:%d', $port);
        $config = (new Processor())->process($rootNode->getNode(true), [
            'api_extension' => [
                'apiClient' => [
                    'base_uri' => $baseUri
                ],
            ],
        ]);

        $this->assertSame([
            'apiClient' => [
                'base_uri' => $baseUri
            ],
        ], $config);

        // Close socket used in test case
        socket_close($sock);
    }
}
