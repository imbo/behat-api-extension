Feature: Test steps to set a request body
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

    Scenario: Set the request body to a string
        Given a file named "features/givens.feature" with:
            """
            Feature: Set the request body to a string
                Scenario: Use steps to set the request body
                    Given the request body is:
                    '''
                    foobar
                    '''
                    When I request "/echo"
                    Then the response body is:
                    '''
                    foobar
                    '''

            """
        When I run "behat features/givens.feature"
        Then it should pass with:
            """
            ...

            1 scenario (1 passed)
            3 steps (3 passed)
            """

    Scenario: Set the request body to the contents of a file
        Given a file named "some/file.txt" with:
            """
            some file
            """
        And a file named "features/givens.feature" with:
            """
            Feature: Set the request body to a path
                Scenario: Use steps to set the request body
                    Given the request body contains "some/file.txt"
                    When I request "/echo"
                    Then the response body is:
                    '''
                    some file
                    '''
                    And the "Content-Type" response header is "text/plain;charset=UTF-8"

            """
        When I run "behat features/givens.feature"
        Then it should pass with:
            """
            ....

            1 scenario (1 passed)
            4 steps (4 passed)
            """
