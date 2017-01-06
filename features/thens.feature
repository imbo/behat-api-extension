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
                    And the response code is not 400
                    And the response reason phrase is "OK"
                    And the response status line is "200 OK"
                    And the response is success
                    And the response is not "client error"
                    And the "X-Foo" response header exists
                    And the "X-Bar" response header does not exist
                    And the "x-foo" response header is foo
                    And the "x-foo" response header matches "/FOO/i"
                    And the response body is:
                    '''
                    {"null":null,"string":"value","integer":42,"float":4.2,"boolean true":true,"boolean false":false,"list":[1,2,3,[1],{"foo":"bar"}],"sub":{"string":"value","integer":42,"float":4.2,"boolean true":true,"boolean false":false,"list":[1,2,3,[1],{"foo":"bar"}]}}
                    '''
                    And the response body matches:
                    '''
                    /"list":\[.*?\]/
                    '''
                    And the response body contains:
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
            ..............

            1 scenario (1 passed)
            14 steps (14 passed)
            """

    Scenario: Use Then steps to verify responses with arrays
        Given a file named "features/thens-array.feature" with:
            """
            Feature: Set up the request
                Scenario: Use all Given steps in a scenario
                    Given the request body is:
                    '''
                    [1, 2, 3]
                    '''
                    When I request "/echo?json" using HTTP POST
                    Then the response body is an array of length 3
                    And the response body is an array with a length of at most 3
                    And the response body is an array with a length of at most 4
                    And the response body is an array with a length of at least 1
                    And the response body is an array with a length of at least 2
                    And the response body is an array with a length of at least 3
            """
        When I run "behat features/thens-array.feature"
        Then it should pass with:
            """
            ........

            1 scenario (1 passed)
            8 steps (8 passed)
            """

    Scenario: Use Then step to verify responses with empty JSON object
        Given a file named "features/thens-empty-json-object.feature" with:
            """
            Feature: Test for empty JSON object in response body
                Scenario: Assert that the response body is an empty object
                    When I request "/emptyObject"
                    Then the response body is an empty JSON object
            """
        When I run "behat features/thens-empty-json-object.feature"
        Then it should pass with:
            """
            ..

            1 scenario (1 passed)
            2 steps (2 passed)
            """

    Scenario: Use Then step to verify responses with empty JSON array
        Given a file named "features/thens-empty-json-array.feature" with:
            """
            Feature: Test for empty JSON array in response body
                Scenario: Assert that the response body is an empty JSON array
                    When I request "/emptyArray"
                    Then the response body is an array of length 0
                    And the response body is an empty JSON array
            """
        When I run "behat features/thens-empty-json-array.feature"
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
                    Given the request body is:
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
                    When I request "/echo?json" using HTTP POST
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
            .....

            1 scenario (1 passed)
            5 steps (5 passed)
            """

    Scenario: Verify a custom response reason phrase
        Given a file named "features/thens.feature" with:
            """
            Feature: Set up the request
                Scenario: Verify a custom response reason phrase
                    When I request "/customReasonPhrase?phrase=foo"
                    Then the response reason phrase is "foo"
            """
        When I run "behat features/thens.feature"
        Then it should pass with:
            """
            ..

            1 scenario (1 passed)
            2 steps (2 passed)
            """
