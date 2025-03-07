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
     *
     * @param array<mixed> $config
     */
    public function initializeClient(array $config): self;

    /**
     * Set the JWT algorithm and key
     *
     * @param string $jwtAlg
     * @param string $jwtKey
     * @return self
     */
    public function setJwt(string $jwtAlg, string $jwtKey): self;
}
