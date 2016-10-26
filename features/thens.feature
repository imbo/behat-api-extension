Feature: Test Then steps
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
                        base_uri: http://localhost:8080

                suites:
                    default:
                        contexts: ['Imbo\BehatApiExtension\Context\ApiContext']
            """

    Scenario: Use Then steps to verify responses
        Given a file named "features/thens.feature" with:
            """
            Feature: Set up the request
                Scenario: Use all Given steps in a scenario
                    When I request "/"
                    Then the response code is 200
                    Then the response code is not 400
                    Then the response is success
                    Then the response is not "client error"
                    Then the "X-Foo" response header exists
                    Then the "X-Bar" response header does not exist
                    Then the "x-foo" response header is foo
                    Then the "x-foo" response header matches "/FOO/i"
                    Then the response body is:
                    '''
                    {"null":null,"string":"value","integer":42,"float":4.2,"boolean true":true,"boolean false":false,"list":[1,2,3,[1],{"foo":"bar"}],"sub":{"string":"value","integer":42,"float":4.2,"boolean true":true,"boolean false":false,"list":[1,2,3,[1],{"foo":"bar"}]}}
                    '''
                    Then the response body matches:
                    '''
                    /"list":\[.*?\]/
                    '''
                    Then the response body contains:
                    '''
                    {
                        "null": null,
                        "string": "value",
                        "integer": 42,
                        "float": 4.2,
                        "boolean true": true,
                        "boolean false": false,
                        "list": [1, 2, 3, [1], {"foo": "bar"}],
                        "list[0]": 1,
                        "list[1]": 2,
                        "list[2]": 3,
                        "list[3]": [1],
                        "list[4]": {"foo": "bar"},
                        "sub": {
                            "string": "value",
                            "integer": 42,
                            "float": 4.2,
                            "boolean true": true,
                            "boolean false": false,
                            "list": [1, 2, 3, [1], {"foo": "bar"}],
                            "list[0]": 1,
                            "list[1]": 2,
                            "list[2]": 3,
                            "list[3]": [1],
                            "list[4]": {"foo": "bar"}
                        }
                    }
                    '''
            """
        When I run "behat features/thens.feature"
        Then it should pass with:
            """
            ............

            1 scenario (1 passed)
            12 steps (12 passed)
            """

    Scenario: Use Then steps to verify responses with arrays
        Given a file named "features/thens-array.feature" with:
            """
            Feature: Set up the request
                Scenario: Use all Given steps in a scenario
                    When I request "/echo?json" using HTTP POST with body:
                    '''
                    [1, 2, 3]
                    '''
                    Then the response body is an array of length 3
                    Then the response body is an array with a length of at most 3
                    Then the response body is an array with a length of at most 4
                    Then the response body is an array with a length of at least 1
                    Then the response body is an array with a length of at least 2
                    Then the response body is an array with a length of at least 3
            """
        When I run "behat features/thens-array.feature"
        Then it should pass with:
            """
            .......

            1 scenario (1 passed)
            7 steps (7 passed)
            """

    Scenario: Use Then step to verify responses with empty array
        Given a file named "features/thens-empty-array.feature" with:
            """
            Feature: Set up the request
                Scenario: Use all Given steps in a scenario
                    When I request "/echo?json" using HTTP POST with body:
                    '''
                    []
                    '''
                    Then the response body is an array of length 0
                    Then the response body is an empty array
            """
        When I run "behat features/thens-empty-array.feature"
        Then it should pass with:
            """
            ...

            1 scenario (1 passed)
            3 steps (3 passed)
            """

    Scenario: Use Then steps to verify responses with numerical array as root
        Given a file named "features/response-with-numerical-array.feature" with:
            """
            Feature: Test response body with numerical array as root
                Scenario: Response returns numerical array
                    When I request "/echo?json" using HTTP POST with body:
                    '''
                    [
                        1,
                        "foo",
                        {
                            "foo": "bar",
                            "bar": "foo"
                        },
                        [1, 2, 3]
                    ]
                    '''
                    Then the response body is an array of length 4
                    And the response body contains:
                    '''
                    {
                        "[0]": 1,
                        "[1]": "foo",
                        "[2]": {"foo": "bar", "bar": "foo"},
                        "[3]": [1, 2, 3]
                    }
                    '''
                    And the response body contains:
                    '''
                    {
                        "[1]": "<re>/foo/</re>",
                        "[3]": "@length(3)"
                    }
                    '''
            """
        When I run "behat features/response-with-numerical-array.feature"
        Then it should pass with:
            """
            ....

            1 scenario (1 passed)
            4 steps (4 passed)
            """

