<?php
namespace Imbo\BehatApiExtension\ServiceContainer;

use Behat\Behat\Context\ServiceContainer\ContextExtension;
use Behat\Testwork\ServiceContainer\Extension as ExtensionInterface;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use GuzzleHttp\ClientInterface;

/**
 * Behat API extension
 *
 * This extension provides a series of steps that can be used to easily test API's. The ApiContext
 * class also exposes the client, request and response objects so custom steps using the underlying
 * client can be implemented.
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 */
class BehatApiExtension implements ExtensionInterface {
    /**
     * Service ID for the Guzzle client
     *
     * @var string
     */
    const CLIENT_SERVICE_ID = 'api_extension.client';

    /**
     * Service ID for the initializer
     *
     * @var string
     */
    const INITIALIZER_SERVICE_ID = 'api_extension.context_initializer';

    /**
     * Config key for the extension
     *
     * @var string
     */
    const CONFIG_KEY = 'api_extension';

    /**
     * {@inheritdoc}
     */
    public function getConfigKey() {
        return self::CONFIG_KEY;
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function initialize(ExtensionManager $extensionManager) {
        // Not used
    }

    /**
     * {@inheritdoc}
     */
    public function configure(ArrayNodeDefinition $builder) {
        $builder
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('base_uri')
                    ->info('The base URI of the application you want to test')
                    ->example('http://localhost:8080')
                    ->defaultValue('http://localhost:8080')
                ->end()
            ->end();
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function load(ContainerBuilder $container, array $config) {
        $definition = new Definition('GuzzleHttp\Client', [$config]);
        $container->setDefinition(self::CLIENT_SERVICE_ID, $definition);

        $definition = new Definition('Imbo\BehatApiExtension\Context\Initializer\ApiClientAwareInitializer', [
            new Reference(self::CLIENT_SERVICE_ID),
            $config
        ]);
        $definition->addTag(ContextExtension::INITIALIZER_TAG);
        $container->setDefinition(self::INITIALIZER_SERVICE_ID, $definition);
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function process(ContainerBuilder $container) {

    }
}
