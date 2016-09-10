Feature: Test Given steps
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
                        base_uri: http://localhost:8080

                suites:
                    default:
                        contexts: ['Imbo\BehatApiExtension\Context\ApiContext']
            """

    Scenario: Use all Given steps to set up the request
        Given a file named "features/givens.feature" with:
            """
            Feature: Set up the request
                Scenario: Use all Given steps in a scenario
                    Given I am authenticating as "user" with password "password"
                    Given the "header" request header is "value"
                    Given I attach "features/givens.feature" to the request as "file"

            """
        When I run "behat features/givens.feature"
        Then it should pass with:
            """
            ...

            1 scenario (1 passed)
            3 steps (3 passed)
            """
