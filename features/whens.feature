Feature: Test When steps
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

    Scenario: Use all When steps to request paths
        Given a file named "features/whens.feature" with:
            """
            Feature: Set up the request
                Scenario: Use all Given steps in a scenario
                    When I request "/"
                    When I request "/" using HTTP "POST"
            """
        When I run "behat features/whens.feature"
        Then it should pass with:
            """
            ..

            1 scenario (1 passed)
            2 steps (2 passed)
            """
