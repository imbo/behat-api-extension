Feature: Preserve explicit HTTP methods for multipart requests
    In order to submit multipart requests using different HTTP methods
    As a developer
    I want explicit HTTP methods to override the default POST method

    Background:
        Given a default Behat configuration file

    Scenario Outline: Send multipart form parameters with an explicit HTTP method
        Given a file named "features/multipart-method.feature" with:
            """
            Feature:
                Scenario:
                    Given the following multipart form parameters are set:
                        | name | value |
                        | foo  | bar   |
                    When I request "/echoHttpMethod" using HTTP <method>
                    Then the response body contains JSON:
                        '''
                        {"method": "<method>"}
                        '''
            """
        When I run "behat features/multipart-method.feature"
        Then it should pass with:
            """
            ...

            1 scenario (1 passed)
            3 steps (3 passed)
            """

        Examples:
            | method |
            | GET    |
            | POST   |
            | PUT    |
            | PATCH  |

    Scenario Outline: Send a file with an explicit HTTP method
        Given a file named "features/file-method.feature" with:
            """
            Feature:
                Scenario:
                    Given I attach "behat.php" to the request as file
                    When I request "/echoHttpMethod" using HTTP <method>
                    Then the response body contains JSON:
                        '''
                        {"method": "<method>"}
                        '''
            """
        When I run "behat features/file-method.feature"
        Then it should pass with:
            """
            ...

            1 scenario (1 passed)
            3 steps (3 passed)
            """

        Examples:
            | method |
            | GET    |
            | POST   |
            | PUT    |
            | PATCH  |
