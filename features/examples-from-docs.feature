Feature: Test examples from the docs
    In order to have quality examples in the docs
    As an extension maintainer
    I want to be able to test all examples

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
                          "bool": true,
                          "null": null,
                          "object":
                          {
                            "string": "string value",
                            "integer": 123,
                            "double": 1.23,
                            "bool": true,
                            "null": null,
                            "object":
                            {
                              "string": "string value",
                              "integer": 123,
                              "double": 1.23,
                              "bool": true,
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
                              "bool": true,
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
                          "bool": true
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

    Scenario:
        Given a file named "features/example.feature" with:
            """
            Feature:
                Scenario:
                    Given the request body is:
                        '''
                        {
                          "bool value": true,
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
                          "bool value": "@variableType(bool)",
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

    Scenario:
        Given a file named "features/example.feature" with:
            """
            Feature:
                Scenario:
                    Given the request body is:
                        '''
                        {
                          "some-int": 123,
                          "some-double": 1.23,
                          "some-string": "123"
                        }
                        '''
                    When I request "/echo" using HTTP POST
                    Then the response body contains JSON:
                        '''
                        {
                          "some-int": "@gt(120)",
                          "some-double": "@gt(1.20)",
                          "some-string": "@gt(120)"
                        }
                        '''
                    And the response body contains JSON:
                        '''
                        {
                          "some-int": "@lt(125)",
                          "some-double": "@lt(1.25)",
                          "some-string": "@lt(125)"
                        }
                        '''
            """
        When I run "behat features/example.feature"
        Then it should pass with:
            """
            ....

            1 scenario (1 passed)
            4 steps (4 passed)
            """

    Scenario:
        Given a file named "features/example.feature" with:
            """
            Feature:
                Scenario:
                    Given the response body contains a JWT identified by "my JWT", signed with "secret":
                        '''
                        {
                          "user": "Some user"
                        }
                        '''
                    And the request body is:
                        '''
                        {
                          "value": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyIjoiU29tZSB1c2VyIn0.DsGGNmDD-PBnwMLiQxeSHDGmKBSdP0lSmWuaiwSxfQE"
                        }
                        '''
                    When I request "/echo" using HTTP POST
                    Then the response body contains JSON:
                        '''
                        {
                          "value": "@jwt(my JWT)"
                        }
                        '''
            """
        When I run "behat features/example.feature"
        Then it should pass with:
            """
            ....

            1 scenario (1 passed)
            4 steps (4 passed)
            """
