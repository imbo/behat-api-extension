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
                    Given I get an OAuth token using password grant from "/oauth/token" with "foo" and "bar" in scope "baz" using client ID "id" and client secret "secret"
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
                    Given I get an OAuth token using password grant from "/oauth/token" with "invalid" and "invalid" in scope "baz" using client ID "id"
                    When I request "/securedWithOAuth"
            """
        When I run "behat features/oauth-no-success.feature"
        Then it should fail with:
            """
            Expected request for access token to pass, got status code 401 with the following response: {"error":"invalid_request"} (RuntimeException)

            1 scenario (1 failed)
            2 steps (1 failed, 1 skipped)
            """

    Scenario: Invalid OAuth token response
        Given a file named "features/oauth-missing-token.feature" with:
            """
            Feature: Set up the request
                Scenario: Specify auth
                    Given I get an OAuth token using password grant from "/echoHttpMethod" with "foo" and "bar" in scope "baz" using client ID "id"
                    When I request "/securedWithOAuth"
            """
        When I run "behat features/oauth-missing-token.feature"
        Then it should fail with:
            """
            Missing access_token from response body: {"method":"POST"} (RuntimeException)

            1 scenario (1 failed)
            2 steps (1 failed, 1 skipped)
            """