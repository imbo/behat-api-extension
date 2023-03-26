<?php declare(strict_types=1);
namespace Imbo\BehatApiExtension\Context;

use Behat\Behat\Context\Context;

/**
 * Api client aware interface
 */
interface ApiClientAwareContext extends Context
{
    /**
     * Initialize the Guzzle client
     */
    public function initializeClient(array $config): self;
}
