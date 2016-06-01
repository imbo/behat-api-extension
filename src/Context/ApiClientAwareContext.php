<?php
namespace Imbo\BehatApiExtension\Context;

use Behat\Behat\Context\Context,
    GuzzleHttp\ClientInterface;

/**
 * Api client aware interface
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 */
interface ApiClientAwareContext extends Context {
    /**
     * Set the Guzzle client
     *
     * @param ClientInterface $client
     */
    function setClient(ClientInterface $client);
}
