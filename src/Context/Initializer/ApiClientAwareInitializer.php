<?php
namespace Imbo\BehatApiExtension\Context\Initializer;

use Imbo\BehatApiExtension\Context\ApiClientAwareContext,
    Behat\Behat\Context\Context,
    Behat\Behat\Context\Initializer\ContextInitializer,
    GuzzleHttp\ClientInterface;

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
     */
    public function __construct(ClientInterface $client) {
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
