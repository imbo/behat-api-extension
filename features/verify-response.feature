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
                        apiClient:
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
                    And the response reason phrase matches "/ok/i"
                    And the response reason phrase is not "Not Modified"
                    And the response status line is "200 OK"
                    And the response status line is not "304 Not Modified"
                    And the response status line matches "/200 ok/i"
                    And the response is "success"
                    And the response is not "client error"
                    And the "X-Foo" response header exists
                    And the "X-Bar" response header does not exist
                    And the "x-foo" response header is "foo"
                    And the "x-foo" response header is not "bar"
                    And the "x-foo" response header matches "/FOO/i"
                    And the response body is:
                        '''
                        {"null":null,"string":"value","integer":42,"float":4.2,"boolean true":true,"boolean false":false,"list":[1,2,3,[1],{"foo":"bar"}],"sub":{"string":"value","integer":42,"float":4.2,"boolean true":true,"boolean false":false,"list":[1,2,3,[1],{"foo":"bar"}]},"types":{"string":"string","integer":123,"double":1.23,"array":[1,"2",3],"boolean":true,"null":null,"scalar":"123"}}
                        '''
                    And the response body is not:
                        '''
                        foobar
                        '''
                    And the response body matches:
                        '''
                        /"list":\[.*?\]/
                        '''
                    And the response body contains JSON:
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
            ....................

            1 scenario (1 passed)
            20 steps (20 passed)
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
                    Then the response body is a JSON array of length 3
                    And the response body is a JSON array with a length of at most 3
                    And the response body is a JSON array with a length of at most 4
                    And the response body is a JSON array with a length of at least 1
                    And the response body is a JSON array with a length of at least 2
                    And the response body is a JSON array with a length of at least 3
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
                    Then the response body is a JSON array of length 0
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
                    Then the response body is a JSON array of length 4
                    And the response body contains JSON:
                    '''
                    {
                        "[0]": 1,
                        "[1]": "foo",
                        "[2]": {"foo": "bar", "bar": "foo"},
                        "[3]": [1, 2, 3]
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

    @example-from-docs
    Scenario:
        Given a file named "features/example.feature" with:
            """
            Feature:
                Scenario:
                    Given the request body is:
                        '''
                        {"foo":"bar"}
                        '''
                    When I request "/echo" using HTTP POST
                    Then the response body is:
                        '''
                        {"foo":"bar"}
                        '''
            """
        When I run "behat features/example.feature"
        Then it should pass with:
            """
            ...

            1 scenario (1 passed)
            3 steps (3 passed)
            """

    @example-from-docs
    Scenario:
        Given a file named "features/example.feature" with:
            """
            Feature:
                Scenario:
                    Given the request body is:
                        '''
                        foo
                        '''
                    When I request "/echo" using HTTP POST
                    Then the response body is:
                        '''
                        foo
                        '''
            """
        When I run "behat features/example.feature"
        Then it should pass with:
            """
            ...

            1 scenario (1 passed)
            3 steps (3 passed)
            """

    @example-from-docs
    Scenario:
        Given a file named "features/example.feature" with:
            """
            Feature:
                Scenario:
                    Given the request body is:
                        '''
                        foo
                        '''
                    When I request "/echo" using HTTP POST
                    Then the response body is not:
                        '''
                        some value
                        '''
            """
        When I run "behat features/example.feature"
        Then it should pass with:
            """
            ...

            1 scenario (1 passed)
            3 steps (3 passed)
            """

    @example-from-docs
    Scenario:
        Given a file named "features/example.feature" with:
            """
            Feature:
                Scenario:
                    Given the request body is:
                        '''
                        {"foo":"bar"}
                        '''
                    When I request "/echo" using HTTP POST
                    Then the response body matches:
                        '''
                        /^{"FOO": ?"BAR"}$/i
                        '''
            """
        When I run "behat features/example.feature"
        Then it should pass with:
            """
            ...

            1 scenario (1 passed)
            3 steps (3 passed)
            """

    @example-from-docs
    Scenario:
        Given a file named "features/example.feature" with:
            """
            Feature:
                Scenario:
                    Given the request body is:
                        '''
                        {"foo":"bar"}
                        '''
                    When I request "/echo" using HTTP POST
                    Then the response body matches:
                        '''
                        /foo/
                        '''
            """
        When I run "behat features/example.feature"
        Then it should pass with:
            """
            ...

            1 scenario (1 passed)
            3 steps (3 passed)
            """

    @example-from-docs
    Scenario:
        Given a file named "features/example.feature" with:
            """
            Feature:
                Scenario:
                    Given the request body is:
                        '''
                        {
                          "string": "string value",
                          "integer": 123,
                          "double": 1.23,
                          "boolean": true,
                          "null": null,
                          "object":
                          {
                            "string": "string value",
                            "integer": 123,
                            "double": 1.23,
                            "boolean": true,
                            "null": null,
                            "object":
                            {
                              "string": "string value",
                              "integer": 123,
                              "double": 1.23,
                              "boolean": true,
                              "null": null
                            }
                          },
                          "array":
                          [
                            "string value",
                            123,
                            1.23,
                            true,
                            null,
                            {
                              "string": "string value",
                              "integer": 123,
                              "double": 1.23,
                              "boolean": true,
                              "null": null
                            }
                          ]
                        }
                        '''
                    When I request "/echo" using HTTP POST
                    Then the response body contains JSON:
                        '''
                        {
                          "string": "string value",
                          "boolean": true
                        }
                        '''
                    Then the response body contains JSON:
                        '''
                        {
                        "object":
                        {
                          "string": "string value",
                          "object":
                          {
                            "null": null,
                            "integer": 123
                          }
                        }
                      }
                      '''
                    Then the response body contains JSON:
                        '''
                        {
                          "array":
                          [
                            true,
                            "string value",
                            {
                              "integer": 123
                            }
                          ]
                        }
                        '''
            """
        When I run "behat features/example.feature"
        Then it should pass with:
            """
            .....

            1 scenario (1 passed)
            5 steps (5 passed)
            """

    @example-from-docs
    Scenario:
        Given a file named "features/example.feature" with:
            """
            Feature:
                Scenario:
                    Given the request body is:
                        '''
                        {
                          "items":
                          [
                            "foo",
                            "bar",
                            "foobar",
                            "barfoo",
                            123
                          ]
                        }
                        '''
                    When I request "/echo" using HTTP POST
                    Then the response body contains JSON:
                        '''
                        {"items": "@arrayLength(5)"}
                        '''
                    And the response body contains JSON:
                        '''
                        {"items": "@arrayMaxLength(10)"}
                        '''
                    And the response body contains JSON:
                        '''
                        {"items": "@arrayMinLength(3)"}
                        '''
            """
        When I run "behat features/example.feature"
        Then it should pass with:
            """
            .....

            1 scenario (1 passed)
            5 steps (5 passed)
            """

    @example-from-docs
    Scenario:
        Given a file named "features/example.feature" with:
            """
            Feature:
                Scenario:
                    Given the request body is:
                        '''
                        {
                          "boolean value": true,
                          "int value": 123,
                          "double value": 1.23,
                          "string value": "some string",
                          "array value": [1, 2, 3],
                          "object value": {"foo": "bar"},
                          "null value": null,
                          "scalar value": 3.1416
                        }
                        '''
                    When I request "/echo" using HTTP POST
                    Then the response body contains JSON:
                        '''
                        {
                          "boolean value": "@variableType(boolean)",
                          "int value": "@variableType(integer)",
                          "double value": "@variableType(double)",
                          "string value": "@variableType(string)",
                          "array value": "@variableType(array)",
                          "object value": "@variableType(object)",
                          "null value": "@variableType(null)",
                          "scalar value": "@variableType(scalar)"
                        }
                        '''
            """
        When I run "behat features/example.feature"
        Then it should pass with:
            """
            ...

            1 scenario (1 passed)
            3 steps (3 passed)
            """

    @example-from-docs
    Scenario:
        Given a file named "features/example.feature" with:
            """
            Feature:
                Scenario:
                    Given the request body is:
                        '''
                        {
                          "foo": "expression",
                          "bar":
                          {
                            "baz": 1234567890
                          }
                        }
                        '''
                    When I request "/echo" using HTTP POST
                    Then the response body contains JSON:
                        '''
                        {
                          "foo": "@regExp(/(some|expression)/i)",
                          "bar":
                          {
                            "baz": "@regExp(/[0-9]+/)"
                          }
                        }
                        '''
            """
        When I run "behat features/example.feature"
        Then it should pass with:
            """
            ...

            1 scenario (1 passed)
            3 steps (3 passed)
            """

    @example-from-docs
    Scenario:
        Given a file named "features/example.feature" with:
            """
            Feature:
                Scenario:
                    Given the request body is:
                        '''
                        {
                          "items":
                          [
                            "foo",
                            "bar",
                            {
                              "some":
                              {
                                "nested": "object",
                                "foo": "bar"
                              }
                            },
                            [1, 2, 3]
                          ]
                        }
                        '''
                    When I request "/echo" using HTTP POST
                    Then the response body contains JSON:
                        '''
                        {
                          "items[0]": "foo",
                          "items[1]": "@regExp(/(foo|bar|baz)/)",
                          "items[2]":
                          {
                            "some":
                            {
                              "foo": "@regExp(/ba(r|z)/)"
                            }
                          },
                          "items[3]": "@arrayLength(3)"
                        }
                        '''
            """
        When I run "behat features/example.feature"
        Then it should pass with:
            """
            ...

            1 scenario (1 passed)
            3 steps (3 passed)
            """

    @example-from-docs
    Scenario:
        Given a file named "features/example.feature" with:
            """
            Feature:
                Scenario:
                    Given the request body is:
                        '''
                        [
                          "foo",
                          123,
                          {
                            "foo": "bar"
                          },
                          "bar",
                          [1, 2, 3]
                        ]
                        '''
                    When I request "/echo" using HTTP POST
                    Then the response body contains JSON:
                        '''
                        {
                          "[0]": "foo",
                          "[1]": 123,
                          "[2]":
                          {
                            "foo": "bar"
                          },
                          "[3]": "@regExp(/bar/)",
                          "[4]": "@arrayLength(3)"
                        }
                        '''
            """
        When I run "behat features/example.feature"
        Then it should pass with:
            """
            ...

            1 scenario (1 passed)
            3 steps (3 passed)
            """
