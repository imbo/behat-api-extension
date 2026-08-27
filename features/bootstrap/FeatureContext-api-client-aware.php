<?php declare(strict_types=1);

use Assert\Assertion;
use Behat\Step\Then;
use Imbo\BehatApiExtension\Context\ApiClientAwareContext;

class FeatureContext implements ApiClientAwareContext
{
    private bool $set = false;

    public function initializeClient(array $config): static
    {
        $this->set = true;

        return $this;
    }

    #[Then('the client should be set')]
    public function theClientShouldBeSet(): void
    {
        Assertion::true($this->set);
    }
}
