Feature: Configure internal client
    In order to write custom scenario steps for API testing
    As a developer
    I need to configure the internal Guzzle client in the feature context

    Scenario: Context parameters
        Given a file named "features/bootstrap/FeatureContext.php" with:
            """
            <?php
            use GuzzleHttp\HandlerStack;
            use GuzzleHttp\Middleware;
            use Imbo\BehatApiExtension\Context\ApiContext;

            class FeatureContext extends ApiContext
            {
                public function initializeClient(array $config): static
                {
                    $stack = $config['handler'] ?? HandlerStack::create();
                    $stack->push(Middleware::mapRequest(
                        fn ($req) => $req->withAddedHeader('Some-Custom-Header', 'some value')
                    ));
                    $config['handler'] = $stack;
                    return parent::initializeClient($config);
                }
            }
            """
        And a file named "behat.yml" with:
            """
            default:
                formatters:
                    progress: ~
                extensions:
                    Imbo\BehatApiExtension: ~
            """
        And a file named "features/check-request-headers.feature" with:
            """
            Feature: Request data from endpoint
                Scenario: Request data from endpoint
                    When I request "/requestInfo"
                    Then the response body contains JSON:
                    '''
                    {
                        "_SERVER": {
                            "HTTP_SOME_CUSTOM_HEADER": "some value"
                        }
                    }
                    '''

            """
        When I run "behat features/check-request-headers.feature"
        Then it should pass with:
            """
            ..

            1 scenario (1 passed)
            2 steps (2 passed)
            """
