<?php declare(strict_types=1);
namespace Imbo\BehatApiExtension\Context\Initializer;

use Imbo\BehatApiExtension\Context\ApiClientAwareContext;
use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;
use GuzzleHttp\Client;

/**
 * API client aware initializer
 *
 * Initializer for feature contexts that implements the ApiClientAwareContext interface.
 */
class ApiClientAwareInitializer implements ContextInitializer {
    /**
     * @var array{base_uri?: string} Guzzle client configuration array
     * @see http://docs.guzzlephp.org/ Check out the Guzzle docs for a complete overview of available configuration parameters
     */
    private array $guzzleConfig = [];

    /**
     * Class constructor
     *
     * @param array{base_uri?: string} $guzzleConfig Guzzle client configuration array
     */
    public function __construct(array $guzzleConfig) {
        $this->guzzleConfig = $guzzleConfig;
    }

    /**
     * Initialize the context
     *
     * Inject the Guzzle client if the context implements the ApiClientAwareContext interface
     */
    public function initializeContext(Context $context) : void {
        if ($context instanceof ApiClientAwareContext) {
            $context->setClient(
                new Client($this->guzzleConfig),
                $this->guzzleConfig['base_uri'] ?? '',
            );
        }
    }
}
