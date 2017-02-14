<?php
namespace Imbo\BehatApiExtension\Context\Initializer;

use Imbo\BehatApiExtension\ArrayContainsComparator\Matcher;
use Imbo\BehatApiExtension\ArrayContainsComparator as Comparator;
use Imbo\BehatApiExtension\Context\ArrayContainsComparatorAwareContext;
use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;

/**
 * Array contains comparator context aware initializer
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 */
class ArrayContainsComparatorAwareInitializer implements ContextInitializer {
    /**
     * @var Comparator
     */
    private $comparator;

    /**
     * Class constructor
     *
     * @param Comparator $comparator
     */
    public function __construct(Comparator $comparator) {
        $comparator
            ->addFunction('arrayLength', new Matcher\ArrayLength())
            ->addFunction('arrayMinLength', new Matcher\ArrayMinLength())
            ->addFunction('arrayMaxLength', new Matcher\ArrayMaxLength())
            ->addFunction('variableType', new Matcher\VariableType())
            ->addFunction('regExp', new Matcher\RegExp());

        $this->comparator = $comparator;
    }

    /**
     * Initialize the context
     *
     * @param Context $context
     */
    public function initializeContext(Context $context) {
        if ($context instanceof ArrayContainsComparatorAwareContext) {
            $context->setArrayContainsComparator($this->comparator);
        }
    }
}
