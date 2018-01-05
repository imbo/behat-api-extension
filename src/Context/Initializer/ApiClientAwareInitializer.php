<?php
namespace Imbo\BehatApiExtension\Context\Initializer;

use Imbo\BehatApiExtension\Context\ApiClientAwareContext;
use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;
use GuzzleHttp\Client;

/**
 * API client aware initializer
 *
 * Initializer for feature contexts that implements the ApiClientAwareContext interface.
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 */
class ApiClientAwareInitializer implements ContextInitializer {
    /**
     * @var array Guzzle client configuration array
     */
    private $guzzleConfig = [];

    /**
     * Class constructor
     *
     * @param array $guzzleConfig Guzzle client configuration array
     */
    public function __construct(array $guzzleConfig) {
        $this->guzzleConfig = $guzzleConfig;
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
            $context->setClient(new Client($this->guzzleConfig));
        }
    }
}
