<?php
namespace Imbo\BehatApiExtension\Context\Initializer;

use Imbo\BehatApiExtension\Context\ApiClientAwareContext;
use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;
use GuzzleHttp\Client;
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
            // Fetch base URI from the Guzzle client configuration, if it exists
            $baseUri = !empty($this->guzzleConfig['base_uri']) ? $this->guzzleConfig['base_uri'] : null;

            if ($baseUri && !$this->validateConnection($baseUri)) {
                throw new RuntimeException(sprintf('Can\'t connect to base_uri: "%s".', $baseUri));
            }

            $context->setClient(new Client($this->guzzleConfig));
        }
    }

    /**
     * Validate a connection to the base URI
     *
     * @param string $baseUri
     * @return boolean
     */
    private function validateConnection($baseUri) {
        $parts = parse_url($baseUri);
        $host = $parts['host'];
        $port = isset($parts['port']) ? $parts['port'] : ($parts['scheme'] === 'https' ? 443 : 80);

        set_error_handler(function () {
            return true;
        });

        $resource = fsockopen($host, $port);
        restore_error_handler();

        if ($resource === false) {
            // Can't connect
            return false;
        }

        // Connection successful, close connection
        fclose($resource);

        return true;
    }
}
