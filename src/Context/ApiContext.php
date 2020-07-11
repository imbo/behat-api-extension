<?php declare(strict_types=1);
namespace Imbo\BehatApiExtension\Context;

use Imbo\BehatApiExtension\ArrayContainsComparator\Matcher\JWT as JwtMatcher;
use Imbo\BehatApiExtension\ArrayContainsComparator;
use Imbo\BehatApiExtension\Exception\AssertionFailedException;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\UriResolver;
use Assert\Assertion;
use Assert\AssertionFailedException as AssertionFailure;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use InvalidArgumentException;
use RuntimeException;
use stdClass;

/**
 * Behat feature context that can be used to simplify testing of JSON-based RESTful HTTP APIs
 */
class ApiContext implements ApiClientAwareContext, ArrayContainsComparatorAwareContext {
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
     * @var array{
     *   auth: string[],
     *   form_params: array<string, string|string[]>,
     *   multipart: array<int, array{name: string, contents: string|resource}>,
     *   query: array<string, mixed>
     * }
     */
    protected $requestOptions = [
        'auth'        => [],
        'form_params' => [],
        'multipart'   => [],
        'query'       => [],
    ];

    /**
     * Response instance
     *
     * The response object will be set once the request has been made.
     *
     * @var ?ResponseInterface
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
    protected $forceHttpMethod = false;

    /**
     * Request / response history for the Guzzle Client
     *
     * @var array{request: RequestInterface, response: ResponseInterface}[]
     */
    protected $clientHistory = [];

    /**
     * Error message used when a required response instance if missing
     *
     * @var string
     */
    protected $missingResponseError = 'The request has not been made yet, so no response object exists.';

    /**
     * Set the client instance
     *
     * @return self
     */
    public function setClient(ClientInterface $client) {
        $this->client  = $client;

        /** @var string|UriInterface */
        $uri = $client->getConfig('base_uri');

        $this->request = new Request('GET', $uri);

        return $this;
    }

    /**
     * Set the array contains comparator instance
     *
     * @return self
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

        $contents = fopen($path, 'r');

        if (false === $contents) {
            throw new RuntimeException(sprintf('Unable to open file: %s', $path));
        }

        return $this->addMultipartPart([
            'name'     => $partName,
            'contents' => $contents,
            'filename' => basename($path),
        ]);
    }

    /**
     * Add an element to the multipart array
     *
     * @param array{name: string, contents: resource|string, filename?: string} $part The part to add
     * @return self
     */
    private function addMultipartPart($part) {
        $this->requestOptions['multipart'][] = $part;

        return $this;
    }

    /**
     * Add multipart form parameters to the request
     *
     * @param TableNode $table Table with name / value pairs
     * @return self
     *
     * @Given the following multipart form parameters are set:
     */
    public function setRequestMultipartFormParams(TableNode $table) {
        foreach ($this->getTableNodeHash($table) as $name => $value) {
            $this->addMultipartPart([
                'name'     => $name,
                'contents' => $value,
            ]);
        }

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
     * Send OAuth request using password grant and set Authorization header.
     *
     * @param string $path         The path to get the token from
     * @param string $username     The username to authenticate with
     * @param string $password     The password to authenticate with
     * @param string $scope        The scope to authenticate in
     * @param string $clientId     The client_id to send
     * @param string $clientSecret Optional client_secret to send
     * @return self
     *
     * @Given I get an OAuth token using password grant from :path with :username and :password in scope :scope using client ID :clientId
     * @Given I get an OAuth token using password grant from :path with :username and :password in scope :scope using client ID :clientId and client secret :clientSecret
     */
    public function oauthWithPasswordGrantInScope($path, $username, $password, $scope, $clientId, $clientSecret = null) {
        $this->requestOptions['form_params'] = array_filter([
            'grant_type'    => 'password',
            'username'      => $username,
            'password'      => $password,
            'scope'         => $scope,
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
        ]);

        $this->addRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        $this->setRequestPath($path);
        $this->setRequestMethod('POST');
        $this->sendRequest();

        if (!$this->response) {
            throw new RuntimeException($this->missingResponseError);
        }

        $this->requestOptions['form_params'] = [];

        if (200 !== $this->response->getStatusCode()) {
            throw new RuntimeException(sprintf(
                'Expected request for access token to pass, got status code %d with the following response: %s',
                $this->response->getStatusCode(),
                (string) $this->response->getBody()
            ));
        }

        /** @var stdClass */
        $body = $this->getResponseBody();

        if (empty($body->access_token)) {
            throw new RuntimeException(sprintf(
                'Missing access_token from response body: %s',
                json_encode($body))
            );
        }

        $this->addRequestHeader(
            'Authorization',
            sprintf('Bearer %s', (string) $body->access_token)
        );

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
        /** @var array<string, array{name: string, value: string}> */
        $rows = $table->getColumnsHash();

        foreach ($rows as $row) {
            $name  = (string) $row['name'];
            $value = (string) $row['value'];

            if (isset($this->requestOptions['form_params'][$name]) && !is_array($this->requestOptions['form_params'][$name])) {
                $this->requestOptions['form_params'][$name] = [
                    $this->requestOptions['form_params'][$name],
                    $value
                ];
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

        if ($string instanceof PyStringNode) {
            $string = (string) $string;
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
     * @throws InvalidArgumentException|RuntimeException
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

        /** @var resource */
        $fp = fopen($path, 'r');

        // Set the Content-Type request header and the request body
        return $this
            ->setRequestHeader('Content-Type', (string) mime_content_type($path))
            ->setRequestBody($fp);
    }

    /**
     * Add a JWT token to the matcher
     *
     * @param string $name String identifying the token
     * @param string $secret The secret used to sign the token
     * @param PyStringNode $payload The payload for the JWT
     * @throws RuntimeException
     * @return self
     *
     * @Given the response body contains a JWT identified by :name, signed with :secret:
     */
    public function addJwtToken($name, $secret, PyStringNode $payload) {
        $jwtMatcher = $this->arrayContainsComparator->getMatcherFunction('jwt');

        if (!($jwtMatcher instanceof JwtMatcher)) {
            throw new RuntimeException(sprintf(
                'Matcher registered for the @jwt() matcher function must be an instance of %s',
                JwtMatcher::class
            ));
        }

        $jwtMatcher->addToken($name, $this->jsonDecode((string) $payload), $secret);

        return $this;
    }

    /**
     * Add a query parameter to the upcoming request
     *
     * @param string $name The name of the parameter
     * @param string|TableNode $value The value to add
     * @return self
     *
     * @Given the query parameter :name is :value
     * @Given the query parameter :name is:
     */
    public function setQueryStringParameter($name, $value) {
        if ($value instanceof TableNode) {
            /** @var string[] */
            $value = array_column($value->getHash(), 'value');
        }

        $this->requestOptions['query'][$name] = $value;

        return $this;
    }

    /**
     * Set multiple query parameters for the upcoming request
     *
     * @param TableNode $params The values to set
     * @return self
     *
     * @Given the following query parameters are set:
     */
    public function setQueryStringParameters(TableNode $params) {
        foreach ($this->getTableNodeHash($params) as $name => $value) {
            $this->requestOptions['query'][$name] = $value;
        }

        return $this;
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

        if (null === $method) {
            $this->setRequestMethod('GET', false);
        } else {
            $this->setRequestMethod($method);
        }

        return $this->sendRequest();
    }

    /**
     * Assert the HTTP response code
     *
     * @param int $code The HTTP response code
     * @throws AssertionFailedException
     *
     * @Then the response code is :code
     */
    public function assertResponseCodeIs($code) : bool {
        if (!$this->response) {
            throw new RuntimeException($this->missingResponseError);
        }

        try {
            Assertion::same(
                $actual = $this->response->getStatusCode(),
                $expected = $this->validateResponseCode((int) $code),
                sprintf('Expected response code %d, got %d.', $expected, $actual)
            );
        } catch (AssertionFailure $e) {
            throw new AssertionFailedException($e->getMessage());
        }

        return true;
    }

    /**
     * Assert the HTTP response code is not a specific code
     *
     * @param int $code The HTTP response code
     * @throws AssertionFailedException
     *
     * @Then the response code is not :code
     */
    public function assertResponseCodeIsNot($code) : bool {
        if (!$this->response) {
            throw new RuntimeException($this->missingResponseError);
        }

        try {
            Assertion::notSame(
                $actual = $this->response->getStatusCode(),
                $this->validateResponseCode((int) $code),
                sprintf('Did not expect response code %d.', $actual)
            );
        } catch (AssertionFailure $e) {
            throw new AssertionFailedException($e->getMessage());
        }

        return true;
    }

    /**
     * Assert that the HTTP response reason phrase equals a given value
     *
     * @param string $phrase Expected HTTP response reason phrase
     * @throws AssertionFailedException
     *
     * @Then the response reason phrase is :phrase
     */
    public function assertResponseReasonPhraseIs($phrase) : bool {
        if (!$this->response) {
            throw new RuntimeException($this->missingResponseError);
        }

        try {
            Assertion::same($phrase, $actual = $this->response->getReasonPhrase(), sprintf(
                'Expected response reason phrase "%s", got "%s".',
                $phrase,
                $actual
            ));
        } catch (AssertionFailure $e) {
            throw new AssertionFailedException($e->getMessage());
        }

        return true;
    }

    /**
     * Assert that the HTTP response reason phrase does not equal a given value
     *
     * @param string $phrase Reason phrase that the HTTP response should not equal
     * @throws AssertionFailedException
     *
     * @Then the response reason phrase is not :phrase
     */
    public function assertResponseReasonPhraseIsNot($phrase) : bool {
        if (!$this->response) {
            throw new RuntimeException($this->missingResponseError);
        }

        try {
            Assertion::notSame($phrase, $this->response->getReasonPhrase(), sprintf(
                'Did not expect response reason phrase "%s".',
                $phrase
            ));
        } catch (AssertionFailure $e) {
            throw new AssertionFailedException($e->getMessage());
        }

        return true;
    }

    /**
     * Assert that the HTTP response reason phrase matches a regular expression
     *
     * @param string $pattern Regular expression pattern
     * @throws AssertionFailedException
     *
     * @Then the response reason phrase matches :expression
     */
    public function assertResponseReasonPhraseMatches($pattern) : bool {
        if (!$this->response) {
            throw new RuntimeException($this->missingResponseError);
        }

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

        return true;
    }

    /**
     * Assert that the HTTP response status line equals a given value
     *
     * @param string $line Expected HTTP response status line
     * @throws AssertionFailedException
     *
     * @Then the response status line is :line
     */
    public function assertResponseStatusLineIs($line) : bool {
        if (!$this->response) {
            throw new RuntimeException($this->missingResponseError);
        }

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

        return true;
    }

    /**
     * Assert that the HTTP response status line does not equal a given value
     *
     * @param string $line Value that the HTTP response status line must not equal
     * @throws AssertionFailedException
     *
     * @Then the response status line is not :line
     */
    public function assertResponseStatusLineIsNot($line) : bool {
        if (!$this->response) {
            throw new RuntimeException($this->missingResponseError);
        }

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

        return true;
    }

    /**
     * Assert that the HTTP response status line matches a regular expression
     *
     * @param string $pattern Regular expression pattern
     * @throws AssertionFailedException
     *
     * @Then the response status line matches :expression
     */
    public function assertResponseStatusLineMatches($pattern) : bool {
        if (!$this->response) {
            throw new RuntimeException($this->missingResponseError);
        }

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

        return true;
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
     *
     * @Then the response is :group
     */
    public function assertResponseIs($group) : bool {
        if (!$this->response) {
            throw new RuntimeException($this->missingResponseError);
        }

        $range = $this->getResponseCodeGroupRange($group);
        $code  = $this->response->getStatusCode();

        try {
            Assertion::range($code, $range['min'], $range['max']);
        } catch (AssertionFailure $e) {
            throw new AssertionFailedException(sprintf(
                'Expected response group "%s", got "%s" (response code: %d).',
                $group,
                $this->getResponseGroup($code),
                $code
            ));
        }

        return true;
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
     *
     * @Then the response is not :group
     */
    public function assertResponseIsNot($group) : bool {
        try {
            $this->assertResponseIs($group);
        } catch (AssertionFailedException $e) {
            // As expected, return
            return true;
        }

        if (!$this->response) {
            throw new RuntimeException($this->missingResponseError);
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
     *
     * @Then the :header response header exists
     */
    public function assertResponseHeaderExists($header) : bool {
        if (!$this->response) {
            throw new RuntimeException($this->missingResponseError);
        }

        try {
            Assertion::true(
                $this->response->hasHeader($header),
                sprintf('The "%s" response header does not exist.', $header)
            );
        } catch (AssertionFailure $e) {
            throw new AssertionFailedException($e->getMessage());
        }

        return true;
    }

    /**
     * Assert that a response header does not exist
     *
     * @param string $header Then name of the header
     * @throws AssertionFailedException
     *
     * @Then the :header response header does not exist
     */
    public function assertResponseHeaderDoesNotExist($header) : bool {
        if (!$this->response) {
            throw new RuntimeException($this->missingResponseError);
        }

        try {
            Assertion::false(
                $this->response->hasHeader($header),
                sprintf('The "%s" response header should not exist.', $header)
            );
        } catch (AssertionFailure $e) {
            throw new AssertionFailedException($e->getMessage());
        }

        return true;
    }

    /**
     * Compare a response header value against a string
     *
     * @param string $header The name of the header
     * @param string $value The value to compare with
     * @throws AssertionFailedException
     *
     * @Then the :header response header is :value
     */
    public function assertResponseHeaderIs($header, $value) : bool {
        if (!$this->response) {
            throw new RuntimeException($this->missingResponseError);
        }

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

        return true;
    }

    /**
     * Assert that a response header is not a value
     *
     * @param string $header The name of the header
     * @param string $value The value to compare with
     * @throws AssertionFailedException
     *
     * @Then the :header response header is not :value
     */
    public function assertResponseHeaderIsNot($header, $value) : bool {
        if (!$this->response) {
            throw new RuntimeException($this->missingResponseError);
        }

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

        return true;
    }

    /**
     * Match a response header value against a regular expression pattern
     *
     * @param string $header The name of the header
     * @param string $pattern The regular expression pattern
     * @throws AssertionFailedException
     *
     * @Then the :header response header matches :pattern
     */
    public function assertResponseHeaderMatches($header, $pattern) : bool {
        if (!$this->response) {
            throw new RuntimeException($this->missingResponseError);
        }

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

        return true;
    }

    /**
     * Assert that the response body contains an empty JSON object
     *
     * @throws AssertionFailedException
     *
     * @Then the response body is an empty JSON object
     */
    public function assertResponseBodyIsAnEmptyJsonObject() : bool {
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

        return true;
    }

    /**
     * Assert that the response body contains an empty JSON array
     *
     * @throws AssertionFailedException
     *
     * @Then the response body is an empty JSON array
     */
    public function assertResponseBodyIsAnEmptyJsonArray() : bool {
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

        return true;
    }

    /**
     * Assert that the response body contains an array with a specific length
     *
     * @param int $length The length of the array
     * @throws AssertionFailedException
     *
     * @Then the response body is a JSON array of length :length
     */
    public function assertResponseBodyJsonArrayLength($length) : bool {
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

        return true;
    }

    /**
     * Assert that the response body contains an array with a length of at least a given length
     *
     * @param int $length The length to use in the assertion
     * @throws AssertionFailedException
     *
     * @Then the response body is a JSON array with a length of at least :length
     */
    public function assertResponseBodyJsonArrayMinLength($length) : bool {
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

        return true;
    }

    /**
     * Assert that the response body contains an array with a length of at most a given length
     *
     * @param int $length The length to use in the assertion
     * @throws AssertionFailedException
     *
     * @Then the response body is a JSON array with a length of at most :length
     */
    public function assertResponseBodyJsonArrayMaxLength($length) : bool {
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

        return true;
    }


    /**
     * Assert that the response body matches some content
     *
     * @param PyStringNode $content The content to match the response body against
     * @throws AssertionFailedException
     *
     * @Then the response body is:
     */
    public function assertResponseBodyIs(PyStringNode $content) : bool {
        if (!$this->response) {
            throw new RuntimeException($this->missingResponseError);
        }

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

        return true;
    }

    /**
     * Assert that the response body does not match some content
     *
     * @param PyStringNode $content The content that the response body should not match
     * @throws AssertionFailedException
     *
     * @Then the response body is not:
     */
    public function assertResponseBodyIsNot(PyStringNode $content) : bool {
        if (!$this->response) {
            throw new RuntimeException($this->missingResponseError);
        }

        $content = (string) $content;

        try {
            Assertion::notSame((string) $this->response->getBody(), $content, sprintf(
                'Did not expect response body to be "%s".',
                $content
            ));
        } catch (AssertionFailure $e) {
            throw new AssertionFailedException($e->getMessage());
        }

        return true;
    }

    /**
     * Assert that the response body matches some content using a regular expression
     *
     * @param PyStringNode $pattern The regular expression pattern to use for the match
     * @throws AssertionFailedException
     *
     * @Then the response body matches:
     */
    public function assertResponseBodyMatches(PyStringNode $pattern) : bool {
        if (!$this->response) {
            throw new RuntimeException($this->missingResponseError);
        }

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

        return true;
    }

    /**
     * Assert that the response body contains all keys / values in the parameter
     *
     * @param PyStringNode $contains
     * @throws AssertionFailedException
     *
     * @Then the response body contains JSON:
     */
    public function assertResponseBodyContainsJson(PyStringNode $contains) : bool {
        $this->requireResponse();

        // Decode the parameter to the step as an array and make sure it's valid JSON
        $contains = $this->jsonDecode((string) $contains);

        // Get the decoded response body and make sure it's decoded to an array
        /** @var array<array-key, mixed> */
        $body = json_decode((string) json_encode($this->getResponseBody()), true);

        try {
            // Compare the arrays, on error this will throw an exception
            Assertion::true($this->arrayContainsComparator->compare($contains, $body));
        } catch (AssertionFailure $e) {
            throw new AssertionFailedException(
                'Comparator did not return in a correct manner. Marking assertion as failed.'
            );
        }

        return true;
    }

    /**
     * Send the current request and set the response instance
     *
     * @throws RequestException
     * @return self
     */
    protected function sendRequest() {
        if (!empty($this->requestOptions['form_params']) && !$this->forceHttpMethod) {
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

            $this->requestOptions['form_params'] = [];
        }

        try {
            $this->response = $this->client->send(
                $this->request,
                array_filter($this->requestOptions)
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
    protected function requireResponse() : void {
        if (!$this->response) {
            throw new RuntimeException('The request has not been made yet, so no response object exists.');
        }
    }

    /**
     * Get the min and max values for a response body group
     *
     * @param string $group The name of the group
     * @throws InvalidArgumentException
     * @return array{min: int, max: int} An array with two keys, min and max, which represents the
     *                                   min and max values for $group
     */
    protected function getResponseCodeGroupRange(string $group) : array {
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
    protected function getResponseGroup(int $code) : string {
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
    protected function validateResponseCode(int $code) : int {
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
    protected function setRequestPath(string $path) {
        /** @var UriInterface */
        $baseUri = $this->client->getConfig('base_uri');
        $uri = UriResolver::resolve($baseUri, Psr7\uri_for($path));
        $this->request = $this->request->withUri($uri);

        return $this;
    }

    /**
     * Update the HTTP method of the request
     *
     * @param string $method The HTTP method
     * @param bool $force Force the HTTP method. If set to false the method set CAN be
     *                       overridden (this occurs for instance when adding form parameters to the
     *                       request, and not specifying HTTP POST for the request)
     * @return self
     */
    protected function setRequestMethod(string $method, bool $force = true) {
        $this->request = $this->request->withMethod($method);
        $this->forceHttpMethod = $force;

        return $this;
    }

    /**
     * Get the JSON-encoded array or stdClass from the response body
     *
     * @throws InvalidArgumentException
     * @return array<mixed>|stdClass
     */
    protected function getResponseBody() {
        if (!$this->response) {
            throw new RuntimeException($this->missingResponseError);
        }

        /** @var mixed */
        $body = json_decode((string) $this->response->getBody());

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('The response body does not contain valid JSON data.');
        } else if (!is_array($body) && !($body instanceof stdClass)) {
            throw new InvalidArgumentException('The response body does not contain a valid JSON array / object.');
        }

        /** @var array<mixed>|stdClass */
        return $body;
    }

    /**
     * Get the response body as an array
     *
     * @throws InvalidArgumentException
     * @return array
     */
    protected function getResponseBodyArray() : array {
        $body = $this->getResponseBody();

        if (!is_array($body)) {
            throw new InvalidArgumentException('The response body does not contain a valid JSON array.');
        }

        return $body;
    }

    /**
     * Convert some variable to a JSON-array
     *
     * @param string $value The value to decode
     * @param string $errorMessage Optional error message
     * @throws InvalidArgumentException
     * @return array<mixed>
     */
    protected function jsonDecode(string $value, string $errorMessage = null) : array {
        /** @var array<mixed> */
        $decoded = json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException(
                $errorMessage ?: 'The supplied parameter is not a valid JSON object.'
            );
        }

        return $decoded;
    }

    /**
     * Get an associative array from the TableNode
     *
     * This method will effectively remove duplicates from TableNode
     *
     * @param TableNode $table
     * @return array<string, string>
     */
    protected function getTableNodeHash(TableNode $table) : array {
        /** @var array<string, string> */
        return array_slice($table->getRowsHash(), 1);
    }
}
