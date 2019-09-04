<?php
namespace Imbo\BehatApiExtension\ServiceContainer;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\BehatApiExtension\ServiceContainer\BehatApiExtension
 */
class BehatApiExtensionTest extends TestCase {
    private $extension;

    public function setUp() : void {
        $this->extension = new BehatApiExtension();
    }

    /**
     * @covers ::getConfigKey
     * @covers ::configure
     */
    public function testCanBuildConfiguration() : void {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root($this->extension->getConfigKey());

        // Configure the root node builder
        $this->extension->configure($rootNode);

        // Process the configuration
        $config = (new Processor())->process($rootNode->getNode(true), []);

        $this->assertSame([
            'apiClient' => [
                'base_uri' => 'http://localhost:8080',
            ],
        ], $config);
    }

    /**
     * @covers ::configure
     */
    public function testCanOverrideDefaultValuesWhenBuildingConfiguration() : void {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root($this->extension->getConfigKey());

        // Configure the root node builder
        $this->extension->configure($rootNode);

        $baseUri = 'http://localhost:8888';
        $config = (new Processor())->process($rootNode->getNode(true), [
            'api_extension' => [
                'apiClient' => [
                    'base_uri' => $baseUri,
                ],
            ],
        ]);

        $this->assertSame([
            'apiClient' => [
                'base_uri' => $baseUri,
            ],
        ], $config);
    }
}
