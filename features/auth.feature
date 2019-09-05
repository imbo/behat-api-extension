Feature: Test auth steps
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

    Scenario: Successfully authenticate
        Given a file named "features/auth-success.feature" with:
            """
            Feature: Set up the request
                Scenario: Specity auth
                    Given I am authenticating as "foo" with password "bar"
                    When I request "/basicAuth"
                    Then the response body contains JSON:
                    '''
                    {
                        "user": "foo"
                    }
                    '''


            """
        When I run "behat features/auth-success.feature"
        Then it should pass with:
            """
            ...

            1 scenario (1 passed)
            3 steps (3 passed)
            """

    Scenario: Unsuccessful authentication
        Given a file named "features/auth-no-success.feature" with:
            """
            Feature: Set up the request
                Scenario: Specity auth
                    Given I am authenticating as "foo" with password "foobar"
                    When I request "/basicAuth"
                    Then the response code is 401


            """
        When I run "behat features/auth-no-success.feature"
        Then it should pass with:
            """
            ...

            1 scenario (1 passed)
            3 steps (3 passed)
            """
