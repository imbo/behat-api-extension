Feature: Fix issue #34
    In order to validate a list of objects
    As an extension
    I need to recursively compare arrays / lists

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

    Scenario: Recursively compare objects in a list
        Given a file named "features/issue-34.feature" with:
            """
            Feature:
                Scenario:
                    Given the request body is:
                        '''
                        [
                            {
                                "id": 1,
                                "gameId": 1
                            },
                            {
                                "id": 2,
                                "gameId": 2
                            }
                        ]
                        '''
                    When I request "/echo?json" using HTTP POST
                    Then the response code is 200
                    And the response body contains JSON:
                        '''
                        [
                            {
                                "id": 1,
                                "gameId": 1
                            },
                            {
                                "id": 2,
                                "gameId": 2
                            }
                        ]
                        '''
            """
        When I run "behat features/issue-34.feature"
        Then it should pass with:
            """
            ....

            1 scenario (1 passed)
            4 steps (4 passed)
            """

    Scenario: Recursively compare objects in a list with failed result
        Given a file named "features/issue-34-failure.feature" with:
            """
            Feature:
                Scenario:
                    Given the request body is:
                        '''
                        [
                            {
                                "id": 1,
                                "gameId": 1
                            },
                            {
                                "id": 2,
                                "gameId": 2
                            }
                        ]
                        '''
                    When I request "/echo?json" using HTTP POST
                    Then the response code is 200
                    And the response body contains JSON:
                        '''
                        [
                            {
                                "id": 1,
                                "gameId": 1
                            },
                            {
                                "id": 1,
                                "gameId": 2
                            }
                        ]
                        '''
            """
        When I run "behat features/issue-34-failure.feature"
        Then it should fail with:
            """
            The object in needle was not found in the object elements in the haystack.
            """
