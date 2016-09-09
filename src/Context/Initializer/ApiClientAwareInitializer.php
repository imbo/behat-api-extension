<?php
namespace Imbo\BehatApiExtension\Context\Initializer;

use Imbo\BehatApiExtension\Context\ApiClientAwareContext;
use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;
use GuzzleHttp\ClientInterface;
use RuntimeException;

/**
 * API client aware initializer
 *
 * Initializer for feature contexts that implements the ApiClientAwareContext interface.
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 */
class ApiClientAwareInitializer implements ContextInitializer {
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * Class constructor
     *
     * @param ClientInterface $client
     * @throws RuntimeException
     */
    public function __construct(ClientInterface $client) {
        $baseUri = $client->getConfig()['base_uri'];
        $host = $baseUri->getHost();
        $port = $baseUri->getPort() ?: ($baseUri->getScheme() === 'http' ? 80 : 443);

        set_error_handler(function() { return true; });
        $resource = fsockopen($host, $port);
        restore_error_handler();

        if ($resource === false) {
            throw new RuntimeException(sprintf('Can not connect to %s:%d', $host, $port));
        }

        fclose($resource);

        $this->client = $client;
    }

    /**
     * Initialize the context
     *
     * Inject the Guzzle client if the context implements the ApiClientAwareContext interface
     *
     * @param Context $context
     */
    public function initializeContext(Context $context) {
        if ($context instanceof ApiClientAwareContext) {
            $context->setClient($this->client);
        }
    }
}
