<?php declare(strict_types=1);

namespace Imbo\BehatApiExtension\Context\Initializer;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;
use Imbo\BehatApiExtension\Context\ApiClientAwareContext;

/**
 * API client aware initializer.
 *
 * Initializer for feature contexts that implements the ApiClientAwareContext interface.
 */
class ApiClientAwareInitializer implements ContextInitializer
{
    /**
     * @var array<mixed> Guzzle client configuration array
     *
     * @see http://docs.guzzlephp.org/ Check out the Guzzle docs for a complete overview of available configuration parameters
     */
    private array $config = [];

    /**
     * Class constructor.
     *
     * @param array<mixed> $config Guzzle client configuration array
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Initialize the context.
     *
     * Inject the Guzzle client if the context implements the ApiClientAwareContext interface
     */
    public function initializeContext(Context $context): void
    {
        if ($context instanceof ApiClientAwareContext) {
            $context->initializeClient($this->config);
        }
    }
}
