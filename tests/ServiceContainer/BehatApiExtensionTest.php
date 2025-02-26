<?php declare(strict_types=1);
namespace Imbo\BehatApiExtension\ServiceContainer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;

#[CoversClass(BehatApiExtension::class)]
class BehatApiExtensionTest extends TestCase
{
    private BehatApiExtension $extension;

    public function setUp(): void
    {
        $this->extension = new BehatApiExtension();
    }

    public function testCanBuildConfiguration(): void
    {
        /** @var ArrayNodeDefinition */
        $rootNode = (new TreeBuilder($this->extension->getConfigKey()))->getRootNode();

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

    public function testCanOverrideDefaultValuesWhenBuildingConfiguration(): void
    {
        /** @var ArrayNodeDefinition */
        $rootNode = (new TreeBuilder($this->extension->getConfigKey()))->getRootNode();

        // Configure the root node builder
        $this->extension->configure($rootNode);

        $baseUri = 'http://localhost:8888';
        $config = (new Processor())->process($rootNode->getNode(true), [
            'api_extension' => [
                'apiClient' => [
                    'jwt_alg' => 'HS256',
                    'jwt_key' => null,
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
