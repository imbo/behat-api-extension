<?php
namespace Imbo\BehatApiExtension\Context;

use Imbo\BehatApiExtension\ArrayContainsComparator;
use Behat\Behat\Context\Context;
use GuzzleHttp\ClientInterface;

/**
 * Array contains comparator aware interface
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 */
interface ArrayContainsComparatorAwareContext extends Context {
    /**
     * Set the instance of the array contains comparator
     *
     * @param ArrayContainsComparator $comparator
     * @return self
     */
    function setArrayContainsComparator(ArrayContainsComparator $comparator);
}
