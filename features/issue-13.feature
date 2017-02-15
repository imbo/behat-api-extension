Feature: Fix issue #13
    In order to check multidimensional arrays
    As an extension
    I need to recursively compare arrays

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

    Scenario: Recursively compare arrays
        Given a file named "features/issue-13.feature" with:
            """
            Feature: Recursively check arrays
                Scenario: Check the value for a sub-array
                    When I request "/issue-13"
                    Then the response code is 200
                    And the response body contains JSON:
                        '''
                        {
                            "customer": {
                                "images[0]": {
                                    "filename_client": "tech.ai"
                                }
                            }
                        }
                        '''
            """
        When I run "behat features/issue-13.feature"
        Then it should pass with:
            """
            ...

            1 scenario (1 passed)
            3 steps (3 passed)
            """
