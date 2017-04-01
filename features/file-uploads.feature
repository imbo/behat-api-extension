Feature: Test file uploading
    In order to test file uploads
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

    Scenario: Attach files to the request
        Given a file named "features/attach-files.feature" with:
            """
            Feature: Set up the request
                Scenario: Use the Given step to attach files
                    Given I attach "behat.yml" to the request as file1
                    And I attach "features/attach-files.feature" to the request as file2
                    When I request "/files" using HTTP POST
                    Then the response body contains JSON:
                    '''
                    {
                        "file1": {
                            "name": "behat.yml",
                            "type": "text/yaml",
                            "tmp_name": "@regExp(/.*/)",
                            "error": 0,
                            "size": "@regExp(/[0-9]+/)"
                        },
                        "file2": {
                            "name": "attach-files.feature",
                            "type": "",
                            "tmp_name": "@regExp(/.*/)",
                            "error": 0,
                            "size": "@regExp(/[0-9]+/)"
                        }
                    }
                    '''
            """
        When I run "behat features/attach-files.feature"
        Then it should pass with:
            """
            ....

            1 scenario (1 passed)
            4 steps (4 passed)
            """
