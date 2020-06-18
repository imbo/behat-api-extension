<?php declare(strict_types=1);
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
 */
class ApiClientAwareInitializer implements ContextInitializer {
    /**
     * @var array Guzzle client configuration array
     */
    private $guzzleConfig = [];

    /**
     * @var string JWT algorithm
     */
    private $jwtAlg;

    /**
     * @var string JWT key
     */
    private $jwtKey;

    public function __construct(array $guzzleConfig, $jwtAlg, $jwtKey) {
        $this->guzzleConfig = $guzzleConfig;
        $this->jwtAlg = $jwtAlg;
        $this->jwtKey = $jwtKey;
    }

    /**
     * Initialize the context
     *
     * Inject the Guzzle client if the context implements the ApiClientAwareContext interface
     */
    public function initializeContext(Context $context) : void {
        if ($context instanceof ApiClientAwareContext) {
            // Fetch base URI from the Guzzle client configuration, if it exists
            $baseUri = !empty($this->guzzleConfig['base_uri']) ? $this->guzzleConfig['base_uri'] : null;

            if ($baseUri && !$this->validateConnection($baseUri)) {
                throw new RuntimeException(sprintf('Can\'t connect to base_uri: "%s".', $baseUri));
            }

            $context->setClient(new Client($this->guzzleConfig));
            $context->setJwt($this->jwtAlg, $this->jwtKey);
        }
    }

    private function validateConnection(string $baseUri) : bool {
        /** @var string[] */
        $parts = parse_url($baseUri);
        $host = $parts['host'];
        $port = isset($parts['port']) ? (int) $parts['port'] : ($parts['scheme'] === 'https' ? 443 : 80);

        set_error_handler(function () : bool {
            return true;
        });

        $resource = fsockopen($host, $port);
        restore_error_handler();

        if ($resource === false) {
            return false;
        }

        fclose($resource);

        return true;
    }
}
