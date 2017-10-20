<?php
namespace Imbo\BehatApiExtension\Context;

use Firebase\JWT\JWT;
use Imbo\BehatApiExtension\ArrayContainsComparator;
use Imbo\BehatApiExtension\Exception\AssertionFailedException;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7;
use Assert\Assertion;
use Assert\AssertionFailedException as AssertionFailure;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use InvalidArgumentException;
use RuntimeException;
use stdClass;

/**
 * Behat feature context that can be used to simplify testing of JSON-based RESTful HTTP APIs
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 */
class ApiContext implements ApiClientAwareContext, ArrayContainsComparatorAwareContext, SnippetAcceptingContext {
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
     * Instance of the comparator that handles matching of JSON
     *
     * @var ArrayContainsComparator
     */
    protected $arrayContainsComparator;

    /**
     * Does HTTP method has been manually set
     *
     * @var bool
     */
    protected $httpMethodSpecified = false;

    /**
     * {@inheritdoc}
     */
    public function setClient(ClientInterface $client) {
        $this->client = $client;
        $this->request = new Request('GET', $client->getConfig('base_uri'));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setArrayContainsComparator(ArrayContainsComparator $comparator) {
        $this->arrayContainsComparator = $comparator;

        return $this;
    }

    /**
     * Attach a file to the request
     *
     * @param string $path Path to the image to add to the request
     * @param string $partName Multipart entry name
     * @throws InvalidArgumentException If the $path does not point to a file, an exception is
     *                                  thrown
     * @return self
     *
     * @Given I attach :path to the request as :partName
     */
    public function addMultipartFileToRequest($path, $partName) {
        if (!file_exists($path)) {
            throw new InvalidArgumentException(sprintf('File does not exist: "%s"', $path));
        }

        // Create the multipart entry in the request options if it does not already exist
        if (!isset($this->requestOptions['multipart'])) {
            $this->requestOptions['multipart'] = [];
        }

        // Add an entry to the multipart array
        $this->requestOptions['multipart'][] = [
            'name' => $partName,
            'contents' => fopen($path, 'r'),
            'filename' => basename($path),
        ];

        return $this;
    }

    /**
     * Set basic authentication information for the next request
     *
     * @param string $username The username to authenticate with
     * @param string $password The password to authenticate with
     * @return self
     *
     * @Given I am authenticating as :username with password :password
     */
    public function setBasicAuth($username, $password) {
        $this->requestOptions['auth'] = [$username, $password];

        return $this;
    }

    /**
     * Set a HTTP request header
     *
     * If the header already exists it will be overwritten
     *
     * @param string $header The header name
     * @param string $value The header value
     * @return self
     *
     * @Given the :header request header is :value
     */
    public function setRequestHeader($header, $value) {
        $this->request = $this->request->withHeader($header, $value);

        return $this;
    }

    /**
     * Set/add a HTTP request header
     *
     * If the header already exists it will be converted to an array
     *
     * @param string $header The header name
     * @param string $value The header value
     * @return self
     *
     * @Given the :header request header contains :value
     */
    public function addRequestHeader($header, $value) {
        $this->request = $this->request->withAddedHeader($header, $value);

        return $this;
    }

    /**
     * Set form parameters
     *
     * @param TableNode $table Table with name / value pairs
     * @return self
     *
     * @Given the following form parameters are set:
     */
    public function setRequestFormParams(TableNode $table) {
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

        return $this;
    }

    /**
     * Set the request body to a string
     *
     * @param resource|string|PyStringNode $string The content to set as the request body
     * @throws InvalidArgumentException If form_params or multipart is used in the request options
     *                                  an exception will be thrown as these can't be combined.
     * @return self
     *
     * @Given the request body is:
     */
    public function setRequestBody($string) {
        if (!empty($this->requestOptions['multipart']) || !empty($this->requestOptions['form_params'])) {
            throw new InvalidArgumentException(
                'It\'s not allowed to set a request body when using multipart/form-data or form parameters.'
            );
        }

        $this->request = $this->request->withBody(Psr7\stream_for($string));

        return $this;
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
     * @return self
     *
     * @Given the request body contains :path
     */
    public function setRequestBodyToFileResource($path) {
        if (!file_exists($path)) {
            throw new InvalidArgumentException(sprintf('File does not exist: "%s"', $path));
        }

        if (!is_readable($path)) {
            throw new InvalidArgumentException(sprintf('File is not readable: "%s"', $path));
        }

        // Set the Content-Type request header and the request body
        return $this
            ->setRequestHeader('Content-Type', mime_content_type($path))
            ->setRequestBody(fopen($path, 'r'));
    }

    /**
     * Request a path
     *
     * @param string $path The path to request
     * @param string $method The HTTP method to use
     * @return self
     *
     * @When I request :path
     * @When I request :path using HTTP :method
     */
    public function requestPath($path, $method = null) {
        $this->setRequestPath($path);

        if (null !== $method) {
            $this->setRequestMethod($method);
        }

        return $this->sendRequest();
    }

    /**
     * Assert the HTTP response code
     *
     * @param int $code The HTTP response code
     * @throws AssertionFailedException
     * @return void
     *
     * @Then the response code is :code
     */
    public function assertResponseCodeIs($code) {
        $this->requireResponse();

        try {
            Assertion::same(
                $actual = $this->response->getStatusCode(),
                $expected = $this->validateResponseCode($code),
                sprintf('Expected response code %d, got %d.', $expected, $actual)
            );
        } catch (AssertionFailure $e) {
            throw new AssertionFailedException($e->getMessage());
        }
    }

    /**
     * Assert the HTTP response code is not a specific code
     *
     * @param int $code The HTTP response code
     * @throws AssertionFailedException
     * @return void
     *
     * @Then the response code is not :code
     */
    public function assertResponseCodeIsNot($code) {
        $this->requireResponse();

        try {
            Assertion::notSame(
                $actual = $this->response->getStatusCode(),
                $this->validateResponseCode($code),
                sprintf('Did not expect response code %d.', $actual)
            );
        } catch (AssertionFailure $e) {
            throw new AssertionFailedException($e->getMessage());
        }
    }

    /**
     * Assert that the HTTP response reason phrase equals a given value
     *
     * @param string $phrase Expected HTTP response reason phrase
     * @throws AssertionFailedException
     * @return void
     *
     * @Then the response reason phrase is :phrase
     */
    public function assertResponseReasonPhraseIs($phrase) {
        $this->requireResponse();

        try {
            Assertion::same($phrase, $actual = $this->response->getReasonPhrase(), sprintf(
                'Expected response reason phrase "%s", got "%s".',
                $phrase,
                $actual
            ));
        } catch (AssertionFailure $e) {
            throw new AssertionFailedException($e->getMessage());
        }
    }

    /**
     * Assert that the HTTP response reason phrase does not equal a given value
     *
     * @param string $phrase Reason phrase that the HTTP response should not equal
     * @throws AssertionFailedException
     * @return void
     *
     * @Then the response reason phrase is not :phrase
     */
    public function assertResponseReasonPhraseIsNot($phrase) {
        $this->requireResponse();

        try {
            Assertion::notSame($phrase, $this->response->getReasonPhrase(), sprintf(
                'Did not expect response reason phrase "%s".',
                $phrase
            ));
        } catch (AssertionFailure $e) {
            throw new AssertionFailedException($e->getMessage());
        }
    }

    /**
     * Assert that the HTTP response reason phrase matches a regular expression
     *
     * @param string $pattern Regular expression pattern
     * @throws AssertionFailedException
     * @return void
     *
     * @Then the response reason phrase matches :expression
     */
    public function assertResponseReasonPhraseMatches($pattern) {
        $this->requireResponse();

        try {
            Assertion::regex(
                $actual = $this->response->getReasonPhrase(),
                $pattern,
                sprintf(
                    'Expected the response reason phrase to match the regular expression "%s", got "%s".',
                    $pattern,
                    $actual
                ));
        } catch (AssertionFailure $e) {
            throw new AssertionFailedException($e->getMessage());
        }
    }

    /**
     * Assert that the HTTP response status line equals a given value
     *
     * @param string $line Expected HTTP response status line
     * @throws AssertionFailedException
     * @return void
     *
     * @Then the response status line is :line
     */
    public function assertResponseStatusLineIs($line) {
        $this->requireResponse();

        try {
            $actualStatusLine = sprintf(
                '%d %s',
                $this->response->getStatusCode(),
                $this->response->getReasonPhrase()
            );

            Assertion::same($line, $actualStatusLine, sprintf(
                'Expected response status line "%s", got "%s".',
                $line,
                $actualStatusLine
            ));
        } catch (AssertionFailure $e) {
            throw new AssertionFailedException($e->getMessage());
        }
    }

    /**
     * Assert that the HTTP response status line does not equal a given value
     *
     * @param string $line Value that the HTTP response status line must not equal
     * @throws AssertionFailedException
     * @return void
     *
     * @Then the response status line is not :line
     */
    public function assertResponseStatusLineIsNot($line) {
        $this->requireResponse();

        try {
            $actualStatusLine = sprintf(
                '%d %s',
                $this->response->getStatusCode(),
                $this->response->getReasonPhrase()
            );

            Assertion::notSame($line, $actualStatusLine, sprintf(
                'Did not expect response status line "%s".',
                $line
            ));
        } catch (AssertionFailure $e) {
            throw new AssertionFailedException($e->getMessage());
        }
    }

    /**
     * Assert that the HTTP response status line matches a regular expression
     *
     * @param string $pattern Regular expression pattern
     * @throws AssertionFailedException
     * @return void
     *
     * @Then the response status line matches :expression
     */
    public function assertResponseStatusLineMatches($pattern) {
        $this->requireResponse();

        try {
            $actualStatusLine = sprintf(
                '%d %s',
                $this->response->getStatusCode(),
                $this->response->getReasonPhrase()
            );

            Assertion::regex(
                $actualStatusLine,
                $pattern,
                sprintf(
                    'Expected the response status line to match the regular expression "%s", got "%s".',
                    $pattern,
                    $actualStatusLine
                ));
        } catch (AssertionFailure $e) {
            throw new AssertionFailedException($e->getMessage());
        }
    }

    /**
     * Checks if the HTTP response code is in a group
     *
     * Allowed groups are:
     *
     * - informational
     * - success
     * - redirection
     * - client error
     * - server error
     *
     * @param string $group Name of the group that the response code should be in
     * @throws AssertionFailedException
     * @return void
     *
     * @Then the response is :group
     */
    public function assertResponseIs($group) {
        $this->requireResponse();
        $range = $this->getResponseCodeGroupRange($group);

        try {
            Assertion::range($code = $this->response->getStatusCode(), $range['min'], $range['max']);
        } catch (AssertionFailure $e) {
            throw new AssertionFailedException(sprintf(
                'Expected response group "%s", got "%s" (response code: %d).',
                $group,
                $this->getResponseGroup($code),
                $code
            ));
        }
    }

    /**
     * Checks if the HTTP response code is *not* in a group
     *
     * Allowed groups are:
     *
     * - informational
     * - success
     * - redirection
     * - client error
     * - server error
     *
     * @param string $group Name of the group that the response code is not in
     * @throws AssertionFailedException
     * @return void
     *
     * @Then the response is not :group
     */
    public function assertResponseIsNot($group) {
        try {
            $this->assertResponseIs($group);
        } catch (AssertionFailedException $e) {
            // As expected, return
            return;
        }

        throw new AssertionFailedException(sprintf(
            'Did not expect response to be in the "%s" group (response code: %d).',
            $group,
            $this->response->getStatusCode()
        ));
    }

    /**
     * Assert that a response header exists
     *
     * @param string $header Then name of the header
     * @throws AssertionFailedException
     * @return void
     *
     * @Then the :header response header exists
     */
    public function assertResponseHeaderExists($header) {
        $this->requireResponse();

        try {
            Assertion::true(
                $this->response->hasHeader($header),
                sprintf('The "%s" response header does not exist.', $header)
            );
        } catch (AssertionFailure $e) {
            throw new AssertionFailedException($e->getMessage());
        }
    }

    /**
     * Assert that a response header does not exist
     *
     * @param string $header Then name of the header
     * @throws AssertionFailedException
     * @return void
     *
     * @Then the :header response header does not exist
     */
    public function assertResponseHeaderDoesNotExist($header) {
        $this->requireResponse();

        try {
            Assertion::false(
                $this->response->hasHeader($header),
                sprintf('The "%s" response header should not exist.', $header)
            );
        } catch (AssertionFailure $e) {
            throw new AssertionFailedException($e->getMessage());
        }
    }

    /**
     * Compare a response header value against a string
     *
     * @param string $header The name of the header
     * @param string $value The value to compare with
     * @throws AssertionFailedException
     * @return void
     *
     * @Then the :header response header is :value
     */
    public function assertResponseHeaderIs($header, $value) {
        $this->requireResponse();

        try {
            Assertion::same(
                $actual = $this->response->getHeaderLine($header),
                $value,
                sprintf(
                    'Expected the "%s" response header to be "%s", got "%s".',
                    $header,
                    $value,
                    $actual
                )
            );
        } catch (AssertionFailure $e) {
            throw new AssertionFailedException($e->getMessage());
        }
    }

    /**
     * Assert that a response header is not a value
     *
     * @param string $header The name of the header
     * @param string $value The value to compare with
     * @throws AssertionFailedException
     * @return void
     *
     * @Then the :header response header is not :value
     */
    public function assertResponseHeaderIsNot($header, $value) {
        $this->requireResponse();

        try {
            Assertion::notSame(
                $this->response->getHeaderLine($header),
                $value,
                sprintf(
                    'Did not expect the "%s" response header to be "%s".',
                    $header,
                    $value
                )
            );
        } catch (AssertionFailure $e) {
            throw new AssertionFailedException($e->getMessage());
        }
    }

    /**
     * Match a response header value against a regular expression pattern
     *
     * @param string $header The name of the header
     * @param string $pattern The regular expression pattern
     * @throws AssertionFailedException
     * @return void
     *
     * @Then the :header response header matches :pattern
     */
    public function assertResponseHeaderMatches($header, $pattern) {
        $this->requireResponse();

        try {
            Assertion::regex(
                $actual = $this->response->getHeaderLine($header),
                $pattern,
                sprintf(
                    'Expected the "%s" response header to match the regular expression "%s", got "%s".',
                    $header,
                    $pattern,
                    $actual
                )
            );
        } catch (AssertionFailure $e) {
            throw new AssertionFailedException($e->getMessage());
        }
    }

    /**
     * Assert that the response body contains an empty JSON object
     *
     * @throws AssertionFailedException
     * @return void
     *
     * @Then the response body is an empty JSON object
     */
    public function assertResponseBodyIsAnEmptyJsonObject() {
        $this->requireResponse();
        $body = $this->getResponseBody();

        try {
            Assertion::isInstanceOf($body, 'stdClass', 'Expected response body to be a JSON object.');
            Assertion::same('{}', $encoded = json_encode($body, JSON_PRETTY_PRINT), sprintf(
                'Expected response body to be an empty JSON object, got "%s".',
                $encoded
            ));
        } catch (AssertionFailure $e) {
            throw new AssertionFailedException($e->getMessage());
        }
    }

    /**
     * Assert that the response body contains an empty JSON array
     *
     * @throws AssertionFailedException
     * @return void
     *
     * @Then the response body is an empty JSON array
     */
    public function assertResponseBodyIsAnEmptyJsonArray() {
        $this->requireResponse();

        try {
            Assertion::same(
                [],
                $body = $this->getResponseBodyArray(),
                sprintf('Expected response body to be an empty JSON array, got "%s".', json_encode($body, JSON_PRETTY_PRINT))
            );
        } catch (AssertionFailure $e) {
            throw new AssertionFailedException($e->getMessage());
        }
    }

    /**
     * Assert that the response body contains an array with a specific length
     *
     * @param int $length The length of the array
     * @throws AssertionFailedException
     * @return void
     *
     * @Then the response body is a JSON array of length :length
     */
    public function assertResponseBodyJsonArrayLength($length) {
        $this->requireResponse();
        $length = (int) $length;

        try {
            Assertion::count(
                $body = $this->getResponseBodyArray(),
                $length,
                sprintf(
                    'Expected response body to be a JSON array with %d entr%s, got %d: "%s".',
                    $length,
                    $length === 1 ? 'y' : 'ies',
                    count($body),
                    json_encode($body, JSON_PRETTY_PRINT)
                )
            );
        } catch (AssertionFailure $e) {
            throw new AssertionFailedException($e->getMessage());
        }
    }

    /**
     * Assert that the response body contains an array with a length of at least a given length
     *
     * @param int $length The length to use in the assertion
     * @throws AssertionFailedException
     * @return void
     *
     * @Then the response body is a JSON array with a length of at least :length
     */
    public function assertResponseBodyJsonArrayMinLength($length) {
        $this->requireResponse();

        $length = (int) $length;
        $body = $this->getResponseBodyArray();

        try {
            Assertion::min(
                $bodyLength = count($body),
                $length,
                sprintf(
                    'Expected response body to be a JSON array with at least %d entr%s, got %d: "%s".',
                    $length,
                    (int) $length === 1 ? 'y' : 'ies',
                    $bodyLength,
                    json_encode($body, JSON_PRETTY_PRINT)
                )
            );
        } catch (AssertionFailure $e) {
            throw new AssertionFailedException($e->getMessage());
        }
    }

    /**
     * Assert that the response body contains an array with a length of at most a given length
     *
     * @param int $length The length to use in the assertion
     * @throws AssertionFailedException
     * @return void
     *
     * @Then the response body is a JSON array with a length of at most :length
     */
    public function assertResponseBodyJsonArrayMaxLength($length) {
        $this->requireResponse();

        $length = (int) $length;
        $body = $this->getResponseBodyArray();

        try {
            Assertion::max(
                $bodyLength = count($body),
                $length,
                sprintf(
                    'Expected response body to be a JSON array with at most %d entr%s, got %d: "%s".',
                    $length,
                    (int) $length === 1 ? 'y' : 'ies',
                    $bodyLength,
                    json_encode($body, JSON_PRETTY_PRINT)
                )
            );
        } catch (AssertionFailure $e) {
            throw new AssertionFailedException($e->getMessage());
        }
    }


    /**
     * Assert that the response body matches some content
     *
     * @param PyStringNode $content The content to match the response body against
     * @throws AssertionFailedException
     * @return void
     *
     * @Then the response body is:
     */
    public function assertResponseBodyIs(PyStringNode $content) {
        $this->requireResponse();
        $content = (string) $content;

        try {
            Assertion::same($body = (string) $this->response->getBody(), $content, sprintf(
                'Expected response body "%s", got "%s".',
                $content,
                $body
            ));
        } catch (AssertionFailure $e) {
            throw new AssertionFailedException($e->getMessage());
        }
    }

    /**
     * Assert that the response body does not match some content
     *
     * @param PyStringNode $content The content that the response body should not match
     * @throws AssertionFailedException
     * @return void
     *
     * @Then the response body is not:
     */
    public function assertResponseBodyIsNot(PyStringNode $content) {
        $this->requireResponse();
        $content = (string) $content;

        try {
            Assertion::notSame((string) $this->response->getBody(), $content, sprintf(
                'Did not expect response body to be "%s".',
                $content
            ));
        } catch (AssertionFailure $e) {
            throw new AssertionFailedException($e->getMessage());
        }
    }

    /**
     * Assert that the response body matches some content using a regular expression
     *
     * @param PyStringNode $pattern The regular expression pattern to use for the match
     * @throws AssertionFailedException
     * @return void
     *
     * @Then the response body matches:
     */
    public function assertResponseBodyMatches(PyStringNode $pattern) {
        $this->requireResponse();
        $pattern = (string) $pattern;

        try {
            Assertion::regex($body = (string) $this->response->getBody(), $pattern, sprintf(
                'Expected response body to match regular expression "%s", got "%s".',
                $pattern,
                $body
            ));
        } catch (AssertionFailure $e) {
            throw new AssertionFailedException($e->getMessage());
        }
    }

    /**
     * Assert that the response body contains all keys / values in the parameter
     *
     * @param PyStringNode $contains
     * @throws AssertionFailedException
     * @return void
     *
     * @Then the response body contains JSON:
     */
    public function assertResponseBodyContainsJson(PyStringNode $contains) {
        $this->requireResponse();

        // Decode the parameter to the step as an array and make sure it's valid JSON
        $contains = json_decode((string) $contains, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('The supplied parameter is not a valid JSON object.');
        }

        // Get the decoded response body and make sure it's decoded to an array
        $body = json_decode(json_encode($this->getResponseBody()), true);

        try {
            // Compare the arrays, on error this will throw an exception
            Assertion::true($this->arrayContainsComparator->compare($contains, $body));
        } catch (AssertionFailure $e) {
            throw new AssertionFailedException(
                'Comparator did not return in a correct manner. Marking assertion as failed.'
            );
        }
    }

    /**
     * Assert that the field in the response body contains a valid JWT
     *
     * @param string $field
     * @throws AssertionFailedException
     * @return void
     *
     * @Then the JSON response body field :field contains a JWT with:
     */
    public function assertJsonFieldContainsValidJWT($field, PyStringNode $contains) {
        $this->requireResponse();

        // Decode the parameter to the step as an array and make sure it's valid JSON
        $contains = json_decode((string) $contains, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('The supplied parameter is not a valid JSON object.');
        }

        // Get the decoded response body and make sure it's decoded to an array
        $body = json_decode(json_encode($this->getResponseBody()), true);
        $secret = $contains['secret'];
        $responseJwt = (array) JWT::decode($body[$field], $secret, ['HS256']);

        try {
            Assertion::true($this->arrayContainsComparator->compare($contains['claims'], $responseJwt));
        } catch (AssertionFailure $e) {
            throw new AssertionFailedException($e->getMessage());
        }
    }

    /**
     * Send the current request and set the response instance
     *
     * @throws RequestException
     * @return self
     */
    protected function sendRequest() {
        if (!empty($this->requestOptions['form_params']) && !$this->httpMethodSpecified) {
            $this->setRequestMethod('POST');
        }

        if (!empty($this->requestOptions['multipart']) && !empty($this->requestOptions['form_params'])) {
            // We have both multipart and form_params set in the request options. Take all
            // form_params and add them to the multipart part of the option array as it's not
            // allowed to have both.
            foreach ($this->requestOptions['form_params'] as $name => $contents) {
                if (is_array($contents)) {
                    // The contents is an array, so use array notation for the part name and store
                    // all values under this name
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

        return $this;
    }

    /**
     * Require a response object
     *
     * @throws RuntimeException
     */
    protected function requireResponse() {
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
    protected function getResponseCodeGroupRange($group) {
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
     * Get the "response group" based on a status code
     *
     * @param int $code The respose code
     * @return string
     */
    protected function getResponseGroup($code) {
        $code = (int) $code;

        if ($code >= 500) {
            return 'server error';
        } else if ($code >= 400) {
            return 'client error';
        } else if ($code >= 300) {
            return 'redirection';
        } else if ($code >= 200) {
            return 'success';
        }

        return 'informational';
    }

    /**
     * Validate a response code
     *
     * @param int $code
     * @throws InvalidArgumentException
     * @return int
     */
    protected function validateResponseCode($code) {
        $code = (int) $code;

        try {
            Assertion::range($code, 100, 599, sprintf('Response code must be between 100 and 599, got %d.', $code));
        } catch (AssertionFailure $e) {
            throw new InvalidArgumentException($e->getMessage());
        }

        return $code;
    }

    /**
     * Update the path of the request
     *
     * @param string $path The path to request
     * @return self
     */
    protected function setRequestPath($path) {
        // Resolve the path with the base_uri set in the client
        $uri = Psr7\Uri::resolve($this->client->getConfig('base_uri'), Psr7\uri_for($path));
        $this->request = $this->request->withUri($uri);

        return $this;
    }

    /**
     * Update the HTTP method of the request
     *
     * @param string $method The HTTP method
     * @return self
     */
    protected function setRequestMethod($method) {
        $this->request = $this->request->withMethod($method);
        $this->httpMethodSpecified = true;

        return $this;
    }

    /**
     * Get the JSON-encoded array or stdClass from the response body
     *
     * @throws InvalidArgumentException
     * @return array|stdClass
     */
    protected function getResponseBody() {
        $body = json_decode((string) $this->response->getBody());

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('The response body does not contain valid JSON data.');
        } else if (!is_array($body) && !($body instanceof stdClass)) {
            throw new InvalidArgumentException('The response body does not contain a valid JSON array / object.');
        }

        return $body;
    }

    /**
     * Get the response body as an array
     *
     * @throws InvalidArgumentException
     * @return array
     */
    protected function getResponseBodyArray() {
        if (!is_array($body = $this->getResponseBody())) {
            throw new InvalidArgumentException('The response body does not contain a valid JSON array.');
        }

        return $body;
    }
}
