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
                        base_uri: http://localhost:8080

                suites:
                    default:
                        contexts: ['Imbo\BehatApiExtension\Context\ApiContext']
            """

    Scenario: Attach form data to the request
        Given a file named "features/attach-form-data.feature" with:
            """
            Feature: Set up the request
                Scenario: Use the Given step to attach form-data
                    Given the following form parameters are set:
                        | name | value |
                        | foo  | bar   |
                        | bar  | foo   |
                        | bar  | bar   |
                    When I request "/formData"
                    Then the response body contains:
                    '''
                    {
                        "_POST": {
                            "foo": "bar",
                            "bar": ["foo", "bar"]
                        },
                        "_FILES": "@length(0)"
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
                    When I request "/formData"
                    Then the response body contains:
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
                                "tmp_name": "<re>/.*/</re>",
                                "error": 0,
                                "size": "<re>/[0-9]+/</re>"
                            }
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
