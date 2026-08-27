<?php declare(strict_types=1);

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Imbo\BehatApiExtension\Context\ApiContext;

class FeatureContext extends ApiContext
{
    public function initializeClient(array $config): static
    {
        $stack = $config['handler'] ?? HandlerStack::create();
        $stack->push(Middleware::mapRequest(
            static fn ($req) => $req->withAddedHeader('Some-Custom-Header', 'some value'),
        ));
        $config['handler'] = $stack;

        return parent::initializeClient($config);
    }
}
