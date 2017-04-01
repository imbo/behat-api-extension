<?php
namespace Imbo\BehatApiExtension\Context;

use Behat\Behat\Context\Context;
use GuzzleHttp\ClientInterface;

/**
 * Api client aware interface
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 */
interface ApiClientAwareContext extends Context {
    /**
     * Set the Guzzle client and create a pristine request instance
     *
     * @param ClientInterface $client
     * @return self
     */
    function setClient(ClientInterface $client);
}
