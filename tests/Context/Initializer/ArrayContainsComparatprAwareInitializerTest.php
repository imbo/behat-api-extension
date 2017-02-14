<?php
namespace Imbo\BehatApiExtension\Context\Initializer;

use Imbo\BehatApiExtension\ArrayContainsComparator;
use Imbo\BehatApiExtension\ArrayContainsComparator\Matcher;
use PHPUnit_Framework_TestCase;

/**
 * @coversDefaultClass Imbo\BehatApiExtension\Context\Initializer\ArrayContainsComparatorAwareInitializer
 * @testdox Initializer for array contains comparator aware contexts
 */
class ArrayContainsComparatorAwareInitializerTest extends PHPUnit_Framework_TestCase {
    /**
     * @covers ::__construct
     */
    public function testInitializerInjectsDefaultMatcherFunctions() {
        $comparator = $this->createMock('Imbo\BehatApiExtension\ArrayContainsComparator');
        $comparator
            ->expects($this->exactly(4))
            ->method('addFunction')
            ->withConsecutive(
                ['arrayLength', $this->isInstanceOf('Imbo\BehatApiExtension\ArrayContainsComparator\Matcher\ArrayLength')],
                ['arrayMinLength', $this->isInstanceOf('Imbo\BehatApiExtension\ArrayContainsComparator\Matcher\ArrayMinLength')],
                ['arrayMaxLength', $this->isInstanceOf('Imbo\BehatApiExtension\ArrayContainsComparator\Matcher\ArrayMaxLength')],
                ['variableType', $this->isInstanceOf('Imbo\BehatApiExtension\ArrayContainsComparator\Matcher\VariableType')]
            )
            ->will($this->returnSelf());

        $initializer = new ArrayContainsComparatorAwareInitializer($comparator);
    }

    /**
     * @covers ::initializeContext
     */
    public function testInjectsComparatorWhenInitializingContext() {
        $comparator = $this->createMock('Imbo\BehatApiExtension\ArrayContainsComparator');
        $comparator
            ->expects($this->exactly(4))
            ->method('addFunction')
            ->will($this->returnSelf());

        $context = $this->createMock('Imbo\BehatApiExtension\Context\ArrayContainsComparatorAwareContext');
        $context->expects($this->once())->method('setArrayContainsComparator')->with($comparator);

        $initializer = new ArrayContainsComparatorAwareInitializer($comparator);
        $initializer->initializeContext($context);
    }

}
