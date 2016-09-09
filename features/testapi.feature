Feature: Test API
    In order to test the extension
    As a developer
    I want to be able to test all available features

    Background:
        Given a file named "behat.yml" with:
            """
            default:
                formatters:
                    progress: ~
                extensions:
                    Imbo\BehatApiExtension:
                        base_uri: http://localhost:8080

                suites:
                    default:
                        contexts: ['Imbo\BehatApiExtension\Context\ApiContext']
            """

    @http-methods
    Scenario: Sending requests using HTTP GET
        Given a file named "features/request-using-http-get.feature" with:
            """
            Feature: Send a request using HTTP GET
                Scenario: Send a request using HTTP GET
                    When I request echoHttpMethod
                    Then the response body is:
                    '''
                    {"method":"GET"}
                    '''
            """
        When I run "behat features/request-using-http-get.feature"
        Then it should pass with:
            """
            ..

            1 scenario (1 passed)
            2 steps (2 passed)
            """

    @http-methods
    Scenario Outline: Sending requests using different HTTP methods
        Given a file named "features/request-using-different-http-methods.feature" with:
            """
            Feature: Send a request using different HTTP methods
                Scenario: Send a request using different HTTP methods
                    When I request echoHttpMethod using HTTP <method>
                    Then the response body is:
                    '''
                    <response>
                    '''
            """
        When I run "behat features/request-using-different-http-methods.feature"
        Then it should pass with:
            """
            ..

            1 scenario (1 passed)
            2 steps (2 passed)
            """

        Examples:
            | method  | response             |
            | GET     | {"method":"GET"}     |
            | POST    | {"method":"POST"}    |
            | PUT     | {"method":"PUT"}     |
            | DELETE  | {"method":"DELETE"}  |
            | OPTIONS | {"method":"OPTIONS"} |

    @auth
    Scenario Outline: Sending requests using basic HTTP auth
        Given a file named "features/request-using-basic-auth.feature" with:
            """
            Feature: Test basic auth
                Scenario: Authenticated endpoint
                    Given I am authenticating as <username> with password <password>
                    When I request basicAuth
                    Then the response body is:
                    '''
                    <response>
                    '''
                    And the response code is <responseCode>
                    And the response code is not <notResponseCode>
            """
        When I run "behat features/request-using-basic-auth.feature"
        Then it should pass with:
            """
            .....

            1 scenario (1 passed)
            5 steps (5 passed)
            """

        Examples:
            | username | password | responseCode | notResponseCode | response       |
            | foo      | foo      | 401          | 200             |                |
            | foo      | bar      | 200          | 401             | {"user":"foo"} |
            | bar      | foo      | 200          | 401             | {"user":"bar"} |
