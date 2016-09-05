<?php
namespace Imbo\BehatApiExtension\ServiceContainer;

use Symfony\Component\Config\Definition\Builder\TreeBuilder,
    Symfony\Component\Config\Definition\Processor,
    PHPUnit_Framework_TestCase;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @covers Imbo\BehatApiExtension\ServiceContainer\BehatApiExtension
 */
class BehatApiExtensionTest extends PHPUnit_Framework_TestCase {
    private $extension;

    public function setUp() {
        $this->extension = new BehatApiExtension();
    }

    public function testBuildsConfiguration() {
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
