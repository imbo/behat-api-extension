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
     */
    function setClient(ClientInterface $client);
}
