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

            class MyMatcher {
                public function __invoke($value) {
                    if (!is_string($value)) {
                        throw new InvalidArgumentException('Want string yo');
                    }
                }
            }

            class FeatureContext extends ApiContext {
                public function setArrayContainsComparator(ArrayContainsComparator $comparator) {
                    $comparator->addFunction('myMatcher', new MyMatcher());
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

    Scenario: Custom myMatcher class passes
        Given a file named "behat.yml" with:
            """
            default:
                formatters:
                    progress: ~
                extensions:
                    Imbo\BehatApiExtension: ~
            """
        And a file named "features/test-custom-matcher-class.feature" with:
            """
            Feature: Custom matcher function
                In order to use a custom matcher function
                As a feature runner
                I need to be able to expose the function

                Scenario: Call step that invokes custom matcher function
                    When I request "/"
                    Then the response body contains JSON:
                        '''
                        {
                            "string": "@myMatcher()"
                        }
                        '''
            """
        When I run "behat features/test-custom-matcher-class.feature"
        Then it should pass with:
            """
            ..

            1 scenario (1 passed)
            2 steps (2 passed)
            """

    Scenario: Custom myMatcher class passes when used in list
        Given a file named "behat.yml" with:
            """
            default:
                formatters:
                    progress: ~
                extensions:
                    Imbo\BehatApiExtension: ~
            """
        And a file named "features/test-custom-matcher-class-in-list.feature" with:
            """
            Feature: Custom matcher function
                In order to use a custom matcher function
                As a feature runner
                I need to be able to expose the function

                Scenario: Call step that invokes custom matcher function
                    When I request "/list"
                    Then the response body contains JSON:
                        '''
                        {
                            "[0]": {
                                "string": "@myMatcher()"
                            }
                        }
                        '''
            """
        When I run "behat features/test-custom-matcher-class-in-list.feature"
        Then it should pass with:
            """
            ..

            1 scenario (1 passed)
            2 steps (2 passed)
            """

    Scenario: Custom myMatcher class fails
        Given a file named "behat.yml" with:
            """
            default:
                formatters:
                    progress: ~
                extensions:
                    Imbo\BehatApiExtension: ~
            """
        And a file named "features/test-custom-matcher-class-fails.feature" with:
            """
            Feature: Custom matcher function
                In order to use a custom matcher function
                As a feature runner
                I need to be able to expose the function

                Scenario: Call step that invokes custom matcher function
                    When I request "/"
                    Then the response body contains JSON:
                        '''
                        {
                            "integer": "@myMatcher()"
                        }
                        '''
            """
        When I run "behat features/test-custom-matcher-class-fails.feature"
        Then it should fail with:
            """
            Want string yo
            """

    Scenario: Custom myMatcher class fails when used with list
        Given a file named "behat.yml" with:
            """
            default:
                formatters:
                    progress: ~
                extensions:
                    Imbo\BehatApiExtension: ~
            """
        And a file named "features/test-custom-matcher-class-fails-in-list.feature" with:
            """
            Feature: Custom matcher function
                In order to use a custom matcher function
                As a feature runner
                I need to be able to expose the function

                Scenario: Call step that invokes custom matcher function
                    When I request "/list"
                    Then the response body contains JSON:
                        '''
                        {
                            "[0]": {
                                "integer": "@myMatcher()"
                            }
                        }
                        '''
            """
        When I run "behat features/test-custom-matcher-class-fails-in-list.feature"
        Then it should fail with:
            """
            Want string yo
            """

