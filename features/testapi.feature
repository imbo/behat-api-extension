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

    Scenario Outline: Sending requests using different methods
        Given a file named "features/request.feature" with:
            """
            Feature: Test different methods
                In order to validate the send request step
                As a context developer
                I need to be able to use any HTTP1/1 method in a scenario

                Scenario: Endpoint outputs HTTP method
                    When I request "getMethod" using HTTP <method>
                    Then the response body should be "<response>"
                    And the response code should be 200
            """
        When I run "behat features/request.feature"
        Then it should pass with:
            """
            ...

            1 scenario (1 passed)
            3 steps (3 passed)
            """

        Examples:
            | method  | response             |
            | GET     | {"method":"GET"}     |
            | POST    | {"method":"POST"}    |
            | PUT     | {"method":"PUT"}     |
            | DELETE  | {"method":"DELETE"}  |
            | OPTIONS | {"method":"OPTIONS"} |

    Scenario Outline: Sending requests using HTTP auth
        Given a file named "features/auth.feature" with:
            """
            Feature: Test basic auth
                In order to test basic auth
                As a context developer
                I need to have an authentication step

                Scenario: Authenticated endpoint
                    Given I am authenticating as "<username>" with password "<password>"
                    When I request "auth"
                    Then the response body should be "<response>"
                    And the response code should be <responseCode>
            """
        When I run "behat features/auth.feature"
        Then it should pass with:
            """
            ....

            1 scenario (1 passed)
            4 steps (4 passed)
            """

        Examples:
            | username | password | responseCode | response       |
            | foo      | foo      | 401          |                |
            | foo      | bar      | 200          | {"user":"foo"} |
            | bar      | foo      | 200          | {"user":"bar"} |

    Scenario Outline: Sending requests with a body
        Given a file named "features/requestWithBody.feature" with:
            """
            Feature: Test request with body
                In order to send request with body
                As a context developer
                I need to have an step for this

                Scenario: Endpoint echo's request body in response
                    When I request "echo" using HTTP POST with body:
                        '''
                        <data>
                        '''
                    Then the response body should be "<data>"
            """
        When I run "behat features/requestWithBody.feature"
        Then it should pass with:
            """
            ..

            1 scenario (1 passed)
            2 steps (2 passed)
            """

        Examples:
            | data      |
            | Some data |
            |           |
