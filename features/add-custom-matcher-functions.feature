Feature: Custom function addition
    In order to enable custom matcher functions
    As a developer
    I need to be able to add the matcher in the feature context

    Background:
        Given a custom FeatureContext file named "FeatureContext-custom-matchers.php"

    Scenario: Custom function passes
        Given a minimal Behat configuration file
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
        Given a minimal Behat configuration file
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
        Given a minimal Behat configuration file
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
        Given a minimal Behat configuration file
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
        Given a minimal Behat configuration file
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
        Given a minimal Behat configuration file
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

