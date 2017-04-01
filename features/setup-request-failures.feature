Feature: Setup steps can fail
    In order to test the extension
    As a developer
    I want to be able to test all available steps and outcomes

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

    Scenario: Attach multipart file that does not exist
        Given a file named "features/attach-multipart-file-that-does-not-exist.feature" with:
            """
            Feature: Set up the request
                Scenario: Attach file
                    Given I attach "/foo/bar" to the request as "file"
            """
        When I run "behat features/attach-multipart-file-that-does-not-exist.feature"
        Then it should fail with:
            """
            File does not exist: "/foo/bar" (InvalidArgumentException)

            1 scenario (1 failed)
            """

    Scenario: Set request body when multipart exists
        Given a file named "file.txt" with:
            """
            some content
            """
        And a file named "features/request-body-and-multipart.feature" with:
            """
            Feature: Set up the request
                Scenario: Attach file and set request body
                    Given I attach "file.txt" to the request as "file"
                    And the request body is:
                        '''
                        some body
                        '''
            """
        When I run "behat features/request-body-and-multipart.feature"
        Then it should fail with:
            """
            It's not allowed to set a request body when using multipart/form-data or form parameters. (InvalidArgumentException)
            """

    Scenario: Set request body when form_params exists
        Given a file named "features/request-body-and-form-params.feature" with:
            """
            Feature: Set up the request
                Scenario: Set form params and request body
                    Given the following form parameters are set:
                        | name | value |
                        | key  | value |
                    And the request body is:
                        '''
                        some body
                        '''
            """
        When I run "behat features/request-body-and-form-params.feature"
        Then it should fail with:
            """
            It's not allowed to set a request body when using multipart/form-data or form parameters. (InvalidArgumentException)
            """

    Scenario: Attach file to request body that is not readable
        Given a non-readable file named "file.txt" with:
            """
            some content
            """
        And a file named "features/non-readable-file-as-request-body.feature" with:
            """
            Feature: Set up the request
                Scenario: Set request body
                Given the request body contains "file.txt"
            """
        When I run "behat features/non-readable-file-as-request-body.feature"
        Then it should fail with:
            """
            File is not readable: "file.txt" (InvalidArgumentException)
            """

    Scenario: Attach file to request body that does not exist
        Given a file named "features/non-existing-file-as-request-body.feature" with:
            """
            Feature: Set up the request
                Scenario: Set request body
                Given the request body contains "file.txt"
            """
        When I run "behat features/non-existing-file-as-request-body.feature"
        Then it should fail with:
            """
            File does not exist: "file.txt" (InvalidArgumentException)
            """
