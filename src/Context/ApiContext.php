<?php
namespace Imbo\BehatApiExtension\Context;

use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7;
use Assert;
use Assert\Assertion;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use InvalidArgumentException;
use LengthException;
use LogicException;
use OutOfRangeException;
use RuntimeException;
use UnexpectedValueException;
use Closure;

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
     * Request instance
     *
     * The request instance will be created once the client is ready to send it.
     *
     * @var RequestInterface
     */
    private $request;

    /**
     * Request options
     *
     * Options to send with the request.
     *
     * @var array
     */
    private $requestOptions = [];

    /**
     * Response instance
     *
     * The response object will be set once the request has been made.
     *
     * @var ResponseInterface
     */
    private $response;

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function setClient(ClientInterface $client) {
        $this->client = $client;
        $this->request = new Request('GET', $client->getConfig('base_uri'));
    }

    /**
     * Attach a file to the request
     *
     * @param string $path Path to the image to add to the request
     * @param string $filename Multipart entry name
     * @Given I attach :path to the request as :partName
     */
    public function givenIAttachAFileToTheRequest($path, $partName) {
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
    public function givenIAuthenticateAs($username, $password) {
        $this->addRequestHeader(
            'Authorization',
            sprintf('Basic %s', base64_encode($username . ':' . $password))
        );
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
    public function givenTheRequestHeaderIs($header, $value) {
        $this->addRequestHeader($header, $value);
    }

    /**
     * Request a path using GET or another HTTP method
     *
     * @param string $path The path to request
     * @param string $method The HTTP method to use
     * @When I request :path
     * @When I request :path using HTTP :method
     */
    public function whenIRequestPath($path, $method = 'GET') {
        $this->setRequestPath($path)
             ->setRequestMethod($method)
             ->sendRequest();
    }

    /**
     * Request a URL using a specific method
     *
     * @param string $path The path to request
     * @param string $method The HTTP Method to use
     * @param PyStringNode $body The body to attach to request
     * @When I request :path using HTTP :method with body:
     */
    public function whenIRequestPathWithBody($path, $method, PyStringNode $body) {
        $this->setRequestMethod($method)
             ->setRequestPath($path)
             ->setRequestBody($body)
             ->sendRequest();
    }

    /**
     * Request a URL with a JSON request body using a specific method
     *
     * @param string $path The path to request
     * @param string $method The HTTP Method to use
     * @param PyStringNode $body The body to attach to request
     * @When I request :path using HTTP :method with JSON body:
     */
    public function whenIRequestPathWithJsonBody($path, $method, PyStringNode $body) {
        Assertion::isJsonString((string) $body);

        $this->setRequestHeader('Content-Type', 'application/json')
             ->whenIRequestPathWithBody($path, $method, $body);
    }

    /**
     * Send a file to a path using a given HTTP method
     *
     * @param string $filePath The path of the file to send
     * @param string $path The path to request
     * @param string $method HTTP method
     * @param string $mimeType Optional mime type of the file to send
     * @throws InvalidArgumentException
     * @When I send :filePath :path using HTTP :method
     * @When I send :filePath as :mimeType to :path using HTTP :method
     */
    public function whenISendFile($filePath, $path, $method, $mimeType = null) {
        if (!file_exists($filePath)) {
            throw new InvalidArgumentException(sprintf('File does not exist: %s', $filePath));
        }

        if ($mimeType === null) {
            $mimeType = mime_content_type($filePath);
        }

        $this->setRequestHeader('Content-Type', $mimeType)
             ->whenIRequestPathWithBody($path, $method, new PyStringNode([file_get_contents($filePath)], 1));
    }

    /**
     * Assert the HTTP response code
     *
     * @param int $code The HTTP response code
     * @Then the response code is :code
     */
    public function thenTheResponseCodeIs($code) {
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
    public function thenTheResponseCodeIsNot($code) {
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
    public function thenTheResponseIs($group) {
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
    public function thenTheResponseIsNot($group) {
        try {
            $this->thenTheResponseIs($group);

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
    public function thenTheResponseBodyIs($content) {
        $this->requireResponse();

        Assertion::same((string) $this->response->getBody(), $content);
    }

    /**
     * Assert that the response body matches some content using a regular expression
     *
     * @param string $pattern The regular expression pattern to use for the match
     * @Then the response body matches :pattern
     */
    public function thenTheResponseBodyMatches($pattern) {
        $this->requireResponse();

        Assertion::regex((string) $this->response->getBody(), $pattern);
    }

    /**
     * Assert that a response header is present
     *
     * @param string $header Then name of the header
     * @Then the :header response header is present
     */
    public function thenTheResponseHeaderIsPresent($header) {
        $this->requireResponse();

        Assertion::true(
            $this->response->hasHeader($header),
            sprintf('The "%s" response header does not exist', $header)
        );
    }

    /**
     * Assert that a response header is not present
     *
     * @param string $header Then name of the header
     * @Then the :header response header is not present
     */
    public function thenTheResponseHeaderIsNotPresent($header) {
        $this->requireResponse();

        Assertion::false(
            $this->response->hasHeader($header),
            sprintf('The "%s" response header should not exist', $header)
        );
    }

    /**
     * Compare a response header value against a string
     *
     * @param string $header The name of the header
     * @param string $value The value to compare with
     * @Then the :header response header is :value
     */
    public function thenTheResponseHeaderIs($header, $value) {
        $this->requireResponse();
        $actual = $this->response->getHeaderLine($header);

        Assertion::same(
            $actual,
            $value,
            sprintf(
                'Response header (%s) mismatch. Expected "%s", got "%s".',
                $header,
                $value,
                $actual
            )
        );
    }

    /**
     * Match a response header value against a regular expression pattern
     *
     * @param string $header The name of the header
     * @param string $pattern The regular expression pattern
     * @Then the :header response header matches :pattern
     */
    public function thenTheResponseHeaderMatches($header, $pattern) {
        $this->requireResponse();
        $actual = $this->response->getHeaderLine($header);

        Assertion::regex(
            $actual,
            $pattern,
            sprintf(
                'Response header (%s) mismatch. "%s" does not match "%s".',
                $header,
                $actual,
                $pattern
            )
        );
    }

    /**
     * Assert that the respones body contains an array with a specific length
     *
     * @param int $length The length of the array
     * @Then the response body is an array of length :length
     * @Then the response body is an empty array
     */
    public function thenTheResponseBodyIsAnArrayOfLength($length = 0) {
        $this->requireResponse();

        $body = $this->getResponseBodyArray();

        Assertion::count(
            $body,
            $length,
            sprintf(
                'Wrong length for the array in the response body. Expected %d, got %d.',
                $length,
                count($body)
            )
        );
    }

    /**
     * Assert that the response body contains an array with a length of at least / most a given
     * length
     *
     * @param string $mode Either "least" or "most" that decides which assertion to use
     * @param int $length The length to use in the assertion
     * @Then /^the response body is an array with a length of at (least|most) (\d+)$/
     */
    public function thenTheResponseBodyIsAnArrayWithALengthOfAtLeastOrMost($mode, $length) {
        $this->requireResponse();

        $body = $this->getResponseBodyArray();

        $actualLength = count($body);

        $callback = $mode === 'least' ? 'min' : 'max';
        $message = $mode === 'least' ?
            'Array length should be at least %d, but length was %d' :
            'Array length should be at most %d, but length was %d';

        Assertion::$callback(
            $actualLength,
            $length,
            sprintf($message, $length, $actualLength)
        );
    }

    /**
     * Assert that the response body contains all keys / values in the parameter
     *
     * @param PyStringNode $body
     * @Then the response body contains:
     */
    public function thenTheResponseBodyContains(PyStringNode $contains) {
        $this->requireResponse();

        $body = $this->getResponseBodyObject();
        $contains = json_decode((string) $contains);

        Assertion::isInstanceOf(
            $contains,
            'stdClass',
            'The supplied parameter is not a valid JSON object.'
        );

        // Convert both objects to arrays
        $body = json_decode(json_encode($body), true);
        $contains = json_decode(json_encode($contains), true);

        // Parse the contains array to convert some specific values to callbacks
        $contains = $this->parseBodyContainsJson($contains);

        $this->arrayContains($body, $contains);
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
     * Send the current request and set the response instance
     *
     * @throws RequestException
     */
    private function sendRequest() {
        try {
            $this->response = $this->client->send(
                $this->request,
                $this->requestOptions
            );
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

    /**
     * Update the path of the request
     *
     * @param string $path The path to request
     * @return self
     */
    private function setRequestPath($path) {
        $uri = $this->request->getUri()->withPath($path);
        $this->request = $this->request->withUri($uri);

        return $this;
    }

    /**
     * Update the HTTP method of the request
     *
     * @param string $method The HTTP method
     * @return self
     */
    private function setRequestMethod($method) {
        $this->request = $this->request->withMethod($method);

        return $this;
    }

    /**
     * Set the request body
     *
     * @param string $body The body to set
     * @return self
     */
    private function setRequestBody($body) {
        $this->request = $this->request->withBody(Psr7\stream_for($body));

        return $this;
    }

    /**
     * Add a request header
     *
     * @param string $header Name of the header
     * @param mixed $value The value of the header
     * @return self
     */
    private function addRequestHeader($header, $value) {
        $this->request = $this->request->withAddedHeader($header, $value);

        return $this;
    }

    /**
     * Set a request header, possibly overwriting an existing one
     *
     * @param string $header Name of the header
     * @param mixed $value The value of the header
     * @return self
     */
    private function setRequestHeader($header, $value) {
        $this->request = $this->request->withHeader($header, $value);

        return $this;
    }

    /**
     * Get the JSON-encoded array from the response body
     *
     * @return array
     */
    private function getResponseBodyArray() {
        $body = json_decode((string) $this->response->getBody());

        Assertion::isArray($body, 'The response body does not contain a valid JSON array.');

        return $body;
    }

    /**
     * Get the JSON-encoded object from the response body
     *
     * @return stdClass
     */
    private function getResponseBodyObject() {
        $body = json_decode((string) $this->response->getBody());

        Assertion::isInstanceOf(
            $body,
            'stdClass',
            'The response body does not contain a valid JSON object.'
        );

        return $body;
    }

    /**
     * Recursively look over an array and make sure all the items in $needle exists
     *
     * @param array $haystack
     * @param array $needle
     * @throws Exception
     */
    private function arrayContains(array $haystack, array $needle, $path = null) {
        foreach ($needle as $key => $value) {
            // Path used in the error messages
            $keyPath = ltrim($path . '.' . $key, '.');

            if (!array_key_exists($key, $haystack)) {
                throw new OutOfRangeException(sprintf(
                    'Key is missing from the haystack: %s',
                    $keyPath
                ));
            }

            // Match types
            $haystackValueType = gettype($haystack[$key]);
            $needleValueType   = gettype($value);
            $valueIsCallback   = $value instanceof Closure;

            // If the needle is a closure we can disregard the fact that they are different, as this
            // will be handled properly below
            if (!$valueIsCallback && $haystackValueType !== $needleValueType) {
                throw new UnexpectedValueException(sprintf(
                    'Type mismatch for key: %s (haystack type: %s, needle type: %s)',
                    $keyPath,
                    $haystackValueType,
                    $needleValueType
                ));
            }

            if (is_scalar($value)) {
                if ($haystack[$key] !== $value) {
                    throw new InvalidArgumentException(sprintf('Value mismatch for key: %s', $keyPath));
                } else {
                    continue;
                }
            } else if ($valueIsCallback) {
                $value($haystack[$key], $keyPath); // Throws exception on error
                continue;
            }

            if (is_array($value)) {
                if (key($value) === 0) {
                    // Regular array, simply loop over the values and see if they are in the
                    // haystack
                    foreach ($value as $v) {
                        if (!is_scalar($v)) {
                            throw new InvalidArgumentException(sprintf('Array for key %s must only contain scalars.', $keyPath));
                        }

                        if (!in_array($v, $haystack[$key])) {
                            throw new InvalidArgumentException(sprintf('Array for key %s is missing a value: %s', $keyPath, $v));
                        }
                    }
                } else {
                    $this->arrayContains($haystack[$key], $value, $keyPath);
                }

                continue;
            }

            // @codeCoverageIgnoreStart
            throw new LogicException(sprintf(
                'Value has not been matched for key: %s. This should never happen, so please file an issue.',
                $keyPath
            ));
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * Recursively walk over the values of the array, replacing some nodes with callbacks.
     *
     * This method will look for some specific patterns in the value part of the array and replace
     * them with callbacks that will be used in the matching process.
     *
     * The specific values that we look for are:
     *
     * <re>/pattern/</re>
     * @length(num)
     * @atLeast(num)
     * @atMost(num)
     *
     * @param array $contains Array that will be used to match a response body
     * @return array Returne the array where specific value parts have been replaced by callbacks.
     */
    private function parseBodyContainsJson(array $contains) {
        array_walk_recursive($contains, function(&$value) {
            if (!is_string($value)) {
                // We only care about string values
                return;
            }

            // Initialize an array for the preg_match calls below
            $match = [];

            if (preg_match('|^<re>(.*?)</re>$|', $value, $match)) {
                $pattern = $match[1];
                $value = function($value, $keyPath) use ($pattern) {
                    if (!is_scalar($value)) {
                        throw new InvalidArgumentException('Regular expressions must be used with scalars.');
                    }

                    if (!preg_match($pattern, (string) $value)) {
                        throw new InvalidArgumentException(sprintf(
                            'Regular expression mismatch for key: %s.',
                            $keyPath
                        ));
                    }
                };
            } else if (preg_match('/^@length\(([\d]+)\)$/', $value, $match)) {
                $expected = (int) $match[1];
                $value = function($value, $keyPath) use ($expected) {
                    if (!is_array($value)) {
                        throw new InvalidArgumentException('@length function must be used with arrays.');
                    }

                    $actual = count($value);

                    if ($actual !== $expected) {
                        throw new InvalidArgumentException(sprintf(
                            'Length of array for key %s is wrong. Expected %d but actual length is %d.',
                            $keyPath,
                            $expected,
                            $actual
                        ));
                    }
                };
            } else if (preg_match('/^@atLeast\(([\d]+)\)$/', $value, $match)) {
                $min = (int) $match[1];
                $value = function($value, $keyPath) use ($min) {
                    if (!is_array($value)) {
                        throw new InvalidArgumentException('@atLeast function must be used with arrays.');
                    }

                    $length = count($value);

                    if ($length < $min) {
                        throw new InvalidArgumentException(sprintf(
                            'Length of array for key %s is wrong. It should be at least %d but is actually %d.',
                            $keyPath,
                            $min,
                            $length
                        ));
                    }
                };
            } else if (preg_match('/^@atMost\(([\d]+)\)$/', $value, $match)) {
                $max = (int) $match[1];
                $value = function($value, $keyPath) use ($max) {
                    if (!is_array($value)) {
                        throw new InvalidArgumentException('@atMost function must be used with arrays.');
                    }

                    $length = count($value);

                    if ($length > $max) {
                        throw new InvalidArgumentException(sprintf(
                            'Length of array for key %s is wrong. It should be at most %d but is actually %d.',
                            $keyPath,
                            $max,
                            $length
                        ));
                    }
                };
            }
        });

        return $contains;
    }
}
