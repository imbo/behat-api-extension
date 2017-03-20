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
use InvalidArgumentException;

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
     * Service ID for the comparator
     *
     * @var string
     */
    const COMPARATOR_SERVICE_ID = 'api_extension.comparator';

    /**
     * Service ID for the client initializer
     *
     * @var string
     */
    const APICLIENT_INITIALIZER_SERVICE_ID = 'api_extension.api_client.context_initializer';

    /**
     * Service ID for the initializer
     *
     * @var string
     */
    const COMPARATOR_INITIALIZER_SERVICE_ID = 'api_extension.comparator.context_initializer';

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
            ->children()
                ->arrayNode('apiClient')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('base_uri')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->defaultValue('http://localhost:8080')
                            ->validate()
                            ->ifTrue(function($uri) {
                                $parts = parse_url($uri);
                                $host = $parts['host'];
                                $port = isset($parts['port']) ? $parts['port'] : ($parts['scheme'] === 'https' ? 443 : 80);

                                set_error_handler(function() { return true; });
                                $resource = fsockopen($host, $port);
                                restore_error_handler();

                                if ($resource === false) {
                                    // Can't connect, return true to mark as failure
                                    return true;
                                }

                                // Connection successful, close connection and return false to mark
                                // as success
                                fclose($resource);

                                return false;
                            })
                                ->then(function($uri) {
                                    throw new InvalidArgumentException(sprintf('Can\'t connect to base_uri: "%s".', $uri));
                                })
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function load(ContainerBuilder $container, array $config) {
        // Client initializer definition
        $clientInitializerDefinition = new Definition(
            'Imbo\BehatApiExtension\Context\Initializer\ApiClientAwareInitializer',
            [
                $config['apiClient']['base_uri']
            ]
        );
        $clientInitializerDefinition->addTag(ContextExtension::INITIALIZER_TAG);

        // Definition for the array contains comparator
        $comparatorDefinition = new Definition(
            'Imbo\BehatApiExtension\ArrayContainsComparator'
        );

        // Comparator initializer definition
        $comparatorInitializerDefinition = new Definition(
            'Imbo\BehatApiExtension\Context\Initializer\ArrayContainsComparatorAwareInitializer',
            [
                new Reference(self::COMPARATOR_SERVICE_ID)
            ]
        );
        $comparatorInitializerDefinition->addTag(ContextExtension::INITIALIZER_TAG);

        // Add all definitions to the container
        $container->setDefinition(self::APICLIENT_INITIALIZER_SERVICE_ID, $clientInitializerDefinition);
        $container->setDefinition(self::COMPARATOR_SERVICE_ID, $comparatorDefinition);
        $container->setDefinition(self::COMPARATOR_INITIALIZER_SERVICE_ID, $comparatorInitializerDefinition);
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function process(ContainerBuilder $container) {

    }
}
