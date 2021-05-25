<?php declare(strict_types=1);
namespace Imbo\BehatApiExtension\Context\Initializer;

use Imbo\BehatApiExtension\Context\ArrayContainsComparatorAwareContext;
use Imbo\BehatApiExtension\ArrayContainsComparator;
use Imbo\BehatApiExtension\ArrayContainsComparator\Matcher;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\BehatApiExtension\Context\Initializer\ArrayContainsComparatorAwareInitializer
 */
class ArrayContainsComparatorAwareInitializerTest extends TestCase {
    /**
     * @covers ::__construct
     */
    public function testInitializerInjectsDefaultMatcherFunctions() : void {
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
            ->willReturnSelf();

        new ArrayContainsComparatorAwareInitializer($comparator);
    }

    /**
     * @covers ::initializeContext
     */
    public function testInjectsComparatorWhenInitializingContext() : void {
        $comparator = $this->createMock(ArrayContainsComparator::class);
        $comparator
            ->expects($this->exactly(8))
            ->method('addFunction')
            ->willReturnSelf();

        $context = $this->createMock(ArrayContainsComparatorAwareContext::class);
        $context->expects($this->once())->method('setArrayContainsComparator')->with($comparator);

        (new ArrayContainsComparatorAwareInitializer($comparator))->initializeContext($context);
    }
}
