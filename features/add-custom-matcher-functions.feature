Feature: Custom function addition
    In order to enable custom matcher functions
    As a developer
    I need to be able to add the matcher in the feature context

    Background:
        Given a file named "features/bootstrap/FeatureContext.php" with:
            """
            <?php
            use Imbo\BehatApiExtension\Context\ApiContext;
            use Imbo\BehatApiExtension\ArrayContainsComparator;
            use Assert\Assertion;

            class FeatureContext extends ApiContext {
                public function setArrayContainsComparator(ArrayContainsComparator $comparator) {
                    $comparator->addFunction('valueIs', function($actual, $expected) {
                        if ($actual !== $expected) {
                            throw new InvalidArgumentException(sprintf(
                                'Expected "%s", got "%s".',
                                $expected,
                                $actual
                            ));
                        }
                    });

                    return parent::setArrayContainsComparator($comparator);
                }

                /**
                 * @Then :actual is :expected
                 */
                public function assertValueIsBar($actual, $expected) {
                    $needle = ['value' => sprintf('@valueIs(%s)', $expected)];
                    $haystack = ['value' => $actual];

                    Assertion::true(
                        $this->arrayContainsComparator->compare($needle, $haystack)
                    );
                }
            }
            """

    Scenario: Custom function passes
        Given a file named "behat.yml" with:
            """
            default:
                formatters:
                    progress: ~
                extensions:
                    Imbo\BehatApiExtension: ~
            """
        And a file named "features/test-custom-function.feature" with:
            """
            Feature: Custom matcher function
                In order to use a custom matcher function
                As a feature runner
                I need to be able to expose the function

                Scenario: Call step that invokes custom matcher function
                    Then "foo" is "foo"
            """
        When I run "behat features/test-custom-function.feature"
        Then it should pass with:
            """
            .

            1 scenario (1 passed)
            1 step (1 passed)
            """

    Scenario: Custom function fails
        Given a file named "behat.yml" with:
            """
            default:
                formatters:
                    progress: ~
                extensions:
                    Imbo\BehatApiExtension: ~
            """
        And a file named "features/test-custom-function-failure.feature" with:
            """
            Feature: Custom matcher function
                In order to use a custom matcher function
                As a feature runner
                I need to be able to expose the function

                Scenario: Call step that invokes custom matcher function
                    Then "actual" is "expected"
            """
        When I run "behat features/test-custom-function-failure.feature"
        Then it should fail with:
            """
            Function "valueIs" failed with error message: "Expected "expected", got "actual".".
            """
