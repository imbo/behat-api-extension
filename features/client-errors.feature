Feature: Test client errors
    In order to test client errors
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

    Scenario: Assert a response code of 403
        Given a file named "features/403.feature" with:
            """
            Feature: Request an endpoint that responds with HTTP 403
                Scenario: Request endpoint
                    When I request "/403"
                    Then the response code is 403
                    And the response reason phrase is "Forbidden"
                    And the response status line is "403 Forbidden"
            """
        When I run "behat features/403.feature"
        Then it should pass with:
            """
            ....

            1 scenario (1 passed)
            4 steps (4 passed)
            """
