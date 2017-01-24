Feature: Assertion steps can fail
    In order to test the extension
    As a developer
    I want to be able to test all available steps and outcomes

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

    Scenario: Assert that the response is not failure
        Given a file named "features/assert-response-code-is-not.feature" with:
            """
            Feature: Make request and assert response code is not
                Scenario: Make request
                    When I request "/"
                    Then the response code is not 200
            """
        When I run "behat features/assert-response-code-is-not.feature"
        Then it should fail with:
            """
            Did not expect response code 200. (Imbo\BehatApiExtension\Exception\AssertionFailedException)
            """

    Scenario: Assert response reason phrase failure
        Given a file named "features/assert-response-reason-phrase.feature" with:
            """
            Feature: Make request and assert response reason phrase
                Scenario: Make request
                    When I request "/"
                    Then the response reason phrase is "ok"
            """
        When I run "behat features/assert-response-reason-phrase.feature"
        Then it should fail with:
            """
            Expected response reason phrase "ok", got "OK". (Imbo\BehatApiExtension\Exception\AssertionFailedException)
            """

    Scenario: Assert response reason phrase is not failure
        Given a file named "features/assert-response-reason-phrase-is-not.feature" with:
            """
            Feature: Make request and assert response reason phrase is not
                Scenario: Make request
                    When I request "/"
                    Then the response reason phrase is not "OK"
            """
        When I run "behat features/assert-response-reason-phrase-is-not.feature"
        Then it should fail with:
            """
            Did not expect response reason phrase "OK". (Imbo\BehatApiExtension\Exception\AssertionFailedException)
            """

    Scenario: Assert response reason phrase matches failure
        Given a file named "features/assert-response-reason-phrase-matches.feature" with:
            """
            Feature: Make request and assert response reason phrase matches
                Scenario: Make request
                    When I request "/"
                    Then the response reason phrase matches "/FOOBAR/"
            """
        When I run "behat features/assert-response-reason-phrase-matches.feature"
        Then it should fail with:
            """
            Expected the response reason phrase to match the regular expression "/FOOBAR/", got "OK". (Imbo\BehatApiExtension\Exception\AssertionFailedException)
            """

    Scenario: Assert response status line failure
        Given a file named "features/assert-response-status-line.feature" with:
            """
            Feature: Make request and assert response status line
                Scenario: Make request
                    When I request "/"
                    Then the response status line is "200 ok"
            """
        When I run "behat features/assert-response-status-line.feature"
        Then it should fail with:
            """
            Expected response status line "200 ok", got "200 OK". (Imbo\BehatApiExtension\Exception\AssertionFailedException)
            """

    Scenario: Assert response status line is not failure
        Given a file named "features/assert-response-status-line-is-not.feature" with:
            """
            Feature: Make request and assert response status line is not
                Scenario: Make request
                    When I request "/"
                    Then the response status line is not "200 OK"
            """
        When I run "behat features/assert-response-status-line-is-not.feature"
        Then it should fail with:
            """
            Did not expect response status line "200 OK". (Imbo\BehatApiExtension\Exception\AssertionFailedException)
            """

    Scenario: Assert response status line matches failure
        Given a file named "features/assert-response-status-line-matches.feature" with:
            """
            Feature: Make request and assert response status line matches
                Scenario: Make request
                    When I request "/"
                    Then the response status line matches "/200 ok/"
            """
        When I run "behat features/assert-response-status-line-matches.feature"
        Then it should fail with:
            """
            Expected the response status line to match the regular expression "/200 ok/", got "200 OK". (Imbo\BehatApiExtension\Exception\AssertionFailedException)
            """

    Scenario: Assert response group is failure
        Given a file named "features/assert-response-group.feature" with:
            """
            Feature: Make request and assert response group
                Scenario: Make request
                    When I request "/"
                    Then the response is "informational"
            """
        When I run "behat features/assert-response-group.feature"
        Then it should fail with:
            """
            Expected response group "informational", got "success" (response code: 200). (Imbo\BehatApiExtension\Exception\AssertionFailedException)
            """

    Scenario: Assert response group is not failure
        Given a file named "features/assert-response-group-is-not.feature" with:
            """
            Feature: Make request and assert response group is not
                Scenario: Make request
                    When I request "/"
                    Then the response is not "success"
            """
        When I run "behat features/assert-response-group-is-not.feature"
        Then it should fail with:
            """
            Did not expect response to be in the "success" group (response code: 200). (Imbo\BehatApiExtension\Exception\AssertionFailedException)
            """

    Scenario: Assert response header exists failure
        Given a file named "features/assert-response-header-exists.feature" with:
            """
            Feature: Make request and assert response header exists
                Scenario: Make request
                    When I request "/"
                    Then the "FOO" response header exists
            """
        When I run "behat features/assert-response-header-exists.feature"
        Then it should fail with:
            """
            The "FOO" response header does not exist. (Imbo\BehatApiExtension\Exception\AssertionFailedException)
            """

    Scenario: Assert response header exists does not exist failure
        Given a file named "features/assert-response-header-does-not-exist.feature" with:
            """
            Feature: Make request and assert response header does not exists
                Scenario: Make request
                    When I request "/"
                    Then the "X-Foo" response header does not exist
            """
        When I run "behat features/assert-response-header-does-not-exist.feature"
        Then it should fail with:
            """
            The "X-Foo" response header should not exist. (Imbo\BehatApiExtension\Exception\AssertionFailedException)
            """

    Scenario: Assert response header is failure
        Given a file named "features/assert-response-header-is.feature" with:
            """
            Feature: Make request and assert response header is
                Scenario: Make request
                    When I request "/"
                    Then the "X-Foo" response header is "bar"
            """
        When I run "behat features/assert-response-header-is.feature"
        Then it should fail with:
            """
            Expected the "X-Foo" response header to be "bar", got "foo". (Imbo\BehatApiExtension\Exception\AssertionFailedException)
            """

    Scenario: Assert response header matches failure
        Given a file named "features/assert-response-header-matches.feature" with:
            """
            Feature: Make request and assert response header matches
                Scenario: Make request
                    When I request "/"
                    Then the "X-Foo" response header matches "/bar/"
            """
        When I run "behat features/assert-response-header-matches.feature"
        Then it should fail with:
            """
            Expected the "X-Foo" response header to match the regular expression "/bar/", got "foo". (Imbo\BehatApiExtension\Exception\AssertionFailedException)
            """

    Scenario: Assert response body is empty object failure
        Given a file named "features/assert-response-body-is-empty-object.feature" with:
            """
            Feature: Make request and assert response body is empty object
                Scenario: Make request
                    Given the request body is:
                        '''
                        {"foo":"bar"}
                        '''
                    When I request "/echo"
                    Then the response body is an empty JSON object
            """
        When I run "behat features/assert-response-body-is-empty-object.feature"
        Then it should fail
        And the output should contain:
            """
            Expected response body to be an empty JSON object, got "{
            """
        And the output should contain:
            """
            "foo": "bar"
            """
        And the output should contain:
            """
            }". (Imbo\BehatApiExtension\Exception\AssertionFailedException)
            """

    Scenario: Assert response body is empty array failure
        Given a file named "features/assert-response-body-is-empty-array.feature" with:
            """
            Feature: Make request and assert response body is empty array
                Scenario: Make request
                    Given the request body is:
                        '''
                        ["list item"]
                        '''
                    When I request "/echo"
                    Then the response body is an empty JSON array
            """
        When I run "behat features/assert-response-body-is-empty-array.feature"
        Then it should fail
        And the output should contain:
            """
            Expected response body to be an empty JSON array, got "[
            """
        And the output should contain:
            """
            "list item"
            """
        And the output should contain:
            """
            ]". (Imbo\BehatApiExtension\Exception\AssertionFailedException)
            """

    Scenario: Assert response body JSON array length failure
        Given a file named "features/assert-response-body-json-array-length.feature" with:
            """
            Feature: Make request and assert response body JSON array length
                Scenario: Make request
                    Given the request body is:
                        '''
                        ["some item"]
                        '''
                    When I request "/echo"
                    Then the response body is a JSON array of length 4
            """
        When I run "behat features/assert-response-body-json-array-length.feature"
        Then it should fail
        And the output should contain:
            """
            Expected response body to be a JSON array with 4 entries, got 1: "[
            """
        And the output should contain:
            """
            "some item"
            """
        And the output should contain:
            """
            ]". (Imbo\BehatApiExtension\Exception\AssertionFailedException)
            """

    Scenario: Assert response body JSON array min length failure
        Given a file named "features/assert-response-body-json-array-min-length.feature" with:
            """
            Feature: Make request and assert response body JSON array min length
                Scenario: Make request
                    Given the request body is:
                        '''
                        ["some item"]
                        '''
                    When I request "/echo"
                    Then the response body is a JSON array with a length of at least 2
            """
        When I run "behat features/assert-response-body-json-array-min-length.feature"
        Then it should fail
        And the output should contain:
            """
            Expected response body to be a JSON array with at least 2 entries, got 1: "[
            """
        And the output should contain:
            """
            "some item"
            """
        And the output should contain:
            """
            ]". (Imbo\BehatApiExtension\Exception\AssertionFailedException)
            """

    Scenario: Assert response body JSON array max length failure
        Given a file named "features/assert-response-body-json-array-max-length.feature" with:
            """
            Feature: Make request and assert response body JSON array max length
                Scenario: Make request
                    Given the request body is:
                        '''
                        ["some item", "some other item"]
                        '''
                    When I request "/echo"
                    Then the response body is a JSON array with a length of at most 1
            """
        When I run "behat features/assert-response-body-json-array-max-length.feature"
        Then it should fail
        And the output should contain:
            """
            Expected response body to be a JSON array with at most 1 entry, got 2: "[
            """
        And the output should contain:
            """
            "some item"
            """
        And the output should contain:
            """
            "some other item"
            """
        And the output should contain:
            """
            ]". (Imbo\BehatApiExtension\Exception\AssertionFailedException)
            """

    Scenario: Assert response body is failure
        Given a file named "features/assert-response-body-is.feature" with:
            """
            Feature: Make request and assert response body is some content
                Scenario: Make request
                    Given the request body is:
                        '''
                        response body
                        '''
                    When I request "/echo"
                    Then the response body is:
                        '''
                        foobar
                        '''
            """
        When I run "behat features/assert-response-body-is.feature"
        Then it should fail with:
            """
            Expected response body "foobar", got "response body". (Imbo\BehatApiExtension\Exception\AssertionFailedException)
            """

    Scenario: Assert response body matches failure
        Given a file named "features/assert-response-body-matches.feature" with:
            """
            Feature: Make request and assert response body matches some content
                Scenario: Make request
                    Given the request body is:
                        '''
                        response body
                        '''
                    When I request "/echo"
                    Then the response body matches:
                        '''
                        /foobar/
                        '''
            """
        When I run "behat features/assert-response-body-matches.feature"
        Then it should fail with:
            """
            Expected response body to match regular expression "/foobar/", got "response body". (Imbo\BehatApiExtension\Exception\AssertionFailedException)
            """
