<?php
namespace Imbo\BehatApiExtension\Context;

use Behat\Behat\Context\SnippetAcceptingContext,
    Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode,
    GuzzleHttp\ClientInterface,
    GuzzleHttp\Exception\RequestException,
    GuzzleHttp\Psr7\Request,
    Assert,
    Assert\Assertion,
    Psr\Http\Message\RequestInterface,
    Psr\Http\Message\ResponseInterface,
    RuntimeException,
    InvalidArgumentException;

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
     * Request options
     *
     * @var array
     */
    private $requestOptions = [];

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
     * Request a path using GET or another HTTP method
     *
     * @param string $path The path to request
     * @param string $method The HTTP method to use
     * @When I request :path
     * @When I request :path using HTTP :method
     */
    public function requestPath($path, $method = 'GET') {
        $this->makeAndSendRequest($path, $method);
    }

    /**
     * Request a URL with a JSON request body using a specific method
     *
     * @param string $path The path to request
     * @param string $method The HTTP Method to use
     * @param PyStringNode $body The body to attach to request
     * @When I request :path using HTTP :method with JSON body:
     */
    public function makeAndSendRequestWithJsonBody($path, $method, PyStringNode $body = null) {
        Assertion::isJsonString((string) $body);

        $this->addHeader('Content-Type', 'application/json');
        $this->makeAndSendRequest($path, $method, $body);
    }


    /**
     * Request a URL using a specific method
     *
     * @param string $path The path to request
     * @param string $method The HTTP Method to use
     * @param PyStringNode $body The body to attach to request
     * @When I request :path using HTTP :method with body:
     */
    public function makeAndSendRequest($path, $method, PyStringNode $body = null) {
        $this->request = new Request(strtoupper($method), ltrim($path, '/'), $this->headers, (string) $body ?: null);
        $this->sendRequest();
    }

    /**
     * Assert the HTTP response code
     *
     * @param int $code The HTTP response code
     * @throws InvalidArgumentException
     * @Then the response code is :code
     */
    public function assertResponseCode($code) {
        $this->requireResponse();
        $expected = $this->validateResponseCode($code);
        $actual = $this->response->getStatusCode();

        Assertion::same(
            $actual,
            $expected,
            sprintf('Expected response code %d, got %d', $expected, $actual)
        );
    }

    /**
     * Assert the HTTP response code is not a specific code
     *
     * @param int $code The HTTP response code
     * @Then the response code is not :code
     */
    public function assertResponseCodeIsNot($code) {
        $this->requireResponse();
        $expected = $this->validateResponseCode($code);
        $actual = $this->response->getStatusCode();

        Assertion::notSame(
            $actual,
            $expected,
            sprintf('Did not expect response code %d', $actual)
        );
    }

    /**
     * Checks if the HTTP response code is in a group
     *
     * @param string $group Name of the group that the response code should be in
     * @Then the response is :group
     */
    public function assertResponseCodeIsInGroup($group) {
        $this->requireResponse();
        $code = $this->response->getStatusCode();
        $range = $this->getResponseCodeGroupRange($group);
        Assertion::range($code, $range['min'], $range['max']);
    }

    /**
     * Checks if the HTTP response code is *not* in a group
     *
     * @param string $group Name of the group that the response code is not in
     * @Then the response is not :group
     */
    public function assertResponseCodeIsNotInGroup($group) {
        try {
            $this->assertResponseCodeIsInGroup($group);

            throw new InvalidArgumentException(sprintf(
                'Response was not supposed to be %s (actual response code: %d)',
                $group,
                $this->response->getStatusCode()
            ));
        } catch (Assert\InvalidArgumentException $e) {
            // As expected, do nothing
        }
    }

    /**
     * Assert that the response body matches some content
     *
     * @param string $content The content to match the response body against
     * @Then the response body is :content
     */
    public function assertResponseBodyMatches($content) {
        $this->requireResponse();
        Assertion::same((string) $this->response->getBody(), $content);
    }

    /**
     * Attach a file to the request
     *
     * @param string $path Path to the image to add to the request
     * @param string $filename Multipart entry name
     * @Given I attach :path to the request as :partName
     */
    public function addFile($path, $partName) {
        if (!file_exists($path)) {
            throw new InvalidArgumentException(sprintf('File does not exist: %s', $path));
        }

        $part = [
            'name' => $partName,
            'contents' => file_get_contents($path),
        ];

        if (!isset($this->requestOptions['multipart'])) {
            $this->requestOptions['multipart'] = [];
        }

        $this->requestOptions['multipart'][] = $part;
    }

    /**
     * Set basic authentication information for the next request
     *
     * @param string $username The username to authenticate with
     * @param string $password The password to authenticate with
     * @Given I am authenticating as :username with password :password
     */
    public function setBasicAuth($username, $password) {
        $this->addHeader('Authorization', 'Basic ' . base64_encode($username . ':' . $password));
    }

    /**
     * Set a HTTP request header
     *
     * If the header already exists it will be converted to an array
     *
     * @param string $header The header name
     * @param string $value The header value
     * @Given the :header request header is :value
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
     * @codeCoverageIgnore
     */
    protected function getRequest() {
        return $this->request;
    }

    /**
     * Get the response instance
     *
     * @return null|ResponseInterface
     * @codeCoverageIgnore
     */
    protected function getResponse() {
        return $this->response;
    }

    /**
     * Get the Guzzle client
     *
     * @return ClientInterface
     * @codeCoverageIgnore
     */
    protected function getClient() {
        return $this->client;
    }

    /**
     * Get the current request headers
     *
     * @return array
     * @codeCoverageIgnore
     */
    protected function getHeaders() {
        return $this->headers;
    }

    /**
     * Send the current request and set the response instance
     *
     * @throws RequestException
     */
    private function sendRequest() {
        try {
            $this->response = $this->client->send($this->request, $this->requestOptions);
        } catch (RequestException $e) {
            $this->response = $e->getResponse();

            if (!$this->response) {
                throw $e;
            }
        }
    }

    /**
     * Require a response object
     *
     * @throws RuntimeException
     */
    private function requireResponse() {
        if (!$this->response) {
            throw new RuntimeException('The request has not been made yet, so no response object exists.');
        }
    }

    /**
     * Get the min and max values for a response body group
     *
     * @param string $group The name of the group
     * @throws InvalidArgumentException
     * @return array An array with two keys, min and max, which represents the min and max values
     *               for $group
     */
    private function getResponseCodeGroupRange($group) {
        switch ($group) {
            case 'informational':
                $min = 100;
                $max = 199;
                break;
            case 'success':
                $min = 200;
                $max = 299;
                break;
            case 'redirection':
                $min = 300;
                $max = 399;
                break;
            case 'client error':
                $min = 400;
                $max = 499;
                break;
            case 'server error':
                $min = 500;
                $max = 599;
                break;
            default:
                throw new InvalidArgumentException(sprintf('Invalid response code group: %s', $group));
        }

        return [
            'min' => $min,
            'max' => $max,
        ];
    }

    /**
     * Validate a response code
     *
     * @param int $code
     * @throws InvalidArgumentException
     */
    private function validateResponseCode($code) {
        $code = (int) $code;
        Assertion::range($code, 100, 599, sprintf('Response code must be between 100 and 599, got %d.', $code));

        return $code;
    }
}
