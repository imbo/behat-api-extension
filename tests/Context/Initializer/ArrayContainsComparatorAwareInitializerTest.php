<?php
namespace Imbo\BehatApiExtension\Context\Initializer;

use Imbo\BehatApiExtension\Context\ArrayContainsComparatorAwareContext;
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
        $comparator = $this->createMock(ArrayContainsComparator::class);
        $comparator
            ->expects($this->exactly(8))
            ->method('addFunction')
            ->withConsecutive(
                ['arrayLength', $this->isInstanceOf(Matcher\ArrayLength::class)],
                ['arrayMinLength', $this->isInstanceOf(Matcher\ArrayMinLength::class)],
                ['arrayMaxLength', $this->isInstanceOf(Matcher\ArrayMaxLength::class)],
                ['variableType', $this->isInstanceOf(Matcher\VariableType::class)],
                ['regExp', $this->isInstanceOf(Matcher\RegExp::class)],
                ['gt', $this->isInstanceOf(Matcher\GreaterThan::class)],
                ['lt', $this->isInstanceOf(Matcher\LessThan::class)],
                ['jwt', $this->isInstanceOf(Matcher\JWT::class)]
            )
            ->will($this->returnSelf());

        $initializer = new ArrayContainsComparatorAwareInitializer($comparator);
    }

    /**
     * @covers ::initializeContext
     */
    public function testInjectsComparatorWhenInitializingContext() {
        $comparator = $this->createMock(ArrayContainsComparator::class);
        $comparator
            ->expects($this->exactly(8))
            ->method('addFunction')
            ->will($this->returnSelf());

        $context = $this->createMock(ArrayContainsComparatorAwareContext::class);
        $context->expects($this->once())->method('setArrayContainsComparator')->with($comparator);

        $initializer = new ArrayContainsComparatorAwareInitializer($comparator);
        $initializer->initializeContext($context);
    }
}
