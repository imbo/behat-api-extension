<?php
namespace Imbo\BehatApiExtension\Context;

use Behat\Behat\Context\SnippetAcceptingContext,
    Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode,
    GuzzleHttp\ClientInterface,
    GuzzleHttp\Exception\RequestException,
    GuzzleHttp\Psr7\Request,
    Assert\Assertion,
    Psr\Http\Message\RequestInterface,
    Psr\Http\Message\ResponseInterface,
    RuntimeException;

/**
 * API feature context that can be used to ease testing of HTTP APIs
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 */
class ApiContext implements ApiClientAwareContext, SnippetAcceptingContext {
    /**
     * Guzzle client
     *
     * @var ClientInterface
     */
    private $client;

    /**
     * Request headers
     *
     * @var array
     */
    private $headers = [];

    /**
     * Request instance
     *
     * @var RequestInterface
     */
    private $request;

    /**
     * Response instance
     *
     * @var ResponseInterface
     */
    private $response;

    /**
     * {@inheritdoc}
     */
    public function setClient(ClientInterface $client) {
        $this->client = $client;
    }

    /**
     * Request a URL using HTTP GET
     *
     * @param string $url
     * @When /^I request "(.*?)"$/
     */
    public function makeAndSendGetRequest($url) {
        $this->makeAndSendRequest($url, 'GET');
    }

    /**
     * Request a URL using a specific method
     *
     * @param string $url
     * @param string $method
     * @param PyStringNode $body
     * @When /^I request "(.*?)" using HTTP ([A-Z]+)(?: with (JSON )?body:)?$/
     */
    public function makeAndSendRequest($url, $method, $bodyIsJson = false, PyStringNode $body = null) {
        $url = $this->prepareUrl($url);

        if ($bodyIsJson !== false) {
            $this->addHeader('Content-Type', 'application/json');
        }

        $this->request = new Request($method, $url, $this->headers, (string) $body ?: null);
        $this->sendRequest();
    }

    /**
     * Check that the response matches some text
     *
     * @param string $content
     * @Then /^the response body should be "(.*?)"$/
     */
    public function assertResponseBodyMatches($content) {
        Assertion::same((string) $content, (string) $this->response->getBody());
    }

    /**
     * Checks the HTTP response code
     *
     * @param string $code
     * @Then /^the response code should be ([0-9]{3})$/
     */
    public function assertResponseCode($code) {
        Assertion::same((int) $code, $this->response->getStatusCode());
    }

    /**
     * @Given /^I am authenticating as "(.*?)" with password "(.*?)"$/
     */
    public function setAuth($username, $password) {
        $this->addHeader('Authorization', 'Basic ' . base64_encode($username . ':' . $password));
    }

    /**
     * Set a HTTP request header
     *
     * If the header already exists it will be converted to an array
     *
     * @param string $header
     * @param string $value
     * @Given /^the "(.*?)" request header is "(.*?)"$/
     */
    public function addHeader($header, $value) {
        if (isset($this->headers[$header])) {
            if (!is_array($this->headers[$header])) {
                $this->headers[$header] = [$this->headers[$header]];
            }

            $this->headers[$header][] = $value;
        } else {
            $this->headers[$header] = $value;
        }
    }

    /**
     * Make sure that the response body contains a JSON key
     *
     * @param string $key
     * @param array $body
     * @Then /^the response body should contain JSON key "(.*?)"$/
     */
    public function assertBodyHasJsonKey($key, array $body = null) {
        if ($body === null) {
            $body = json_decode($this->response->getBody(), true);

            if ($body === null) {
                throw new RuntimeException(
                    "Can not convert response body to JSON:" . PHP_EOL . (string) $this->response->getBody()
                );
            }
        }

        Assertion::keyExists($body, $key);
    }

    /**
     * Make sure that the response body contains multiple JSON keys
     *
     * @param TableNode $table
     * @throws RuntimeException
     * @Then the response body should contain JSON keys:
     */
    public function assertBodyHasJsonKeys(TableNode $table) {
        $body = json_decode($this->response->getBody(), true);

        if ($body === null) {
            throw new RuntimeException(
                "Can not convert response body to JSON:" . PHP_EOL . (string) $this->response->getBody()
            );
        }

        foreach ($table as $row) {
            $this->assertBodyHasJsonKey($row['key'], $body);
        }
    }

    /**
     * Make sure that all the key/value pairs in the $needle exists in the response body
     *
     * @param PyStringNode $jsonString
     * @throws RuntimeException
     * @Then /^the response body should contain JSON:$/
     */
    public function assertResponseBodyContainsJson(PyStringNode $jsonString) {
        $needle = json_decode($jsonString->getRaw(), true);
        $body = json_decode($this->response->getBody(), true);

        if ($needle === null) {
            throw new RuntimeException(
                "Can not convert needle to JSON:" . PHP_EOL . $jsonString->getRaw()
            );
        } else if ($body === null) {
            throw new RuntimeException(
                "Can not convert response body to JSON:" . PHP_EOL . (string) $this->response->getBody()
            );
        }

        foreach ($needle as $key => $needle) {
            Assertion::keyExists($body, $key);
            Assertion::same($needle, $body[$key]);
        }
    }

    /**
     * Get the request instance
     *
     * @return null|RequestInterface
     */
    protected function getRequest() {
        return $this->request;
    }

    /**
     * Get the response instance
     *
     * @return null|ResponseInterface
     */
    protected function getResponse() {
        return $this->response;
    }

    /**
     * Get the Guzzle client
     *
     * @return ClientInterface
     */
    protected function getClient() {
        return $this->client;
    }

    /**
     * Get the current request headers
     *
     * @return array
     */
    protected function getHeaders() {
        return $this->headers;
    }

    /**
     * Prepare URL for a request
     *
     * @param string $url
     * @return string
     */
    private function prepareUrl($url) {
        return ltrim($url, '/');
    }

    /**
     * Send the current request and set the response instance
     */
    private function sendRequest() {
        try {
            $this->response = $this->client->send($this->request);
        } catch (RequestException $e) {
            $this->response = $e->getResponse();

            if (!$this->response) {
                throw $e;
            }
        }
    }
}
