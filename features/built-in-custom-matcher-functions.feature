Feature: Test built in matcher functions
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

    Scenario: Use custom built in matcher functions
        Given a file named "features/custom-matcher-functions.feature" with:
            """
            Feature: Verify response with matcher functions
                Scenario: Use custom matcher functions
                    Given the request body is:
                        '''
                        {
                            "emptyList": [],
                            "list": [1, 2, 3],
                            "types": {
                                "string": "some string",
                                "integer": 123,
                                "double": 1.23,
                                "array": [1, 2, 3],
                                "boolean": true,
                                "null": null,
                                "scalar": "some string"
                            }
                        }
                        '''
                    When I request "/echo?json" using HTTP POST
                    Then the response body contains JSON:
                        '''
                        {
                            "emptyList": "@arrayLength(0)",
                            "list": "@arrayLength(3)"
                        }
                        '''
                    Then the response body contains JSON:
                        '''
                        {
                            "emptyList": "@arrayMinLength(0)",
                            "list": "@arrayMinLength(2)"
                        }
                        '''
                    Then the response body contains JSON:
                        '''
                        {
                            "emptyList": "@arrayMaxLength(0)",
                            "list": "@arrayMaxLength(4)"
                        }
                        '''
                    Then the response body contains JSON:
                        '''
                        {
                            "types": {
                                "string": "@variableType(string)",
                                "integer": "@variableType(integer)",
                                "double": "@variableType(double)",
                                "array": "@variableType(array)",
                                "boolean": "@variableType(boolean)",
                                "null": "@variableType(null)",
                                "scalar": "@variableType(scalar)"
                            }
                        }
                        '''
                    Then the response body contains JSON:
                        '''
                        {
                            "types": {
                                "string": "@regExp(/SOME STRING/i)",
                                "integer": "@regExp(/\\d\\d\\d/)",
                                "double": "@regExp(/[\\d\\.]+/)"
                            }
                        }
                        '''
            """
        When I run "behat features/custom-matcher-functions.feature"
        Then it should pass with:
            """
            .......

            1 scenario (1 passed)
            7 steps (7 passed)
            """
