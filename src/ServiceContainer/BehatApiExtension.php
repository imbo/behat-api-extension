<?php declare(strict_types=1);
namespace Imbo\BehatApiExtension\ServiceContainer;

use Imbo\BehatApiExtension\Context\Initializer\ApiClientAwareInitializer;
use Imbo\BehatApiExtension\ArrayContainsComparator;
use Imbo\BehatApiExtension\Context\Initializer\ArrayContainsComparatorAwareInitializer;
use Behat\Behat\Context\ServiceContainer\ContextExtension;
use Behat\Testwork\ServiceContainer\Extension as ExtensionInterface;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Behat API extension
 *
 * This extension provides a series of steps that can be used to easily test API's. The ApiContext
 * class also exposes the client, request and response objects so custom steps using the underlying
 * client can be implemented.
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

    public function getConfigKey() : string {
        return self::CONFIG_KEY;
    }

    /**
     * @codeCoverageIgnore
     */
    public function initialize(ExtensionManager $extensionManager) : void {}

    public function configure(ArrayNodeDefinition $builder) : void {
        $builder
            ->children()
                ->arrayNode('apiClient')
                    ->addDefaultsIfNotSet()
                    ->ignoreExtraKeys(false)
                    ->children()
                        ->scalarNode('base_uri')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->defaultValue('http://localhost:8080');
    }

    /**
     * @codeCoverageIgnore
     * @param array $config Guzzle client configuration array
     * @see http://docs.guzzlephp.org/ Check out the Guzzle docs for a complete overview of available configuration parameters
     */
    public function load(ContainerBuilder $container, array $config) : void {
        $clientInitializerDefinition = new Definition(
            ApiClientAwareInitializer::class,
            [
                $config['apiClient']
            ]
        );
        $clientInitializerDefinition->addTag(ContextExtension::INITIALIZER_TAG);
        $comparatorDefinition = new Definition(ArrayContainsComparator::class);
        $comparatorInitializerDefinition = new Definition(
            ArrayContainsComparatorAwareInitializer::class,
            [
                new Reference(self::COMPARATOR_SERVICE_ID)
            ]
        );
        $comparatorInitializerDefinition->addTag(ContextExtension::INITIALIZER_TAG);

        $container->setDefinition(self::APICLIENT_INITIALIZER_SERVICE_ID, $clientInitializerDefinition);
        $container->setDefinition(self::COMPARATOR_SERVICE_ID, $comparatorDefinition);
        $container->setDefinition(self::COMPARATOR_INITIALIZER_SERVICE_ID, $comparatorInitializerDefinition);
    }

    /**
     * @codeCoverageIgnore
     */
    public function process(ContainerBuilder $container) : void {}
}
