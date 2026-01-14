<?php declare(strict_types=1);

namespace Imbo\BehatApiExtension\Context\Initializer;

use Imbo\BehatApiExtension\ArrayContainsComparator;
use Imbo\BehatApiExtension\ArrayContainsComparator\Matcher;
use Imbo\BehatApiExtension\Context\ArrayContainsComparatorAwareContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(ArrayContainsComparatorAwareInitializer::class)]
class ArrayContainsComparatorAwareInitializerTest extends TestCase
{
    public function testInitializerInjectsDefaultMatcherFunctions(): void
    {
        /** @var ArrayContainsComparator&MockObject */
        $comparator = $this->createMock(ArrayContainsComparator::class);
        $comparator
            ->expects($this->exactly(8))
            ->method('addFunction')
            ->willReturnCallback(
                fn (string $matcher, object $impl) => match ([$matcher, $impl::class]) {
                    ['arrayLength', Matcher\ArrayLength::class] => $comparator,
                    ['arrayMinLength', Matcher\ArrayMinLength::class] => $comparator,
                    ['arrayMaxLength', Matcher\ArrayMaxLength::class] => $comparator,
                    ['variableType', Matcher\VariableType::class] => $comparator,
                    ['regExp', Matcher\RegExp::class] => $comparator,
                    ['gt', Matcher\GreaterThan::class] => $comparator,
                    ['lt', Matcher\LessThan::class] => $comparator,
                    ['jwt', Matcher\JWT::class] => $comparator,
                    default => $this->fail('Unexpected matcher: '.$matcher),
                },
            );

        new ArrayContainsComparatorAwareInitializer($comparator);
    }

    public function testInjectsComparatorWhenInitializingContext(): void
    {
        /** @var ArrayContainsComparator&MockObject */
        $comparator = $this->createMock(ArrayContainsComparator::class);
        $comparator
            ->expects($this->exactly(8))
            ->method('addFunction')
            ->willReturnSelf();

        /** @var ArrayContainsComparatorAwareContext&MockObject */
        $context = $this->createMock(ArrayContainsComparatorAwareContext::class);
        $context->expects($this->once())->method('setArrayContainsComparator')->with($comparator);

        (new ArrayContainsComparatorAwareInitializer($comparator))->initializeContext($context);
    }
}
