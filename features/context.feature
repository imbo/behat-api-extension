Feature: Client aware context
    In order to write scenario steps for API testing
    As a developer
    I need the Guzzle client in the feature context

    Background:
        Given a custom FeatureContext file named "FeatureContext-api-client-aware.php"

    Scenario: Context parameters
        Given a minimal Behat configuration file
        And a file named "features/client.feature" with:
            """
            Feature: API client
                In order to call the API
                As a feature runner
                I need to be able to access the client

                Scenario: client is set
                    Then the client should be set
            """
        When I run "behat -f progress features/client.feature"
        Then it should pass with:
            """
            .

            1 scenario (1 passed)
            1 step (1 passed)
            """
