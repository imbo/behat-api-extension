<?php
namespace Imbo\BehatApiExtension\Context;

use Imbo\BehatApiExtension\ArrayContainsComparator;
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
use RuntimeException;
use stdClass;

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
    protected $client;

    /**
     * Request instance
     *
     * The request instance will be created once the client is ready to send it.
     *
     * @var RequestInterface
     */
    protected $request;

    /**
     * Request options
     *
     * Options to send with the request.
     *
     * @var array
     */
    protected $requestOptions = [];

    /**
     * Response instance
     *
     * The response object will be set once the request has been made.
     *
     * @var ResponseInterface
     */
    protected $response;

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
            'contents' => fopen($path, 'r'),
            'filename' => basename($path),
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
     * Set form parameters
     *
     * @param TableNode $table Table with name / value pairs
     * @Given the following form parameters are set:
     */
    public function givenTheFollowingFormParametersAreSet(TableNode $table) {
        if (!isset($this->requestOptions['form_params'])) {
            $this->requestOptions['form_params'] = [];
        }

        foreach ($table as $row) {
            $name = $row['name'];
            $value = $row['value'];

            if (isset($this->requestOptions['form_params'][$name]) && !is_array($this->requestOptions['form_params'][$name])) {
                $this->requestOptions['form_params'][$name] = [$this->requestOptions['form_params'][$name]];
            }

            if (isset($this->requestOptions['form_params'][$name])) {
                $this->requestOptions['form_params'][$name][] = $value;
            } else {
                $this->requestOptions['form_params'][$name] = $value;
            }
        }
    }

    /**
     * Set the request body to a string
     *
     * @param resource|string $string The content to set as the request body
     * @Given the request body is :string
     */
    public function givenTheRequestBodyIs($string) {
        $this->setRequestBody($string);
    }

    /**
     * Set the request body to a read-only resource pointing to a file
     *
     * This step will open a read-only resource to $path and attach it to the request body. If the
     * file does not exist or is not readable the method will end up throwing an exception. The
     * method will also set the Content-Type request header. mime_content_type() is used to get the
     * mime type of the file.
     *
     * @param string $path Path to a file
     * @throws InvalidArgumentException
     * @Given the request body contains :path
     */
    public function givenTheRequestBodyContains($path) {
        if (!file_exists($path)) {
            throw new InvalidArgumentException(sprintf('File does not exist: "%s"', $path));
        }

        if (!is_readable($path)) {
            throw new InvalidArgumentException(sprintf('File is not readable: "%s"', $path));
        }

        // Set the Content-Type request header and the request body
        $this->setRequestHeader('Content-Type', mime_content_type($path))
             ->setRequestBody(fopen($path, 'r'));
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
     * @When I send :filePath to :path using HTTP :method
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
     * Assert HTTP response reason phrase
     *
     * @param string $phrase Expected HTTP response reason phrase
     * @Then the response reason phrase is :phrase
     */
    public function thenTheResponseReasonPhraseIs($phrase) {
        Assertion::same($phrase, $actual = $this->response->getReasonPhrase(), sprintf(
            'Invalid HTTP response reason phrase, expected "%s", got "%s"',
            $phrase,
            $actual
        ));
    }

    /**
     * Assert HTTP response status line
     *
     * @param string $line Expected HTTP response status line
     * @throws InvalidArgumentException
     * @Then the response status line is :line
     */
    public function thenTheResponseStatusLineIs($line) {
        try {
            $parts = explode(' ', $line, 2);

            if (count($parts) !== 2) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid status line: "%s". Must consist of a status code and a test, for instance "200 OK"',
                    $line
                ));
            }

            $this->thenTheResponseCodeIs((int) $parts[0]);
            $this->thenTheResponseReasonPhraseIs($parts[1]);
        } catch (Assert\InvalidArgumentException $e) {
            throw new InvalidArgumentException(sprintf(
                'Response status line did not match. Expected "%s", got "%d %s"',
                $line,
                $this->response->getStatusCode(),
                $this->response->getReasonPhrase()
            ));
        }
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
     * Assert that a response header exists
     *
     * @param string $header Then name of the header
     * @Then the :header response header exists
     */
    public function thenTheResponseHeaderExists($header) {
        $this->requireResponse();

        Assertion::true(
            $this->response->hasHeader($header),
            sprintf('The "%s" response header does not exist', $header)
        );
    }

    /**
     * Assert that a response header does not exist
     *
     * @param string $header Then name of the header
     * @Then the :header response header does not exist
     */
    public function thenTheResponseHeaderDoesNotExist($header) {
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
     * @Then the response body is an empty array
     * @Then the response body is an array of length :length
     */
    public function thenTheResponseBodyIsAnArrayOfLength($length = 0) {
        $this->requireResponse();

        $body = $this->getResponseBody();

        Assertion::count(
            $body,
            (int) $length,
            sprintf(
                'Wrong length for the array in the response body. Expected %d, got %d.',
                $length,
                count($body)
            )
        );
    }

    /**
     * Assert that the response body contains an array with a length of at least a given length
     *
     * @param int $length The length to use in the assertion
     * @Then the response body is an array with a length of at least :length
     */
    public function thenTheResponseBodyIsAnArrayWithALengthOfAtLeast($length) {
        $this->requireResponse();

        $body = $this->getResponseBody();

        $actualLength = count($body);

        Assertion::min(
            $actualLength,
            $length,
            sprintf('Array length should be at least %d, but length was %d', $length, $actualLength)
        );
    }

    /**
     * Assert that the response body contains an array with a length of at most a given length
     *
     * @param int $length The length to use in the assertion
     * @Then the response body is an array with a length of at most :length
     */
    public function thenTheResponseBodyIsAnArrayWithALengthOfAtMost($length) {
        $this->requireResponse();

        $body = $this->getResponseBody();

        $actualLength = count($body);

        Assertion::max(
            $actualLength,
            $length,
            sprintf('Array length should be at most %d, but length was %d', $length, $actualLength)
        );
    }


    /**
     * Assert that the response body matches some content
     *
     * @param string $content The content to match the response body against
     * @Then the response body is:
     */
    public function thenTheResponseBodyIs(PyStringNode $content) {
        $this->requireResponse();

        Assertion::same((string) $this->response->getBody(), (string) $content);
    }

    /**
     * Assert that the response body matches some content using a regular expression
     *
     * @param PyStringNode $pattern The regular expression pattern to use for the match
     * @Then the response body matches:
     */
    public function thenTheResponseBodyMatches(PyStringNode $pattern) {
        $this->requireResponse();

        Assertion::regex((string) $this->response->getBody(), (string) $pattern);
    }

    /**
     * Assert that the response body contains all keys / values in the parameter
     *
     * @param PyStringNode $body
     * @Then the response body contains:
     */
    public function thenTheResponseBodyContains(PyStringNode $contains) {
        $this->requireResponse();

        $body = $this->getResponseBody(false);
        $contains = json_decode((string) $contains);

        Assertion::isInstanceOf(
            $contains,
            'stdClass',
            'The supplied parameter is not a valid JSON object.'
        );

        // Convert both objects to arrays
        $body = json_decode(json_encode($body), true);
        $contains = json_decode(json_encode($contains), true);

        $comparator = new ArrayContainsComparator();

        // Compare the arrays. On error this will throw an exception
        Assertion::true($comparator->compare($body, $contains));
    }

    /**
     * Send the current request and set the response instance
     *
     * @throws RequestException
     */
    private function sendRequest() {
        if (!empty($this->requestOptions['form_params'])) {
            $this->setRequestMethod('POST');
        }

        if (!empty($this->requestOptions['multipart']) && !empty($this->requestOptions['form_params'])) {
            foreach ($this->requestOptions['form_params'] as $name => $contents) {
                if (is_array($contents)) {
                    $name .= '[]';

                    foreach ($contents as $content) {
                        $this->requestOptions['multipart'][] = [
                            'name' => $name,
                            'contents' => $content,
                        ];
                    }
                } else {
                    $this->requestOptions['multipart'][] = [
                        'name' => $name,
                        'contents' => $contents
                    ];
                }
            }

            // Remove form_params from the options, otherwise Guzzle will throw an exception
            unset($this->requestOptions['form_params']);
        }

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
        $parts = parse_url($path);
        $uri = $this->request->getUri();
        $uri = $uri->withPath($parts['path']);

        if (isset($parts['query'])) {
            $uri = $uri->withQuery($parts['query']);
        }

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
     * @throws InvalidArgumentException
     * @return self
     */
    private function setRequestBody($body) {
        if (!empty($this->requestOptions['multipart']) || !empty($this->requestOptions['form_params'])) {
            throw new InvalidArgumentException(
                'It\'s not allowed to set a request body when using multipart/form-data or form parameters.'
            );
        }

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
     * Get the JSON-encoded array or stdClass from the response body
     *
     * @param boolean $requireArray Whether or not the response body should be an array or not
     * @throws InvalidArgumentException
     * @return array|stdClass
     */
    private function getResponseBody($requireArray = true) {
        $body = json_decode((string) $this->response->getBody());

        if ($requireArray && !is_array($body)) {
            throw new InvalidArgumentException('The response body does not contain a valid JSON array.');
        } else if (!is_array($body) && !($body instanceof stdClass)) {
            throw new InvalidArgumentException('The response body does not contain a valid JSON array / object.');
        }

        return $body;
    }
}
