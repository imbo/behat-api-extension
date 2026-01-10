<?php declare(strict_types=1);

namespace Imbo\BehatApiExtension\Context;

use Behat\Behat\Context\Context;
use Imbo\BehatApiExtension\ArrayContainsComparator;

/**
 * Array contains comparator aware interface.
 */
interface ArrayContainsComparatorAwareContext extends Context
{
    /**
     * Set the instance of the array contains comparator.
     */
    public function setArrayContainsComparator(ArrayContainsComparator $comparator): self;
}
