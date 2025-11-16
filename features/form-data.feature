Feature: Test form-data handling
    In order to test form-data handling
    As a developer
    I want to be able to test all related steps

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

    Scenario: Attach form data to the request with no HTTP method specified
        Given a file named "features/attach-form-data.feature" with:
            """
            Feature: Set up the request
                Scenario: Use the Given step to attach form-data
                    Given the following form parameters are set:
                        | name | value     |
                        | name | some name |
                        | foo  | bar       |
                        | bar  | foo       |
                        | bar  | bar       |
                    When I request "/requestInfo"
                    Then the response body contains JSON:
                    '''
                    {
                        "_POST": {
                            "name": "some name",
                            "foo": "bar",
                            "bar": ["foo", "bar"]
                        },
                        "_FILES": "@arrayLength(0)",
                        "_SERVER": {
                            "REQUEST_METHOD": "POST"
                        }
                    }
                    '''

            """
        When I run "behat features/attach-form-data.feature"
        Then it should pass with:
            """
            ...

            1 scenario (1 passed)
            3 steps (3 passed)
            """

    Scenario: Attach form data to the request with custom HTTP method
        Given a file named "features/attach-form-data-http-patch.feature" with:
            """
            Feature: Set up the request
                Scenario: Use the Given step to attach form-data
                    Given the following form parameters are set:
                        | name | value |
                        | foo  | bar   |
                        | bar  | foo   |
                        | bar  | bar   |
                    When I request "/requestInfo" using HTTP PATCH
                    Then the response body contains JSON:
                    '''
                    {
                        "_POST": "@arrayLength(0)",
                        "_SERVER": {
                            "REQUEST_METHOD": "PATCH"
                        },
                        "requestBody": "foo=bar&bar%5B0%5D=foo&bar%5B1%5D=bar"
                    }
                    '''

            """
        When I run "behat features/attach-form-data-http-patch.feature"
        Then it should pass with:
            """
            ...

            1 scenario (1 passed)
            3 steps (3 passed)
            """

    Scenario: Attach form data and files to the request
        Given a file named "features/attach-form-data-and-files.feature" with:
            """
            Feature: Set up the request
                Scenario: Use the Given step to attach form-data
                    Given the following form parameters are set:
                        | name | value |
                        | foo  | bar   |
                        | bar  | foo   |
                        | bar  | bar   |
                    And I attach "behat.yml" to the request as file
                    When I request "/requestInfo"
                    Then the response body contains JSON:
                    '''
                    {
                        "_POST": {
                            "foo": "bar",
                            "bar": ["foo", "bar"]
                        },
                        "_FILES": {
                            "file": {
                                "name": "behat.yml",
                                "type": "text/yaml",
                                "tmp_name": "@regExp(/.*/)",
                                "error": 0,
                                "size": "@regExp(/[0-9]+/)"
                            }
                        },
                        "_SERVER": {
                            "REQUEST_METHOD": "POST"
                        }
                    }
                    '''

            """
        When I run "behat features/attach-form-data-and-files.feature"
        Then it should pass with:
            """
            ....

            1 scenario (1 passed)
            4 steps (4 passed)
            """

    Scenario: Attach multipart form data
        Given a file named "features/multipart-form-data.feature" with:
            """
            Feature: Set up the request
                Scenario: Verify form data
                    Given the following multipart form parameters are set:
                    | name     | value     |
                    | name     | some name |
                    | username | admin     |
                    | password | password  |
                    When I request "/requestInfo" using HTTP "POST"
                    Then the response body contains JSON:
                    '''
                    {
                        "_POST": {
                            "name": "some name",
                            "username": "admin",
                            "password": "password"
                        },
                        "_SERVER": {
                            "CONTENT_TYPE": "@regExp(/^multipart\\/form-data;/)"
                        }
                    }
                    '''

            """
        When I run "behat features/multipart-form-data.feature"
        Then it should pass with:
            """
            ...

            1 scenario (1 passed)
            3 steps (3 passed)
            """
