<?php declare(strict_types=1);
namespace Imbo\BehatApiExtension\Context\Initializer;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;
use Imbo\BehatApiExtension\Context\ApiClientAwareContext;

/**
 * API client aware initializer
 *
 * Initializer for feature contexts that implements the ApiClientAwareContext interface.
 */
class ApiClientAwareInitializer implements ContextInitializer
{
    /**
     * @var array<mixed> Guzzle client configuration array
     * @see http://docs.guzzlephp.org/ Check out the Guzzle docs for a complete overview of available configuration parameters
     */
    private array $config = [];

    /**
     * @var string JWT algorithm
     */
    private string $jwtAlg;

    /**
     * @var string|null JWT key
     */
    private ?string $jwtKey;

    /**
     * Class constructor
     *
     * @param array<mixed> $config Guzzle client configuration array
     * @param string $jwtAlg JWT algorithm
     * @param ?string $jwtKey JWT key
     */
    public function __construct(array $config, string $jwtAlg = 'HS256', ?string $jwtKey = null)
    {
        $this->config = $config;
        $this->jwtAlg = $jwtAlg;
        $this->jwtKey = $jwtKey;
    }

    /**
     * Initialize the context
     *
     * Inject the Guzzle client if the context implements the ApiClientAwareContext interface
     */
    public function initializeContext(Context $context): void
    {
        if ($context instanceof ApiClientAwareContext) {
            $context->initializeClient($this->config);
            $context->setJwt($this->jwtAlg, $this->jwtKey);
        }
    }
}
