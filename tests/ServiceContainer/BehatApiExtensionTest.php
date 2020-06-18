<?php declare(strict_types=1);
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
        $rootNode = (new TreeBuilder($this->extension->getConfigKey()))->getRootNode();

        // Configure the root node builder
        $this->extension->configure($rootNode);

        // Process the configuration
        $config = (new Processor())->process($rootNode->getNode(true), []);

        $this->assertSame([
            'apiClient' => [
                'jwt_alg' => 'HS256',
                'jwt_key' => null,
                'base_uri' => 'http://localhost:8080',
            ],
        ], $config);
    }

    /**
     * @covers ::configure
     */
    public function testCanOverrideDefaultValuesWhenBuildingConfiguration() : void {
        $rootNode = (new TreeBuilder($this->extension->getConfigKey()))->getRootNode();

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
                'jwt_alg' => 'HS256',
                'jwt_key' => null,
            ],
        ], $config);
    }
}
