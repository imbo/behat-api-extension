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
    Psr\Http\Message\ResponseInterface;

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
     * @When /^I request "(.*?)" using HTTP ([A-Z]+)(?: with body:)?$/
     */
    public function makeAndSendRequest($url, $method, PyStringNode $body = null) {
        $url = $this->prepareUrl($url);

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
