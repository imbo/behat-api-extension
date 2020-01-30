Feature: Manipulate query string
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

    Scenario: Set single parameters
        Given a file named "features/query-params.feature" with:
            """
            Feature: Set up the request
                Scenario: Add query params
                    Given the query parameter "foo" is "bar"
                    Given the query parameter "bar" is "foo"
                    Given the query parameter "bar" is:
                        | value |
                        | foo   |
                        | bar   |
                    Given the following query parameters are set:
                        | name | value |
                        | foo  | bar   |
                        | bar  | foo   |
            """
        When I run "behat features/query-params.feature"
        Then it should pass with:
            """
            ....

            1 scenario (1 passed)
            4 steps (4 passed)
            """

    Scenario: Make a request with query parameters set
        Given a file named "features/query-params.feature" with:
            """
            Feature: Set up the request
                Scenario: Add query params
                    Given the query parameter "p1" is "v1"
                    Given the query parameter "p2" is "v2"
                    Given the query parameter "p3" is "v3"
                    Given the query parameter "p4" is "v4"
                    Given the query parameter "p4" is "v5"
                    Given the query parameter "p1" is:
                        | value |
                        | v6    |
                        | v7    |
                    Given the query parameter "p2" is:
                        | value |
                        | v8    |
                    Given the following query parameters are set:
                        | name | value |
                        | p3   | v9    |
                        | p5   | v10   |
                        | p6   | v11   |
                    When I request "/requestInfo?p5=v12"
                    Then the response body contains JSON:
                        '''
                        {
                            "_GET": {
                                "p1": ["v6", "v7"],
                                "p2": ["v8"],
                                "p3": "v9",
                                "p4": "v5",
                                "p5": "v10",
                                "p6": "v11"
                            }
                        }
                        '''

            """
        When I run "behat features/query-params.feature"
        Then it should pass with:
            """
            ..........

            1 scenario (1 passed)
            10 steps (10 passed)
            """