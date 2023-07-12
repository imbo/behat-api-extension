Feature: Test built in jwt matcher functions
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

    Scenario: Use array contains comparator with JWT
        Given a file named "features/jwt.feature" with:
            """
            Feature:
                Scenario:
                    Given the response body contains a JWT identified by "jwt1", signed with "secret":
                        '''
                        {
                            "sub": "1234567890",
                            "name": "John Doe",
                            "admin": true
                        }
                        '''
                    And the response body contains a JWT identified by "jwt2", signed with "secret":
                        '''
                        {
                            "sub": "@variableType(string)",
                            "name": "@variableType(string)",
                            "admin": "@variableType(bool)"
                        }
                        '''
                    And the response body contains a JWT identified by "jwt3", signed with "secret":
                        '''
                        {
                            "sub": "@regExp(/^[0-9]+$/)"
                        }
                        '''
                    And the request body is:
                        '''
                        {
                            "jwt": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiYWRtaW4iOnRydWV9.TJVA95OrM7E2cBab30RMHrHDcEfxjoYZgeFONFh7HgQ"
                        }
                        '''
                    When I request "/echo?json" using HTTP POST
                    Then the response body contains JSON:
                        '''
                        {
                            "jwt": "@jwt(jwt1)"
                        }
                        '''
                    And the response body contains JSON:
                        '''
                        {
                            "jwt": "@jwt(jwt2)"
                        }
                        '''
                    And the response body contains JSON:
                        '''
                        {
                            "jwt": "@jwt(jwt3)"
                        }
                        '''
            """
        When I run "behat features/jwt.feature"
        Then it should pass with:
            """
            ........

            1 scenario (1 passed)
            8 steps (8 passed)
            """

    Scenario: Use array contains comparator with JWT with failures
        Given a file named "features/jwt-failures.feature" with:
            """
            Feature:
                Scenario:
                    Given the response body contains a JWT identified by "jwt1", signed with "secret":
                        '''
                        {
                            "sub1": "1234567890"
                        }
                        '''
                    And the request body is:
                        '''
                        {
                            "jwt": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiYWRtaW4iOnRydWV9.TJVA95OrM7E2cBab30RMHrHDcEfxjoYZgeFONFh7HgQ"
                        }
                        '''
                    When I request "/echo?json" using HTTP POST
                    Then the response body contains JSON:
                        '''
                        {
                            "jwt": "@jwt(jwt1)"
                        }
                        '''
            """
        When I run "behat features/jwt-failures.feature"
        Then it should fail with:
            """
            Function "jwt" failed with error message: "Haystack object is missing the "sub1" key.
            """
