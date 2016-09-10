Feature: Client aware context
    In order to write scenario steps for API testing
    As a developer
    I need the Guzzle client in the feature context

    Background:
        Given a file named "features/bootstrap/FeatureContext.php" with:
            """
            <?php
            use Imbo\BehatApiExtension\Context\ApiClientAwareContext,
            use GuzzleHttp\ClientInterface;
            use Assert\Assertion;

            class FeatureContext implements ApiClientAwareContext {
                private $client;

                public function setClient(ClientInterface $client) {
                    $this->client = $client;
                }

                /**
                 * @Then the client should be set
                 */
                public function theClientShouldBeSet() {
                    Assertion::isInstanceOf($this->client, 'GuzzleHttp\Client');
                }
            }
            """

    Scenario: Context parameters
        Given a file named "behat.yml" with:
            """
            default:
                extensions:
                    Imbo\BehatApiExtension: ~
            """
        And a file named "features/client.feature" with:
            """
            Feature: API client
                In order to call the API
                As feature runner
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

    Scenario: Host / port not connectable
        Given a file named "behat.yml" with:
            """
            default:
                extensions:
                    Imbo\BehatApiExtension:
                        base_uri: http://localhost:9999
            """
        And a file named "features/client.feature" with:
            """
            Feature: API client
                In order to call the API
                As feature runner
                I need to be able to access the client

                Scenario: client is set
                    Then the client should be set
            """
        When I run "behat -f progress features/client.feature"
        Then it should fail with:
            """
            [RuntimeException]
              Can not connect to localhost:9999
            """
