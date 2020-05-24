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
                            oauth:
                              url: /oauth/token
                              client_id: ''
                              client_secret: ''

                suites:
                    default:
                        contexts: ['Imbo\BehatApiExtension\Context\ApiContext']
            """

    Scenario: Successfully authenticate
        Given a file named "features/auth-success.feature" with:
            """
            Feature: Set up the request
                Scenario: Specify auth
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
                Scenario: Specify auth
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

    Scenario: Successfully OAuth
        Given a file named "features/oauth-success.feature" with:
            """
            Feature: Set up the request
                Scenario: Specify auth
                    Given I use OAuth with "foo" and "bar" in scope "baz"
                    When I request "/securedWithOAuth"
                    Then the response code is 200
                    And the response body contains JSON:
                    '''
                    {
                        "users": {
                            "foo": "bar"
                        }
                    }
                    '''
            """
        When I run "behat features/oauth-success.feature"
        Then it should pass with:
            """
            ...

            1 scenario (1 passed)
            4 steps (4 passed)
            """

    Scenario: Unsuccessfully OAuth
        Given a file named "features/oauth-no-success.feature" with:
            """
            Feature: Set up the request
                Scenario: Specify auth
                    Given I use OAuth with "invalid" and "invalid" in scope "bar"
                    When I request "/securedWithOAuth"
                    Then the response code is 401
            """
        When I run "behat features/oauth-no-success.feature"
        Then it should pass with:
            """
            ...

            1 scenario (1 passed)
            3 steps (3 passed)
            """