Feature: Test built in matcher functions failures
    In order to test the extension
    As a developer
    I want to be able to test all available steps

    Background:
        Given a file named "behat.yml" with:
            """
            default:
                formatters:
                    progress: ~
                extensions:
                    Imbo\BehatApiExtension:
                        apiClient:
                            base_uri: http://localhost:8080

                suites:
                    default:
                        contexts: ['Imbo\BehatApiExtension\Context\ApiContext']
            """

    Scenario: Assert that @arrayLength can fail
        Given a file named "features/array-length-failure.feature" with:
            """
            Feature: Verify failure
                Scenario: Use custom matcher function
                    Given the request body is:
                        '''
                        {
                            "list": [1, 2, 3]
                        }
                        '''
                    When I request "/echo?json" using HTTP POST
                    Then the response body contains JSON:
                        '''
                        {
                            "list": "@arrayLength(2)"
                        }
                        '''
            """
        When I run "behat features/array-length-failure.feature"
        Then it should fail with:
            """
            Function "arrayLength" failed with error message: "Expected array to have exactly 2 entries, actual length: 3.".
            """

    Scenario: Assert that @arrayMaxLength can fail
        Given a file named "features/array-max-length-failure.feature" with:
            """
            Feature: Verify failure
                Scenario: Use custom matcher function
                    Given the request body is:
                        '''
                        {
                            "list": [1, 2, 3]
                        }
                        '''
                    When I request "/echo?json" using HTTP POST
                    Then the response body contains JSON:
                        '''
                        {
                            "list": "@arrayMaxLength(2)"
                        }
                        '''
            """
        When I run "behat features/array-max-length-failure.feature"
        Then it should fail with:
            """
            Function "arrayMaxLength" failed with error message: "Expected array to have less than or equal to 2 entries, actual length: 3.".
            """

    Scenario: Assert that @arrayMinLength can fail
        Given a file named "features/array-min-length-failure.feature" with:
            """
            Feature: Verify failure
                Scenario: Use custom matcher function
                    Given the request body is:
                        '''
                        {
                            "list": [1, 2, 3]
                        }
                        '''
                    When I request "/echo?json" using HTTP POST
                    Then the response body contains JSON:
                        '''
                        {
                            "list": "@arrayMinLength(4)"
                        }
                        '''
            """
        When I run "behat features/array-min-length-failure.feature"
        Then it should fail with:
            """
            Function "arrayMinLength" failed with error message: "Expected array to have more than or equal to 4 entries, actual length: 3.".
            """

    Scenario: Assert that @variableType can fail
        Given a file named "features/variable-type-failure.feature" with:
            """
            Feature: Verify failure
                Scenario: Use custom matcher function
                    Given the request body is:
                        '''
                        {
                            "list": [1, 2, 3]
                        }
                        '''
                    When I request "/echo?json" using HTTP POST
                    Then the response body contains JSON:
                        '''
                        {
                            "list": "@variableType(string)"
                        }
                        '''
            """
        When I run "behat features/variable-type-failure.feature"
        Then it should fail with:
            """
            Function "variableType" failed with error message: "Expected variable type "string", got "array".".
            """

    Scenario: Assert that @regExp can fail
        Given a file named "features/reg-exp-failure.feature" with:
            """
            Feature: Verify failure
                Scenario: Use custom matcher function
                    Given the request body is:
                        '''
                        {
                            "key": "value"
                        }
                        '''
                    When I request "/echo?json" using HTTP POST
                    Then the response body contains JSON:
                        '''
                        {
                            "key": "@regExp(/foo/)"
                        }
                        '''
            """
        When I run "behat features/reg-exp-failure.feature"
        Then it should fail with:
            """
            Function "regExp" failed with error message: "Subject "value" did not match pattern "/foo/".".
            """
