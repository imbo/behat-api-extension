<?php
namespace Imbo\BehatApiExtension\Context;

use PHPUnit_Framework_TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Middleware;
use GuzzleHttp\Exception\RequestException;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use RuntimeException;
use OutOfRangeException;
use stdClass;

/**
 * Namespaced version of file_exists that returns true for a fixed filename. All other paths are
 * checked by the global file_exists function.
 *
 * @param string $path
 * @return boolean
 */
function file_exists($path) {
    if ($path === '/non/readable/file') {
        return true;
    }

    return \file_exists($path);
}

/**
 * Namespaced version of is_readable that returns false for a fixed filename. All other paths are
 * checked by the global is_readable function.
 *
 * @param string $path
 * @return boolean
 */
function is_readable($path) {
    if ($path === '/none/readable/file') {
        return false;
    }

    return \is_readable($path);
}

/**
 * @coversDefaultClass Imbo\BehatApiExtension\Context\ApiContext
 * @testdox Main extension context setup / request / assertions
 */
class ApiContextText extends PHPUnit_Framework_TestCase {
    /**
     * @var MockHandler
     */
    private $mockHandler;

    /**
     * @var HandlerStack
     */
    private $handlerStack;

    /**
     * @var array
     */
    private $historyContainer = [];

    /**
     * @var ApiContext
     */
    private $context;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var ArrayContainsComparator
     */
    private $comparator;

    /**
     * @var string
     */
    private $baseUri = 'http://localhost:9876';

    /**
     * Set up the SUT
     */
    public function setUp() {
        $this->historyContainer = [];

        $this->mockHandler = new MockHandler();
        $this->handlerStack = HandlerStack::create($this->mockHandler);
        $this->handlerStack->push(Middleware::history($this->historyContainer));
        $this->client = new Client([
            'handler' => $this->handlerStack,
            'base_uri' => $this->baseUri,
        ]);
        $this->comparator = $this->createMock('Imbo\BehatApiExtension\ArrayContainsComparator');

        $this->context = new ApiContext();
        $this->context->setClient($this->client);
        $this->context->setArrayContainsComparator($this->comparator);
    }

    /**
     * Data provider: Get HTTP methods
     *
     * @return array[]
     */
    public function getHttpMethods() {
        return [
            ['method' => 'GET'],
            ['method' => 'PUT'],
            ['method' => 'POST'],
            ['method' => 'HEAD'],
            ['method' => 'OPTIONS'],
            ['method' => 'DELETE'],
            ['method' => 'PATCH'],
        ];
    }

    /**
     * Data provider: Get files and mime types
     *
     * @return array[]
     */
    public function getFilesAndMimeTypes() {
        return [
            [
                'filePath'         => __FILE__,
                'method'           => 'POST',
                'expectedMimeType' => 'text/x-php',
            ],
            [
                'filePath'         => __FILE__,
                'method'           => 'POST',
                'expectedMimeType' => 'foo/bar',
                'mimeType'         => 'foo/bar',
            ],
        ];
    }

    /**
     * Data provider: Get a a response code, along with an array of other response codes that the
     *                first parameter does not match
     *
     * @return array[]
     */
    public function getResponseCodes() {
        return [
            [
                'code' => 200,
                'others' => [300, 400, 500],
            ],
            [
                'code' => 300,
                'others' => [200, 400, 500],
            ],
            [
                'code' => 400,
                'others' => [200, 300, 500],
            ],
            [
                'code' => 500,
                'others' => [200, 300, 400],
            ],
        ];
    }

    /**
     * Data provider: Get response codes and groups
     *
     * @return array[]
     */
    public function getGroupAndResponseCodes() {
        return [
            [
                'group' => 'informational',
                'codes' => [100, 199],
            ],
            [
                'group' => 'success',
                'codes' => [200, 299],
            ],
            [
                'group' => 'redirection',
                'codes' => [300, 399],
            ],
            [
                'group' => 'client error',
                'codes' => [400, 499],
            ],
            [
                'group' => 'server error',
                'codes' => [500, 599],
            ],
        ];
    }

    /**
     * Data provider: Return invalid HTTP response codes
     *
     * @return array[]
     */
    public function getInvalidHttpResponseCodes() {
        return [
            [99],
            [600],
        ];
    }

    /**
     * Data provider: Get response body arrays, the length to use in the check, and whether or not
     *                the test should fail
     *
     * @return array[]
     */
    public function getResponseBodyArrays() {
        return [
            [
                'body' => [1, 2, 3],
                'lengthToUse' => 3,
                'willFail' => false,
            ],
            [
                'body' => [1, 2, 3],
                'lengthToUse' => 2,
                'willFail' => true,
            ],
            [
                'body' => [],
                'lengthToUse' => 0,
                'willFail' => false,
            ],
            [
                'body' => [],
                'lengthToUse' => 1,
                'willFail' => true,
            ],
        ];
    }

    /**
     * Data provider: Get response body arrays that will be used for testing that the length is at
     *                least a given length
     *
     * @return array[]
     */
    public function getResponseBodyArraysForAtLeast() {
        return [
            [
                'body' => [1, 2, 3],
                'min' => 0,
            ],
            [
                'body' => [1, 2, 3],
                'min' => 1,
            ],
            [
                'body' => [1, 2, 3],
                'min' => 2,
            ],
            [
                'body' => [1, 2, 3],
                'min' => 3,
            ],
        ];
    }

    /**
     * Data provider: Get response body arrays that will be used for testing that the length is at
     *                most a given length
     *
     * @return array[]
     */
    public function getResponseBodyArraysForAtMost() {
        return [
            [
                'body' => [1, 2, 3],
                'max' => 3,
            ],
            [
                'body' => [1, 2, 3],
                'max' => 4,
            ],
            [
                'body' => [],
                'max' => 4,
            ],
            [
                'body' => [],
                'max' => 0,
            ],
        ];
    }

    /**
     * Data provider
     *
     * @return []
     */
    public function getResponseCodesAndReasonPhrases() {
        return [
            200 => [
                'code' => 200,
                'phrase' => 'OK',
            ],
            300 => [
                'code' => 300,
                'phrase' => 'Multiple Choices',
            ],
            400 => [
                'code' => 400,
                'phrase' => 'Bad Request',
            ],
            500 => [
                'code' => 500,
                'phrase' => 'Internal Server Error',
            ],
        ];
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getUris() {
        return [
            // The first six sets are from http://docs.guzzlephp.org/en/latest/quickstart.html (2016-12-12)
            [
                'baseUri' => 'http://foo.com',
                'path' => '/bar',
                'fullUri' => 'http://foo.com/bar',
            ],
            [
                'baseUri' => 'http://foo.com/foo',
                'path' => '/bar',
                'fullUri' => 'http://foo.com/bar',
            ],
            [
                'baseUri' => 'http://foo.com/foo',
                'path' => 'bar',
                'fullUri' => 'http://foo.com/bar',
            ],
            [
                'baseUri' => 'http://foo.com/foo/',
                'path' => 'bar',
                'fullUri' => 'http://foo.com/foo/bar',
            ],
            [
                'baseUri' => 'http://foo.com',
                'path' => 'http://baz.com',
                'fullUri' => 'http://baz.com',
            ],
            [
                'baseUri' => 'http://foo.com/?bar',
                'path' => 'bar',
                'fullUri' => 'http://foo.com/bar',
            ],

            [
                'baseUri' => 'http://foo.com',
                'path' => '/bar?foo=bar',
                'fullUri' => 'http://foo.com/bar?foo=bar',
            ],

            // https://github.com/imbo/behat-api-extension/issues/20
            [
                'baseUri' => 'http://localhost:8080/app_dev.php',
                'path' => '/api/authenticate',
                'fullUri' => 'http://localhost:8080/api/authenticate',
            ],
            [
                'baseUri' => 'http://localhost:8080/app_dev.php/',
                'path' => 'api/authenticate',
                'fullUri' => 'http://localhost:8080/app_dev.php/api/authenticate',
            ],
            [
                'baseUri' => 'http://localhost:8080',
                'path' => '/app_dev.php/api/authenticate',
                'fullUri' => 'http://localhost:8080/app_dev.php/api/authenticate',
            ],
        ];
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getDataForResponseGroupFailures() {
        return [
            [
                'responseCode' => 100,
                'actualGroup' => 'informational',
                'expectedGroup' => 'success',
            ],
            [
                'responseCode' => 200,
                'actualGroup' => 'success',
                'expectedGroup' => 'informational',
            ],
            [
                'responseCode' => 300,
                'actualGroup' => 'redirection',
                'expectedGroup' => 'success',
            ],
            [
                'responseCode' => 400,
                'actualGroup' => 'client error',
                'expectedGroup' => 'success',
            ],
            [
                'responseCode' => 500,
                'actualGroup' => 'server error',
                'expectedGroup' => 'success',
            ],
        ];
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getRequestBodyValues() {
        return [
            [
                'data' => 'some string',
                'expected' => 'some string',
            ],
            [
                'data' => new PyStringNode(['some string'], 1),
                'expected' => 'some string',
            ],
        ];
    }

    /**
     * @covers ::setRequestHeader
     * @group setup
     */
    public function testCanSetRequestHeaders() {
        $this->mockHandler->append(new Response(200));

        $this->assertSame($this->context, $this->context
            ->setRequestHeader('foo', 'foo')
            ->setRequestHeader('bar', 'foo')
            ->setRequestHeader('bar', 'bar')
        );
        $this->context->requestPath('/some/path', 'POST');
        $this->assertSame(1, count($this->historyContainer));

        $request = $this->historyContainer[0]['request'];
        $this->assertSame('foo', $request->getHeaderLine('foo'));
        $this->assertSame('bar', $request->getHeaderLine('bar'));
    }

    /**
     * @covers ::addRequestHeader
     * @group setup
     */
    public function testCanAddRequestHeaders() {
        $this->mockHandler->append(new Response(200));

        $this->assertSame($this->context, $this->context
            ->addRequestHeader('foo', 'foo')
            ->addRequestHeader('bar', 'foo')
            ->addRequestHeader('bar', 'bar')
        );
        $this->context->requestPath('/some/path', 'POST');
        $this->assertSame(1, count($this->historyContainer));

        $request = $this->historyContainer[0]['request'];
        $this->assertSame('foo', $request->getHeaderLine('foo'));
        $this->assertSame('foo, bar', $request->getHeaderLine('bar'));
    }

    /**
     * @covers ::setBasicAuth
     * @group setup
     */
    public function testSupportBasicHttpAuthentication() {
        $this->mockHandler->append(new Response(200));

        $username = 'user';
        $password = 'pass';

        $this->assertSame($this->context, $this->context->setBasicAuth($username, $password));
        $this->context->requestPath('/some/path', 'POST');

        $this->assertSame(1, count($this->historyContainer));

        $request = $this->historyContainer[0]['request'];
        $this->assertSame('Basic dXNlcjpwYXNz', $request->getHeaderLine('authorization'));
    }

    /**
     * @covers ::addMultipartFileToRequest
     * @group setup
     */
    public function testCanAddMultipleMultipartFilesToTheRequest() {
        $this->mockHandler->append(new Response(200));
        $files = [
            'file1' => __FILE__,
            'file2' => __DIR__ . '/../../README.md'
        ];

        foreach ($files as $name => $path) {
            $this->assertSame($this->context, $this->context->addMultipartFileToRequest($path, $name));
        }

        $this->context->requestPath('/some/path', 'POST');

        $this->assertSame(1, count($this->historyContainer));

        $request = $this->historyContainer[0]['request'];
        $boundary = $request->getBody()->getBoundary();

        $this->assertSame(sprintf('multipart/form-data; boundary=%s', $boundary), $request->getHeaderLine('Content-Type'));
        $contents = $request->getBody()->getContents();

        foreach ($files as $path) {
            $this->assertContains(
                file_get_contents($path),
                $contents
            );
        }
    }

    /**
     * @covers ::setRequestFormParams
     * @covers ::sendRequest
     * @group setup
     */
    public function testCanSetFormParametersInTheRequest() {
        $this->mockHandler->append(new Response(200));
        $this->assertSame($this->context, $this->context->setRequestFormParams(new TableNode([
            ['name', 'value'],
            ['foo', 'bar'],
            ['bar', 'foo'],
            ['bar', 'bar'],
        ])));
        $this->context->requestPath('/some/path');

        $this->assertSame(1, count($this->historyContainer));

        $request = $this->historyContainer[0]['request'];

        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('application/x-www-form-urlencoded', $request->getHeaderLine('Content-Type'));
        $this->assertSame(37, (int) $request->getHeaderLine('Content-Length'));
        $this->assertSame('foo=bar&bar%5B0%5D=foo&bar%5B1%5D=bar', (string) $request->getBody());
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getHttpMethodsForFormParametersTest() {
        return [
            ['httpMethod' => 'PUT'],
            ['httpMethod' => 'POST'],
            ['httpMethod' => 'PATCH'],
            ['httpMethod' => 'DELETE'],
        ];
    }

    /**
     * @dataProvider getHttpMethodsForFormParametersTest
     * @covers ::setRequestFormParams
     * @covers ::sendRequest
     * @group setup
     *
     * @param string $httpMethod The HTTP method
     */
    public function testCanSetFormParametersInTheRequestWithCustomMethod($httpMethod) {
        $this->mockHandler->append(new Response(200));
        $this->assertSame($this->context, $this->context->setRequestFormParams(new TableNode([
            ['name', 'value'],
            ['foo', 'bar'],
            ['bar', 'foo'],
            ['bar', 'bar'],
        ])));
        $this->context->requestPath('/some/path', $httpMethod);

        $this->assertSame(1, count($this->historyContainer));

        $request = $this->historyContainer[0]['request'];

        $this->assertSame($httpMethod, $request->getMethod());
        $this->assertSame('application/x-www-form-urlencoded', $request->getHeaderLine('Content-Type'));
        $this->assertSame(37, (int) $request->getHeaderLine('Content-Length'));
        $this->assertSame('foo=bar&bar%5B0%5D=foo&bar%5B1%5D=bar', (string) $request->getBody());
    }

    /**
     * @covers ::sendRequest
     * @group setup
     */
    public function testCanSetFormParametersAndAttachAFileInTheSameMultipartRequest() {
        $this->mockHandler->append(new Response(200));
        $this->context->setRequestFormParams(new TableNode([
            ['name', 'value'],
            ['foo', 'bar'],
            ['bar', 'foo'],
            ['bar', 'bar'],
        ]));
        $this->context->addMultipartFileToRequest(__FILE__, 'file');
        $this->context->requestPath('/some/path');

        $this->assertSame(1, count($this->historyContainer));

        $request = $this->historyContainer[0]['request'];
        $boundary = $request->getBody()->getBoundary();

        $this->assertSame('POST', $request->getMethod());
        $this->assertSame(sprintf('multipart/form-data; boundary=%s', $boundary), $request->getHeaderLine('Content-Type'));

        $contents = $request->getBody()->getContents();

        $this->assertContains('Content-Disposition: form-data; name="file"; filename="ApiContextTest.php"', $contents);
        $this->assertContains(file_get_contents(__FILE__), $contents);

        $foo = <<<FOO
Content-Disposition: form-data; name="foo"
Content-Length: 3

bar
FOO;

        $bar0 = <<<BAR
Content-Disposition: form-data; name="bar[]"
Content-Length: 3

foo
BAR;
        $bar1 = <<<BAR
Content-Disposition: form-data; name="bar[]"
Content-Length: 3

bar
BAR;
        $this->assertContains($foo, $contents);
        $this->assertContains($bar0, $contents);
        $this->assertContains($bar1, $contents);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage File does not exist: "/foo/bar"
     * @covers ::addMultipartFileToRequest
     * @group setup
     */
    public function testThrowsExceptionWhenAddingNonExistingFileAsMultipartPartToTheRequest() {
        $this->context->addMultipartFileToRequest('/foo/bar', 'foo');
    }

    /**
     * @covers ::setRequestBodyToFileResource
     * @covers ::setRequestBody
     * @group setup
     */
    public function testCanSetRequestBodyToAFile() {
        $this->mockHandler->append(new Response());
        $this->assertSame($this->context, $this->context->setRequestBodyToFileResource(__FILE__));
        $this->context->requestPath('/some/path', 'POST');

        $this->assertSame(1, count($this->historyContainer));
        $request = $this->historyContainer[0]['request'];
        $this->assertSame(file_get_contents(__FILE__), (string) $request->getBody());
        $this->assertSame('text/x-php', $request->getHeaderLine('Content-Type'));
    }

    /**
     * @dataProvider getRequestBodyValues
     * @covers ::setRequestBody
     * @group setup
     *
     * @param string|PyStringNode $data
     * @param string $expected
     */
    public function testCanSetRequestBodyToAString($data, $expected) {
        $this->mockHandler->append(new Response());
        $this->context->setRequestBody($data);
        $this->context->requestPath('/some/path', 'POST');

        $this->assertSame(1, count($this->historyContainer));
        $request = $this->historyContainer[0]['request'];
        $this->assertSame($expected, (string) $request->getBody());
    }

    /**
     * @dataProvider getUris
     * @covers ::setClient
     * @covers ::setRequestPath
     * @group setup
     *
     * @param string $baseUri
     * @param string $path
     * @param string $fullUri
     */
    public function testResolvesFilePathsCorrectlyWhenAttachingFilesToTheRequestBody($baseUri, $path, $fullUri) {
        // Set a new client with the given base_uri (and not the one used in setUp())
        $this->assertSame($this->context, $this->context->setClient(new Client([
            'handler' => $this->handlerStack,
            'base_uri' => $baseUri,
        ])));

        $this->mockHandler->append(new Response());
        $this->assertSame($this->context, $this->context->setRequestBodyToFileResource(__FILE__));
        $this->context->requestPath($path);

        $this->assertSame(1, count($this->historyContainer));
        $request = $this->historyContainer[0]['request'];
        $this->assertSame($fullUri, (string) $request->getUri());
    }

    /**
     * @dataProvider getHttpMethods
     * @covers ::requestPath
     * @covers ::setRequestPath
     * @covers ::setRequestMethod
     * @covers ::sendRequest
     * @group request
     *
     * @param string $method
     */
    public function testCanMakeRequestsUsingDifferentHttpMethods($method) {
        $this->mockHandler->append(new Response(200));
        $this->assertSame($this->context, $this->context->requestPath('/some/path', $method));

        $this->assertSame(1, count($this->historyContainer));
        $this->assertSame($method, $this->historyContainer[0]['request']->getMethod());
    }

    /**
     * @covers ::requestPath
     * @covers ::setRequestMethod
     * @covers ::setRequestPath
     * @covers ::setRequestBody
     * @covers ::sendRequest
     * @group request
     */
    public function testCanMakeRequestsWithQueryStringInThePath() {
        $this->mockHandler->append(new Response(200));
        $this->assertSame(
            $this->context,
            $this->context->requestPath('/some/path?foo=bar&bar=foo&a[]=1&a[]=2')
        );

        $this->assertSame(1, count($this->historyContainer));

        $request = $this->historyContainer[0]['request'];

        $this->assertSame('foo=bar&bar=foo&a%5B%5D=1&a%5B%5D=2', $request->getUri()->getQuery());
    }

    /**
     * @dataProvider getResponseCodes
     * @covers ::assertResponseCodeIs
     * @covers ::validateResponseCode
     * @group assertions
     *
     * @param int $code
     */
    public function testCanAssertWhatTheResponseCodeIs($code) {
        $this->mockHandler->append(new Response($code));
        $this->context->requestPath('/some/path');
        $this->context->assertResponseCodeIs($code);
    }

    /**
     * @covers ::assertResponseCodeIsNot
     * @covers ::validateResponseCode
     * @group assertions
     *
     * @param int $code
     * @param int[] $otherCodes
     */
    public function testCanAssertWhatTheResponseCodeIsNot() {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath('/some/path');
        $this->context->assertResponseCodeIsNot(201);
    }

    /**
     * @dataProvider getGroupAndResponseCodes
     * @covers ::assertResponseIs
     * @covers ::requireResponse
     * @covers ::getResponseCodeGroupRange
     * @group assertions
     *
     * @param string $group
     * @param int[] $codes
     */
    public function testCanAssertWhichGroupTheResponseIsIn($group, array $codes) {
        foreach ($codes as $code) {
            $this->mockHandler->append(new Response($code, [], 'response body'));
            $this->context->requestPath('/some/path');
            $this->context->assertResponseIs($group);
        }
    }

    /**
     * @dataProvider getGroupAndResponseCodes
     * @covers ::assertResponseIsNot
     * @covers ::assertResponseIs
     * @group assertions
     *
     * @param string $group
     * @param int[] $codes
     */
    public function testCanAssertWhichGroupTheResponseIsNotIn($group, array $codes) {
        $groups = [
            'informational',
            'success',
            'redirection',
            'client error',
            'server error',
        ];

        foreach ($codes as $code) {
            $this->mockHandler->append(new Response($code));
            $this->context->requestPath('/some/path');

            foreach (array_filter($groups, function($g) use ($group) { return $g !== $group; }) as $g) {
                // Assert that the response is not in any of the other groups
                $this->context->assertResponseIsNot($g);
            }
        }
    }

    /**
     * @dataProvider getResponseCodesAndReasonPhrases
     * @covers ::assertResponseReasonPhraseIs
     * @group assertions
     *
     * @param int $code The HTTP response code
     * @param string $phrase The HTTP response reason phrase
     */
    public function testCanAssertWhatTheResponseReasonPhraseIs($code, $phrase) {
        $this->mockHandler->append(new Response($code, [], null, 1.1, $phrase));
        $this->context->requestPath('/some/path');
        $this->context->assertResponseReasonPhraseIs($phrase);
    }

    /**
     * @covers ::assertResponseReasonPhraseIsNot
     * @group assertions
     */
    public function testCanAssertWhatTheResponseReasonPhraseIsNot() {
        $this->mockHandler->append(new Response());
        $this->context->requestPath('/some/path');
        $this->context->assertResponseReasonPhraseIsNot('Not Modified');
    }

    /**
     * @covers ::assertResponseReasonPhraseMatches
     * @group assertions
     */
    public function testCanAssertThatTheResponseReasonPhraseMatchesAnExpression() {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath('/some/path');
        $this->context->assertResponseReasonPhraseMatches('/OK/');
    }

    /**
     * @dataProvider getResponseCodesAndReasonPhrases
     * @covers ::assertResponseStatusLineIs
     * @group assertions
     *
     * @param int $code The HTTP response code
     * @param string $phrase The HTTP response reason phrase
     */
    public function testCanAssertWhatTheResponseStatusLineIs($code, $phrase) {
        $this->mockHandler->append(new Response($code, [], null, 1.1, $phrase));
        $this->context->requestPath('/some/path');
        $this->context->assertResponseStatusLineIs(sprintf('%d %s', $code, $phrase));
    }

    /**
     * @covers ::assertResponseStatusLineIsNot
     * @group assertions
     */
    public function testCanAssertWhatTheResponseStatusLineIsNot() {
        $this->mockHandler->append(new Response());
        $this->context->requestPath('/some/path');
        $this->context->assertResponseStatusLineIsNot('304 Not Modified');
    }

    /**
     * @covers ::assertResponseStatusLineMatches
     * @group assertions
     */
    public function testCanAssertThatTheResponseStatusLineMatchesAnExpression() {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath('/some/path');
        $this->context->assertResponseStatusLineMatches('/200 OK/');
    }

    /**
     * @covers ::assertResponseHeaderExists
     * @group assertions
     */
    public function testCanAssertThatAResponseHeaderExists() {
        $this->mockHandler->append(new Response(200, ['Content-Type' => 'application/json']));
        $this->context->requestPath('/some/path');
        $this->context->assertResponseHeaderExists('Content-Type');
    }

    /**
     * @covers ::assertResponseHeaderDoesNotExist
     * @group assertions
     */
    public function testCanAssertThatAResponseHeaderDoesNotExist() {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath('/some/path');
        $this->context->assertResponseHeaderDoesNotExist('Content-Type');
    }

    /**
     * @covers ::assertResponseHeaderIs
     * @group assertions
     */
    public function testCanAssertWhatAResponseHeaderIs() {
        $this->mockHandler->append(new Response(200, ['Content-Type' => 'application/json']));
        $this->context->requestPath('/some/path');
        $this->context->assertResponseHeaderIs('Content-Type', 'application/json');
    }

    /**
     * @covers ::assertResponseHeaderIsNot
     * @group assertions
     */
    public function testCanAssertWhatAResponseHeaderIsNot() {
        $this->mockHandler->append(new Response(200, ['Content-Length' => '123']));
        $this->context->requestPath('/some/path');
        $this->context->assertResponseHeaderIsNot('Content-Type', '456');
    }

    /**
     * @covers ::assertResponseHeaderMatches
     * @group assertions
     */
    public function testCanAssertThatAResponseHeaderMatchesAnExpression() {
        $this->mockHandler->append(new Response(200, ['Content-Type' => 'application/json']));
        $this->context->requestPath('/some/path');
        $this->context->assertResponseHeaderMatches('Content-Type', '#^application/(json|xml)$#');
    }

    /**
     * @covers ::assertResponseBodyIs
     * @group assertions
     */
    public function testCanAssertWhatTheResponseBodyIs() {
        $this->mockHandler->append(new Response(200, [], 'response body'));
        $this->context->requestPath('/some/path');
        $this->context->assertResponseBodyIs(new PyStringNode(['response body'], 1));
    }

    /**
     * @covers ::assertResponseBodyIsNot
     * @group assertions
     */
    public function testCanAssertWhatTheResponseBodyIsNot() {
        $this->mockHandler->append(new Response(200, [], 'response body'));
        $this->context->requestPath('/some/path');
        $this->context->assertResponseBodyIsNot(new PyStringNode(['some other response body'], 1));
    }

    /**
     * @covers ::assertResponseBodyMatches
     * @group assertions
     */
    public function testCanAssertThatTheResponseBodyMatchesAnExpression() {
        $this->mockHandler->append(new Response(200, [], '{"foo":"bar"}'));
        $this->context->requestPath('/some/path');
        $this->context->assertResponseBodyMatches(new PyStringNode(['/^{"FOO": ?"BAR"}$/i'], 1));
    }

    /**
     * @covers ::assertResponseBodyIsAnEmptyJsonArray
     * @covers ::getResponseBodyArray
     * @covers ::getResponseBody
     * @group assertions
     */
    public function testCanAssertThatTheResponseIsAnEmptyArray() {
        $this->mockHandler->append(new Response(200, [], json_encode([])));
        $this->context->requestPath('/some/path');
        $this->context->assertResponseBodyIsAnEmptyJsonArray();
    }

    /**
     * @covers ::assertResponseBodyIsAnEmptyJsonObject
     * @group assertions
     */
    public function testCanAssertThatTheResponseIsAnEmptyObject() {
        $this->mockHandler->append(new Response(200, [], json_encode(new stdClass())));
        $this->context->requestPath('/some/path');
        $this->context->assertResponseBodyIsAnEmptyJsonObject();
    }

    /**
     * @dataProvider getResponseBodyArrays
     * @covers ::assertResponseBodyJsonArrayLength
     * @covers ::getResponseBodyArray
     * @group assertions
     *
     * @param array $body
     * @param int $lenthToUse
     * @param boolean $willFail
     */
    public function testCanAssertThatTheArrayInTheResponseBodyHasACertainLength(array $body, $lengthToUse, $willFail) {
        $this->mockHandler->append(new Response(200, [], json_encode($body)));
        $this->context->requestPath('/some/path');

        if ($willFail) {
            $this->expectException('Imbo\BehatApiExtension\Exception\AssertionFailedException');
            $this->expectExceptionMessage(sprintf(
                'Expected response body to be a JSON array with %d entr%s, got %d: "[',
                $lengthToUse,
                (int) $lengthToUse === 1 ? 'y' : 'ies',
                count($body)
            ));
        }

        $this->context->assertResponseBodyJsonArrayLength($lengthToUse);
    }

    /**
     * @dataProvider getResponseBodyArraysForAtLeast
     * @covers ::assertResponseBodyJsonArrayMinLength
     * @covers ::getResponseBody
     * @group assertions
     *
     * @param array $body
     * @param int $min
     */
    public function testCanAssertTheMinLengthOfAnArrayInTheResponseBody(array $body, $min) {
        $this->mockHandler->append(new Response(200, [], json_encode($body)));
        $this->context->requestPath('/some/path');
        $this->context->assertResponseBodyJsonArrayMinLength($min);
    }

    /**
     * @dataProvider getResponseBodyArraysForAtMost
     * @covers ::assertResponseBodyJsonArrayMaxLength
     * @covers ::getResponseBody
     * @group assertions
     *
     * @param array $body
     * @param int $max
     */
    public function testCanAssertTheMaxLengthOfAnArrayInTheResponseBody(array $body, $max) {
        $this->mockHandler->append(new Response(200, [], json_encode($body)));
        $this->context->requestPath('/some/path');
        $this->context->assertResponseBodyJsonArrayMaxLength($max);
    }

    /**
     * @covers ::setArrayContainsComparator
     * @covers ::assertResponseBodyContainsJson
     * @covers ::getResponseBody
     * @group assertions
     */
    public function testCanAssertThatTheResponseBodyContainsJson() {
        $this->mockHandler->append(new Response(200, [], '{"foo":"bar","bar":"foo"}'));
        $this->context->requestPath('/some/path');
        $this->comparator
            ->expects($this->once())
            ->method('compare')
            ->with(['bar' => 'foo', 'foo' => 'bar'], ['foo' => 'bar', 'bar' => 'foo'])
            ->will($this->returnValue(true));

        $this->context->assertResponseBodyContainsJson(new PyStringNode(['{"bar":"foo","foo":"bar"}'], 1));
    }

    /**
     * @see https://github.com/imbo/behat-api-extension/issues/7
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage It's not allowed to set a request body when using multipart/form-data or form parameters.
     * @covers ::setRequestBody
     * @group setup
     */
    public function testThrowsExceptionWhenTryingToCombineARequestBodyWithMultipartOrFormData() {
        $this->mockHandler->append(new Response(200));
        $this->context->addMultipartFileToRequest(__FILE__, 'file');
        $this->context->setRequestBody('some body');
    }

    /**
     * @covers ::setRequestBodyToFileResource
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage File does not exist: "/foo/bar"
     * @group setup
     */
    public function testThrowsExceptionWhenAttachingANonExistingFileToTheRequestBody() {
        $this->mockHandler->append(new Response());
        $this->context->setRequestBodyToFileResource('/foo/bar');
    }

    /**
     * @covers ::setRequestBodyToFileResource
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage File is not readable: "/non/readable/file"
     * @group setup
     */
    public function testThrowsExceptionWhenAttachingANonReadableFileToTheRequestBody() {
        $this->mockHandler->append(new Response());
        $this->context->setRequestBodyToFileResource('/non/readable/file');
    }

    /**
     * @expectedException GuzzleHttp\Exception\RequestException
     * @expectedExceptionMessage error
     * @covers ::sendRequest
     * @group request
     */
    public function testThrowsExceptionWhenTheRequestCanNotBeSent() {
        $this->mockHandler->append(new RequestException('error', new Request('GET', 'path')));
        $this->context->requestPath('path');
    }

    /**
     * @expectedException Imbo\BehatApiExtension\Exception\AssertionFailedException
     * @expectedExceptionMessage Expected response code 400, got 200
     * @covers ::assertResponseCodeIs
     * @group assertions
     */
    public function testAssertingWhatTheResponseCodeIsCanFail() {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath('/some/path');
        $this->context->assertResponseCodeIs(400);
    }

    /**
     * @dataProvider getInvalidHttpResponseCodes
     * @covers ::assertResponseCodeIs
     * @covers ::validateResponseCode
     * @group assertions
     *
     * @param int $code
     */
    public function testThrowsExceptionWhenSpecifyingAnInvalidCodeWhenAssertingWhatTheResponseCodeIs($code) {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath('/some/path');

        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage(sprintf('Response code must be between 100 and 599, got %d.', $code));

        $this->context->assertResponseCodeIs($code);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The request has not been made yet, so no response object exists.
     * @covers ::assertResponseCodeIs
     * @covers ::requireResponse
     * @group assertions
     */
    public function testThrowsExceptionWhenAssertingWhatTheResponseCodeIsWhenNoResponseExists() {
        $this->context->assertResponseCodeIs(200);
    }

    /**
     * @expectedException Imbo\BehatApiExtension\Exception\AssertionFailedException
     * @expectedExceptionMessage Did not expect response code 200
     * @covers ::assertResponseCodeIsNot
     * @group assertions
     */
    public function testAssertingWhatTheResponseCodeIsNotCanFail() {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath('/some/path');
        $this->context->assertResponseCodeIsNot(200);
    }

    /**
     * @dataProvider getInvalidHttpResponseCodes
     * @covers ::assertResponseCodeIsNot
     * @covers ::validateResponseCode
     * @group assertions
     *
     * @param int $code
     */
    public function testThrowsExceptionWhenSpecifyingAnInvalidCodeWhenAssertingWhatTheResponseCodeIsNot($code) {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath('/some/path');

        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage(sprintf('Response code must be between 100 and 599, got %d.', $code));

        $this->context->assertResponseCodeIsNot($code);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The request has not been made yet, so no response object exists.
     * @covers ::assertResponseCodeIsNot
     * @covers ::requireResponse
     * @group assertions
     */
    public function testThrowsExceptionWhenAssertingWhatTheResponseCodeIsNotWhenNoResponseExists() {
        $this->context->assertResponseCodeIsNot(200);
    }

    /**
     * @dataProvider getDataForResponseGroupFailures
     * @covers ::assertResponseIs
     * @covers ::getResponseCodeGroupRange
     * @covers ::getResponseGroup
     * @group assertions
     *
     * @param int $responseCode
     * @param string $actualGroup
     * @param string $expectedGroup
     */
    public function testAssertingThatTheResponseIsInAGroupCanFail($responseCode, $actualGroup, $expectedGroup) {
        $this->mockHandler->append(new Response($responseCode));
        $this->context->requestPath('/some/path');

        $this->expectException('Imbo\BehatApiExtension\Exception\AssertionFailedException');
        $this->expectExceptionMessage(sprintf(
            'Expected response group "%s", got "%s" (response code: %d).',
            $expectedGroup,
            $actualGroup,
            $responseCode
        ));
        $this->context->assertResponseIs($expectedGroup);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The request has not been made yet, so no response object exists.
     * @covers ::assertResponseIs
     * @covers ::requireResponse
     * @group assertions
     */
    public function testThrowsExceptionWhenAssertingWhichGroupTheResponseIsInWhenNoResponseExists() {
        $this->context->assertResponseIs('success');
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid response code group: foobar
     * @covers ::assertResponseIs
     * @covers ::getResponseCodeGroupRange
     * @group assertions
     */
    public function testThrowsExceptionWhenAssertingThatTheResponseIsInAnInvalidGroup() {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath('/some/path');
        $this->context->assertResponseIs('foobar');
    }

    /**
     * @expectedException Imbo\BehatApiExtension\Exception\AssertionFailedException
     * @expectedExceptionMessage Did not expect response to be in the "success" group (response code: 200).
     * @covers ::assertResponseIsNot
     * @covers ::assertResponseIs
     * @group assertions
     */
    public function testAssertingThatTheResponseIsNotInAGroupCanFail() {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath('/some/path');
        $this->context->assertResponseIsNot('success');
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid response code group: foobar
     * @covers ::assertResponseIsNot
     * @covers ::getResponseCodeGroupRange
     * @group assertions
     */
    public function testThrowsExceptionWhenAssertingThatTheResponseIsNotInAnInvalidGroup() {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath('/some/path');
        $this->context->assertResponseIsNot('foobar');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The request has not been made yet, so no response object exists.
     * @covers ::assertResponseIsNot
     * @covers ::requireResponse
     * @group assertions
     */
    public function testThrowsExceptionWhenAssertingWhichGroupTheResponseIsNotInWhenNoResponseExists() {
        $this->context->assertResponseIsNot('success');
    }

    /**
     * @covers ::assertResponseReasonPhraseIs
     * @expectedException Imbo\BehatApiExtension\Exception\AssertionFailedException
     * @expectedExceptionMessage Expected response reason phrase "ok", got "OK".
     * @group assertions
     */
    public function testAssertingWhatTheResponseReasonPhraseIsCanFail() {
        $this->mockHandler->append(new Response());
        $this->context->requestPath('/some/path');
        $this->context->assertResponseReasonPhraseIs('ok');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The request has not been made yet, so no response object exists.
     * @covers ::assertResponseReasonPhraseIs
     * @covers ::requireResponse
     * @group assertions
     */
    public function testThrowsExceptionWhenAssertingWhatTheResponseReasonPhraseIsWhenNoResponseExist() {
        $this->context->assertResponseReasonPhraseIs('OK');
    }

    /**
     * @covers ::assertResponseReasonPhraseIsNot
     * @expectedException Imbo\BehatApiExtension\Exception\AssertionFailedException
     * @expectedExceptionMessage Did not expect response reason phrase "OK".
     * @group assertions
     */
    public function testAssertingWhatTheResponseReasonPhraseIsNotCanFail() {
        $this->mockHandler->append(new Response());
        $this->context->requestPath('/some/path');
        $this->context->assertResponseReasonPhraseIsNot('OK');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The request has not been made yet, so no response object exists.
     * @covers ::assertResponseReasonPhraseIsNot
     * @covers ::requireResponse
     * @group assertions
     */
    public function testThrowsExceptionWhenAssertingWhatTheResponseReasonPhraseIsNotWhenNoResponseExist() {
        $this->context->assertResponseReasonPhraseIsNot('OK');
    }

    /**
     * @expectedException Imbo\BehatApiExtension\Exception\AssertionFailedException
     * @expectedExceptionMessage Expected the response reason phrase to match the regular expression "/ok/", got "OK".
     * @covers ::assertResponseReasonPhraseMatches
     * @group assertions
     */
    public function testAssertingThatTheResponseReasonPhraseMatchesAnExpressionCanFail() {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath('/some/path');
        $this->context->assertResponseReasonPhraseMatches('/ok/');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The request has not been made yet, so no response object exists.
     * @covers ::assertResponseReasonPhraseMatches
     * @covers ::requireResponse
     * @group assertions
     */
    public function testThrowsExceptionWhenAssertingThatTheResponseReasonPhraseMatchesAnExpressionWhenThereIsNoResponse() {
        $this->context->assertResponseReasonPhraseMatches('/ok/');
    }

    /**
     * @covers ::assertResponseStatusLineIs
     * @expectedException Imbo\BehatApiExtension\Exception\AssertionFailedException
     * @expectedExceptionMessage Expected response status line "200 Foobar", got "200 OK".
     * @group assertions
     */
    public function testAssertingWhatTheResponseStatusLineIsCanFail() {
        $this->mockHandler->append(new Response());
        $this->context->requestPath('/some/path');
        $this->context->assertResponseStatusLineIs('200 Foobar');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The request has not been made yet, so no response object exists.
     * @covers ::assertResponseStatusLineIs
     * @covers ::requireResponse
     * @group assertions
     */
    public function testThrowsExceptionWhenAssertingWhatTheResponseStatusLineIsWhenNoResponseExist() {
        $this->context->assertResponseStatusLineIs('200 OK');
    }

    /**
     * @covers ::assertResponseStatusLineIsNot
     * @expectedException Imbo\BehatApiExtension\Exception\AssertionFailedException
     * @expectedExceptionMessage Did not expect response status line "200 OK".
     * @group assertions
     */
    public function testAssertingWhatTheResponseStatusLineIsNotCanFail() {
        $this->mockHandler->append(new Response());
        $this->context->requestPath('/some/path');
        $this->context->assertResponseStatusLineIsNot('200 OK');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The request has not been made yet, so no response object exists.
     * @covers ::assertResponseStatusLineIsNot
     * @covers ::requireResponse
     * @group assertions
     */
    public function testThrowsExceptionWhenAssertingWhatTheResponseStatusLineIsNotWhenNoResponseExist() {
        $this->context->assertResponseStatusLineIsNot('200 OK');
    }

    /**
     * @expectedException Imbo\BehatApiExtension\Exception\AssertionFailedException
     * @expectedExceptionMessage Expected the response status line to match the regular expression "/200 ok/", got "200 OK".
     * @covers ::assertResponseStatusLineMatches
     * @group assertions
     */
    public function testAssertingThatTheResponseStatusLineMatchesAnExpressionCanFail() {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath('/some/path');
        $this->context->assertResponseStatusLineMatches('/200 ok/');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The request has not been made yet, so no response object exists.
     * @covers ::assertResponseStatusLineMatches
     * @covers ::requireResponse
     * @group assertions
     */
    public function testThrowsExceptionWhenAssertingThatTheResponseStatusLineMatchesAnExpressionWhenNoResponseExist() {
        $this->context->assertResponseStatusLineMatches('/200 OK/');
    }

    /**
     * @expectedException Imbo\BehatApiExtension\Exception\AssertionFailedException
     * @expectedExceptionMessage The "Content-Type" response header does not exist
     * @covers ::assertResponseHeaderExists
     * @group assertions
     */
    public function testAssertingThatAResponseHeaderExistsCanFail() {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath('/some/path');
        $this->context->assertResponseHeaderExists('Content-Type');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The request has not been made yet, so no response object exists.
     * @covers ::assertResponseHeaderExists
     * @covers ::requireResponse
     * @group assertions
     */
    public function testThrowsExceptionWhenAssertingThatAResponseHeaderExistWhenNoResponseExist() {
        $this->context->assertResponseHeaderExists('Connection');
    }

    /**
     * @expectedException Imbo\BehatApiExtension\Exception\AssertionFailedException
     * @expectedExceptionMessage The "Content-Type" response header should not exist
     * @covers ::assertResponseHeaderDoesNotExist
     * @group assertions
     */
    public function testAssertingThatAResponseHeaderDoesNotExistCanFail() {
        $this->mockHandler->append(new Response(200, ['Content-Type' => 'application/json']));
        $this->context->requestPath('/some/path');
        $this->context->assertResponseHeaderDoesNotExist('Content-Type');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The request has not been made yet, so no response object exists.
     * @covers ::assertResponseHeaderDoesNotExist
     * @covers ::requireResponse
     * @group assertions
     */
    public function testThrowsExceptionWhenAssertingThatAResponseHeaderDoesNotExistWhenNoResponseExist() {
        $this->context->assertResponseHeaderDoesNotExist('Connection');
    }

    /**
     * @expectedException Imbo\BehatApiExtension\Exception\AssertionFailedException
     * @expectedExceptionMessage Expected the "Content-Type" response header to be "application/xml", got "application/json".
     * @covers ::assertResponseHeaderIs
     * @group assertions
     */
    public function testAssertingWhatAResponseHeaderIsCanFail() {
        $this->mockHandler->append(new Response(200, ['Content-Type' => 'application/json']));
        $this->context->requestPath('/some/path');
        $this->context->assertResponseHeaderIs('Content-Type', 'application/xml');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The request has not been made yet, so no response object exists.
     * @covers ::assertResponseHeaderIs
     * @covers ::requireResponse
     * @group assertions
     */
    public function testThrowsExceptionWhenAssertingWhatAResponseHeaderIsWhenNoResponseExist() {
        $this->context->assertResponseHeaderIs('Connection', 'close');
    }

    /**
     * @expectedException Imbo\BehatApiExtension\Exception\AssertionFailedException
     * @expectedExceptionMessage Did not expect the "content-type" response header to be "123".
     * @covers ::assertResponseHeaderIsNot
     * @group assertions
     */
    public function testAssertingWhatAResponseHeaderIsNotCanFail() {
        $this->mockHandler->append(new Response(200, ['Content-Type' => '123']));
        $this->context->requestPath('/some/path');
        $this->context->assertResponseHeaderIsNot('content-type', '123');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The request has not been made yet, so no response object exists.
     * @covers ::assertResponseHeaderIsNot
     * @covers ::requireResponse
     * @group assertions
     */
    public function testThrowsExceptionWhenAssertingWhatAResponseHeaderIsNotWhenNoResponseExist() {
        $this->context->assertResponseHeaderIsNot('header', 'value');
    }
    /**
     * @expectedException Imbo\BehatApiExtension\Exception\AssertionFailedException
     * @expectedExceptionMessage Expected the "Content-Type" response header to match the regular expression "#^application/xml$#", got "application/json".
     * @covers ::assertResponseHeaderMatches
     * @group assertions
     */
    public function testAssertingThatAResponseHeaderMatchesAnExpressionCanFail() {
        $this->mockHandler->append(new Response(200, ['Content-Type' => 'application/json']));
        $this->context->requestPath('/some/path');
        $this->context->assertResponseHeaderMatches('Content-Type', '#^application/xml$#');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The request has not been made yet, so no response object exists.
     * @covers ::assertResponseHeaderMatches
     * @covers ::requireResponse
     * @group assertions
     */
    public function testThrowsExceptionWhenAssertingThatAResponseHeaderMatchesAnExpressionWhenNoResponseExist() {
        $this->context->assertResponseHeaderMatches('Connection', 'close');
    }

    /**
     * @expectedException Imbo\BehatApiExtension\Exception\AssertionFailedException
     * @expectedExceptionMessage Expected response body "foo", got "response body".
     * @covers ::assertResponseBodyIs
     * @group assertions
     */
    public function testAssertingWhatTheResponseBodyIsCanFail() {
        $this->mockHandler->append(new Response(200, [], 'response body'));
        $this->context->requestPath('/some/path');
        $this->context->assertResponseBodyIs(new PyStringNode(['foo'], 1));
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The request has not been made yet, so no response object exists.
     * @covers ::assertResponseBodyIs
     * @covers ::requireResponse
     * @group assertions
     */
    public function testThrowsExceptionWhenAssertingWhatTheResponseBodyIsWhenNoResponseExist() {
        $this->context->assertResponseBodyIs(new PyStringNode(['some body'], 1));
    }

    /**
     * @expectedException Imbo\BehatApiExtension\Exception\AssertionFailedException
     * @expectedExceptionMessage Did not expect response body to be "response body".
     * @covers ::assertResponseBodyIsNot
     * @group assertions
     */
    public function testAssertingWhatTheResponseBodyIsNotCanFail() {
        $this->mockHandler->append(new Response(200, [], 'response body'));
        $this->context->requestPath('/some/path');
        $this->context->assertResponseBodyIsNot(new PyStringNode(['response body'], 1));
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The request has not been made yet, so no response object exists.
     * @covers ::assertResponseBodyIsNot
     * @covers ::requireResponse
     * @group assertions
     */
    public function testThrowsExceptionWhenAssertingWhatTheResponseBodyIsNotWhenNoResponseExist() {
        $this->context->assertResponseBodyIsNot(new PyStringNode(['some body'], 1));
    }

    /**
     * @expectedException Imbo\BehatApiExtension\Exception\AssertionFailedException
     * @expectedExceptionMessage Expected response body to match regular expression "/^{"FOO": "BAR"}$/", got "{"foo":"bar"}".
     * @covers ::assertResponseBodyMatches
     * @group assertions
     */
    public function testAssertingThatTheResponseBodyMatchesAnExpressionCanFail() {
        $this->mockHandler->append(new Response(200, [], '{"foo":"bar"}'));
        $this->context->requestPath('/some/path');
        $this->context->assertResponseBodyMatches(new PyStringNode(['/^{"FOO": "BAR"}$/'], 1));
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The request has not been made yet, so no response object exists.
     * @covers ::assertResponseBodyMatches
     * @covers ::requireResponse
     * @group assertions
     */
    public function testThrowsExceptionWhenAssertingThatTheResponseBodyMatchesAnExpressionWhenNoResponseExist() {
        $this->context->assertResponseBodyMatches(new PyStringNode(['/foo/'], 1));
    }

    /**
     * @expectedException Imbo\BehatApiExtension\Exception\AssertionFailedException
     * @expectedExceptionMessage Expected response body to be an empty JSON array, got "[
     * @covers ::assertResponseBodyIsAnEmptyJsonArray
     * @covers ::getResponseBodyArray
     * @covers ::getResponseBody
     * @group assertions
     */
    public function testAssertingThatTheResponseIsAnEmptyArrayCanFail() {
        $this->mockHandler->append(new Response(200, [], json_encode([1, 2, 3])));
        $this->context->requestPath('/some/path');
        $this->context->assertResponseBodyIsAnEmptyJsonArray();
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The response body does not contain a valid JSON array / object.
     * @covers ::assertResponseBodyIsAnEmptyJsonArray
     * @covers ::getResponseBodyArray
     * @covers ::getResponseBody
     * @group assertions
     */
    public function testThrowsExceptionWhenAssertingThatTheResponseBodyIsAnEmptyArrayWhenTheBodyDoesNotContainAnArray() {
        $this->mockHandler->append(new Response(200, [], 123));
        $this->context->requestPath('/some/path');
        $this->context->assertResponseBodyIsAnEmptyJsonArray();
    }

    /**
     * @expectedException Imbo\BehatApiExtension\Exception\AssertionFailedException
     * @expectedExceptionMessage Expected response body to be an empty JSON object, got "{
     * @covers ::assertResponseBodyIsAnEmptyJsonObject
     * @group assertions
     */
    public function testAssertingThatTheResponseIsAnEmptyObjectCanFail() {
        $object = new stdClass();
        $object->foo = 'bar';
        $this->mockHandler->append(new Response(200, [], json_encode($object)));
        $this->context->requestPath('/some/path');
        $this->context->assertResponseBodyIsAnEmptyJsonObject();
    }

    /**
     * @expectedException Imbo\BehatApiExtension\Exception\AssertionFailedException
     * @expectedExceptionMessage Expected response body to be a JSON object.
     * @covers ::assertResponseBodyIsAnEmptyJsonObject
     * @covers ::getResponseBody
     * @group assertions
     */
    public function testThrowsExceptionWhenAssertingThatTheResponseBodyIsAnEmptyObjectWhenTheBodyDoesNotContainAnObject() {
        $this->mockHandler->append(new Response(200, [], json_encode([])));
        $this->context->requestPath('/some/path');
        $this->context->assertResponseBodyIsAnEmptyJsonObject();
    }

    /**
     * @expectedException Imbo\BehatApiExtension\Exception\AssertionFailedException
     * @expectedExceptionMessage Expected response body to be a JSON array with 2 entries, got 3: "[
     * @covers ::assertResponseBodyJsonArrayLength
     * @covers ::getResponseBodyArray
     * @covers ::getResponseBody
     * @group assertions
     */
    public function testAssertingThatTheResponseBodyIsAJsonArrayWithACertainLengthCanFail() {
        $this->mockHandler->append(new Response(200, [], json_encode([1, 2, 3])));
        $this->context->requestPath('/some/path');
        $this->context->assertResponseBodyJsonArrayLength(2);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The request has not been made yet, so no response object exists.
     * @covers ::assertResponseBodyJsonArrayLength
     * @covers ::requireResponse
     * @group assertions
     */
    public function testThrowsExceptionWhenAssertingTheLengthOfAJsonArrayInTheResponseBodyWhenNoResponseExist() {
        $this->context->assertResponseBodyJsonArrayLength(5);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The response body does not contain a valid JSON array.
     * @covers ::assertResponseBodyJsonArrayLength
     * @covers ::getResponseBodyArray
     * @group assertions
     */
    public function testThrowsExceptionWhenAssertingTheLengthOfAJsonArrayInTheResponseBodyAndTheBodyDoesNotContainAnArray() {
        $this->mockHandler->append(new Response(200, [], json_encode(['foo' => 'bar'])));
        $this->context->requestPath('/some/path');
        $this->context->assertResponseBodyJsonArrayLength(0);
    }

    /**
     * @expectedException Imbo\BehatApiExtension\Exception\AssertionFailedException
     * @expectedExceptionMessage Expected response body to be a JSON array with at least 4 entries, got 3: "[
     * @covers ::assertResponseBodyJsonArrayMinLength
     * @covers ::getResponseBody
     * @group assertions
     */
    public function testAssertingThatTheResponseBodyContainsAJsonArrayWithAMinimumLengthCanFail() {
        $this->mockHandler->append(new Response(200, [], json_encode([1, 2, 3])));
        $this->context->requestPath('/some/path');
        $this->context->assertResponseBodyJsonArrayMinLength(4);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The request has not been made yet, so no response object exists.
     * @covers ::assertResponseBodyJsonArrayMinLength
     * @covers ::requireResponse
     * @group assertions
     */
    public function testThrowsExceptionWhenAssertingTheMinimumLengthOfAnArrayInTheResponseBodyWhenNoResponseExist() {
        $this->context->assertResponseBodyJsonArrayMinLength(5);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The response body does not contain a valid JSON array.
     * @covers ::assertResponseBodyJsonArrayMinLength
     * @covers ::getResponseBody
     * @group assertions
     */
    public function testThrowsExceptionWhenAssertingTheMinimumLengthOfAnArrayInTheResponseBodyAndTheBodyDoesNotContainAnArray() {
        $this->mockHandler->append(new Response(200, [], json_encode(['foo' => 'bar'])));
        $this->context->requestPath('/some/path');
        $this->context->assertResponseBodyJsonArrayMinLength(2);
    }

    /**
     * @expectedException Imbo\BehatApiExtension\Exception\AssertionFailedException
     * @expectedExceptionMessage Expected response body to be a JSON array with at most 2 entries, got 3: "[
     * @covers ::assertResponseBodyJsonArrayMaxLength
     * @covers ::getResponseBody
     * @group assertions
     */
    public function testAssertingThatTheResponseBodyContainsAJsonArrayWithAMaximumLengthCanFail() {
        $this->mockHandler->append(new Response(200, [], json_encode([1, 2, 3])));
        $this->context->requestPath('/some/path');
        $this->context->assertResponseBodyJsonArrayMaxLength(2);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The request has not been made yet, so no response object exists.
     * @covers ::assertResponseBodyJsonArrayMaxLength
     * @covers ::requireResponse
     * @group assertions
     */
    public function testThrowsExceptionWhenAssertingTheMaximumLengthOfAnArrayInTheResponseBodyWhenNoResponseExist() {
        $this->context->assertResponseBodyJsonArrayMaxLength(5);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The response body does not contain a valid JSON array.
     * @covers ::assertResponseBodyJsonArrayMaxLength
     * @covers ::getResponseBody
     * @group assertions
     */
    public function testThrowsExceptionWhenAssertingTheMaximumLengthOfAnArrayInTheResponseBodyAndTheBodyDoesNotContainAnArray() {
        $this->mockHandler->append(new Response(200, [], json_encode(['foo' => 'bar'])));
        $this->context->requestPath('/some/path');
        $this->context->assertResponseBodyJsonArrayMaxLength(2);
    }

    /**
     * @expectedException OutOfRangeException
     * @expectedExceptionMessage error message
     * @covers ::assertResponseBodyContainsJson
     * @covers ::getResponseBody
     * @group assertions
     */
    public function testAssertingThatTheResponseBodyContainsJsonCanFail() {
        $this->mockHandler->append(new Response(200, [], '{"foo":"bar"}'));
        $this->context->requestPath('/some/path');
        $this->comparator
            ->expects($this->once())
            ->method('compare')
            ->with(['bar' => 'foo'], ['foo' => 'bar'])
            ->will($this->throwException(new OutOfRangeException('error message')));

        $this->context->assertResponseBodyContainsJson(new PyStringNode(['{"bar":"foo"}'], 1));
    }

    /**
     * @expectedException Imbo\BehatApiExtension\Exception\AssertionFailedException
     * @expectedExceptionMessage Comparator did not return in a correct manner. Marking assertion as failed.
     * @covers ::setArrayContainsComparator
     * @covers ::assertResponseBodyContainsJson
     * @covers ::getResponseBody
     * @group assertions
     */
    public function testWillThrowExceptionWhenArrayContainsComparatorDoesNotReturnInACorrectMannerWhenCheckingTheResponseBodyForJson() {
        $this->mockHandler->append(new Response(200, [], '{"foo":"bar"}'));
        $this->context->requestPath('/some/path');
        $this->comparator
            ->expects($this->once())
            ->method('compare')
            ->with(['bar' => 'foo'], ['foo' => 'bar'])
            ->will($this->returnValue(null));

        $this->context->assertResponseBodyContainsJson(new PyStringNode(['{"bar":"foo"}'], 1));
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The request has not been made yet, so no response object exists.
     * @covers ::assertResponseBodyContainsJson
     * @covers ::requireResponse
     * @group assertions
     */
    public function testThrowsExceptionWhenAssertingThatTheResponseContainsJsonAndNoResponseExist() {
        $this->context->assertResponseBodyContainsJson(new PyStringNode(['{"foo":"bar"}'], 1));
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The response body does not contain valid JSON data.
     * @covers ::assertResponseBodyContainsJson
     * @covers ::getResponseBody
     * @group assertions
     */
    public function testThrowsExceptionWhenAssertingThatTheResponseContainsJsonAndTheResponseContainsInvalidData() {
        $this->mockHandler->append(new Response(200, [], "{'foo':'bar'}"));
        $this->context->requestPath('/some/path');
        $this->context->assertResponseBodyContainsJson(new PyStringNode(['{"foo":"bar"}'], 1));
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The supplied parameter is not a valid JSON object.
     * @covers ::assertResponseBodyContainsJson
     * @group assertions
     */
    public function testThrowsExceptionWhenAssertingThatTheBodyContainsJsonAndTheParameterFromTheTestIsInvalid() {
        $this->mockHandler->append(new Response(200, [], '{"foo":"bar"}'));
        $this->context->requestPath('/some/path');
        $this->context->assertResponseBodyContainsJson(new PyStringNode(["{'foo':'bar'}"], 1));
    }
}
