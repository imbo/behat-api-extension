<?php declare(strict_types=1);

use Assert\Assertion;
use Behat\Step\Then;
use Imbo\BehatApiExtension\ArrayContainsComparator;
use Imbo\BehatApiExtension\Context\ApiContext;

class MyMatcher
{
    public function __invoke(mixed $value): void
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException('Want string yo');
        }
    }
}

class FeatureContext extends ApiContext
{
    public function setArrayContainsComparator(ArrayContainsComparator $comparator): static
    {
        $comparator->addFunction('myMatcher', new MyMatcher());
        $comparator->addFunction('valueIs', static function ($actual, $expected) {
            if ($actual !== $expected) {
                throw new InvalidArgumentException(sprintf('Expected "%s", got "%s".', $expected, $actual));
            }
        });

        return parent::setArrayContainsComparator($comparator);
    }

    #[Then(':actual is :expected')]
    public function assertValueIsBar(mixed $actual, mixed $expected): void
    {
        $needle = ['value' => sprintf('@valueIs(%s)', $expected)];
        $haystack = ['value' => $actual];

        Assertion::true(
            $this->arrayContainsComparator->compare($needle, $haystack),
        );
    }
}
