<?php declare(strict_types=1);
namespace Imbo\BehatApiExtension\Context;

use Behat\Behat\Context\Context;
use GuzzleHttp\ClientInterface;

/**
 * Api client aware interface
 */
interface ApiClientAwareContext extends Context {
    /**
     * Set the Guzzle client and create a pristine request instance
     *
     * @return self
     */
    function setClient(ClientInterface $client);

    /**
     * Set the JWT algorithm and key
     *
     * @param string $jwtAlg
     * @param string $jwtKey
     * @return self
     */
    function setJwt($jwtAlg, $jwtKey);
}
