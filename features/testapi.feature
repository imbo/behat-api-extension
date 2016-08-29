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
        Given a file named "features/request-with-body.feature" with:
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
        When I run "behat features/request-with-body.feature"
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

    Scenario Outline: Check for key in JSON response body
        Given a file named "features/look-for-key-in-json-body.feature" with:
            """
            Feature: Test assert key exists in JSON body
                In order to check if a key exists in the JSON body
                As a context developer
                I need to have a step for this

                Scenario: Look for key in response
                    When I request "echo?json=1" using HTTP POST with body:
                        '''
                        <request>
                        '''
                    Then the response body should contain JSON key "<key>"
            """
        When I run "behat features/look-for-key-in-json-body.feature"
        Then it should pass with:
            """
            ..

            1 scenario (1 passed)
            2 steps (2 passed)
            """

        Examples:
            | request                   | key |
            | {"foo":"bar"}             | foo |
            | {"foo":"bar","bar":"foo"} | bar |

    Scenario: Check for keys in JSON response body
        Given a file named "features/look-for-keys-in-json-body.feature" with:
            """
            Feature: Test assert multiple keys exist in JSON body
                In order to check if mulitple keys exist in the JSON body
                As a context developer
                I need to have a step for this

                Scenario: Look for keys in response
                    When I request "echo?json=1" using HTTP POST with body:
                        '''
                        {"foo":"bar","bar":"foo","foobar":"baz"}
                        '''
                    Then the response body should contain JSON keys:
                        | key    |
                        | foo    |
                        | bar    |
                        | foobar |
            """
        When I run "behat features/look-for-keys-in-json-body.feature"
        Then it should pass with:
            """
            ..

            1 scenario (1 passed)
            2 steps (2 passed)
            """

    Scenario Outline: Check for key/value pairs in JSON response body
        Given a file named "features/key-value-pairs.feature" with:
            """
            Feature: Test assert multiple keys exist in JSON body
                In order to check if key/value pairs exists in the response body
                As a context developer
                I need to have a step for this

                Scenario: Look for key/value pairs in the response body
                    When I request "echo?json=1" using HTTP POST with body:
                        '''
                        <request>
                        '''
                    Then the response body should contain JSON:
                        '''
                        <needle>
                        '''
            """
        When I run "behat features/key-value-pairs.feature"
        Then it should pass with:
            """
            ..

            1 scenario (1 passed)
            2 steps (2 passed)
            """

        Examples:
            | request                                  | needle                    |
            | {"foo":"bar"}                            | {"foo":"bar"}             |
            | {"foo":"bar","bar":"foo"}                | {"bar":"foo"}             |
            | {"foo":"bar","bar":"foo", "baz":[1,2,3]} | {"baz":[1,2,3]}           |
            | {"foo":"bar","bar":"foo", "baz":[1,2,3]} | {"foo":"bar","bar":"foo"} |

    Scenario Outline: Check for response code groups
        Given a file named "features/response-code-groups.feature" with:
            """
            Feature: Test assert response code is in a group
                In order to check if the response code is in a group
                As a context developer
                I need to have a step for this

                Scenario: Match response code to a group
                    When I request "<path>"
                    Then the response code should be <code>
                    And the response code means <group>
            """
        When I run "behat features/response-code-groups.feature"
        Then it should pass with:
            """
            ...

            1 scenario (1 passed)
            3 steps (3 passed)
            """

        Examples:
            | path          | code | group        |
            | /             | 200  | success      |
            | /clientError  | 400  | client error |
            | /serverError  | 500  | server error |

    Scenario: Attach a file that does not exist to the request
        Given a file named "features/attach-file-that-does-not-exist-to-request.feature" with:
            """
            Feature: Assert file that does not exist to the request
                In order to attach one or more files to the request
                As a context developer
                I need to have a step for this

                Scenario: Attach a file that does not exist
                    When I attach "foobar" to the request as "file"
                    And I request "files" using HTTP POST
            """
        When I run "behat features/attach-file-that-does-not-exist-to-request.feature"
        Then it should fail with:
            """
            File does not exist: foobar (InvalidArgumentException)
            """
