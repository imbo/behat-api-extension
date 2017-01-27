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
        $tree = new TreeBuilder();
        $root = $tree->root('root');

        $node = $root->children()->arrayNode($this->extension->getConfigKey());
        $this->extension->configure($node);

        $actualConfig = (new Processor())->process($root->getNode(true), []);
        $expectedConfig = [
            'api_extension' => [
                'base_uri' => 'http://localhost:8080',
            ],
        ];

        $this->assertSame($expectedConfig, $actualConfig);
    }
}
