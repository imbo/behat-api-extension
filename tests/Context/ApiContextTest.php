<?php declare(strict_types=1);
namespace Imbo\BehatApiExtension\Context;

use Imbo\BehatApiExtension\ArrayContainsComparator;
use Imbo\BehatApiExtension\ArrayContainsComparator\Matcher\JWT;
use Imbo\BehatApiExtension\Exception\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Middleware;
use GuzzleHttp\Exception\RequestException;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use InvalidArgumentException;
use RuntimeException;
use OutOfRangeException;
use stdClass;

/**
 * Namespaced version of file_exists that returns true for a fixed filename. All other paths are
 * checked by the global file_exists function.
 */
function file_exists(string $path) : bool {
    if ($path === '/non/readable/file') {
        return true;
    }

    return \file_exists($path);
}

/**
 * Namespaced version of is_readable that returns false for a fixed filename. All other paths are
 * checked by the global is_readable function.
 */
function is_readable(string $path) : bool {
    if ($path === '/none/readable/file') {
        return false;
    }

    return \is_readable($path);
}

/**
 * @coversDefaultClass Imbo\BehatApiExtension\Context\ApiContext
 */
class ApiContextText extends TestCase {
    private $mockHandler;
    private $handlerStack;
    private $historyContainer = [];
    private $context;
    private $client;
    private $comparator;
    private $baseUri = 'http://localhost:9876';

    public function setUp() : void {
        $this->historyContainer = [];

        $this->mockHandler = new MockHandler();
        $this->handlerStack = HandlerStack::create($this->mockHandler);
        $this->handlerStack->push(Middleware::history($this->historyContainer));
        $this->client = new Client([
            'handler' => $this->handlerStack,
            'base_uri' => $this->baseUri,
        ]);
        $this->comparator = $this->createMock(ArrayContainsComparator::class);

        $this->context = new ApiContext();
        $this->context->setClient($this->client);
        $this->context->setArrayContainsComparator($this->comparator);
    }

    public function getHttpMethods() : array {
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

    public function getFilesAndMimeTypes() : array {
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

    public function getResponseCodes() : array {
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

    public function getGroupAndResponseCodes() : array {
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

    public function getInvalidHttpResponseCodes() : array {
        return [
            [99],
            [600],
        ];
    }

    public function getResponseBodyArrays() : array {
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

    public function getResponseBodyArraysForAtLeast() : array {
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

    public function getResponseBodyArraysForAtMost() : array {
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

    public function getResponseCodesAndReasonPhrases() : array {
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

    public function getUris() : array {
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

    public function getDataForResponseGroupFailures() : array {
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

    public function getRequestBodyValues() : array {
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
     */
    public function testCanSetRequestHeaders() : void {
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
     */
    public function testCanAddRequestHeaders() : void {
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
     */
    public function testSupportBasicHttpAuthentication() : void {
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
     * @covers ::addMultipartPart
     */
    public function testCanAddMultipleMultipartFilesToTheRequest() : void {
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
            $this->assertStringContainsString(
                file_get_contents($path),
                $contents
            );
        }
    }

    /**
     * @covers ::setRequestMultipartFormParams
     * @covers ::addMultipartPart
     */
    public function testCanAddMultipartFormDataParametersToTheRequest() : void {
        $this->mockHandler->append(new Response(200));
        $this->assertSame($this->context, $this->context->setRequestMultipartFormParams(new TableNode([
            ['name', 'value'],
            ['foo', 'bar'],
            ['bar', 'foo'],
            ['bar', 'bar'],
        ])));

        $this->context->requestPath('/some/path', 'POST');

        $this->assertSame(1, count($this->historyContainer));

        $request = $this->historyContainer[0]['request'];
        $boundary = $request->getBody()->getBoundary();

        $this->assertSame(sprintf('multipart/form-data; boundary=%s', $boundary), $request->getHeaderLine('Content-Type'));
    }

    /**
     * @covers ::setRequestFormParams
     * @covers ::sendRequest
     */
    public function testCanSetFormParametersInTheRequest() : void {
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

    public function getHttpMethodsForFormParametersTest() : array {
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
     */
    public function testCanSetFormParametersInTheRequestWithCustomMethod(string $httpMethod) : void {
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
     */
    public function testCanSetFormParametersAndAttachAFileInTheSameMultipartRequest() : void {
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

        $this->assertStringContainsString('Content-Disposition: form-data; name="file"; filename="ApiContextTest.php"', $contents);
        $this->assertStringContainsString(file_get_contents(__FILE__), $contents);

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
        $this->assertStringContainsString($foo, $contents);
        $this->assertStringContainsString($bar0, $contents);
        $this->assertStringContainsString($bar1, $contents);
    }

    /**
     * @covers ::addMultipartFileToRequest
     */
    public function testThrowsExceptionWhenAddingNonExistingFileAsMultipartPartToTheRequest() : void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('File does not exist: "/foo/bar"');
        $this->context->addMultipartFileToRequest('/foo/bar', 'foo');
    }

    /**
     * @covers ::setRequestBodyToFileResource
     * @covers ::setRequestBody
     */
    public function testCanSetRequestBodyToAFile() : void {
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
     */
    public function testCanSetRequestBodyToAString($data, string $expected) : void {
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
     */
    public function testResolvesFilePathsCorrectlyWhenAttachingFilesToTheRequestBody(string $baseUri, string $path, string $fullUri) : void {
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
     * @covers ::addJwtToken
     * @covers ::jsonDecode
     */
    public function testCanAddJwtTokensToTheJwtMatcher() : void {
        $name = 'some name';
        $payload = ['some' => 'data'];
        $secret = 'secret';

        $matcher = $this->createMock(JWT::class);
        $matcher
            ->expects($this->once())
            ->method('addToken')
            ->with($name, $payload, $secret);

        $this->comparator
            ->expects($this->once())
            ->method('getMatcherFunction')
            ->with('jwt')
            ->willReturn($matcher);

        $this->assertSame(
            $this->context,
            $this->context->addJwtToken($name, $secret, new PyStringNode(['{"some":"data"}'], 1)),
            'Expected method to return own instance'
        );
    }

    /**
     * @covers ::addJwtToken
     */
    public function testThrowsExceptionWhenTryingToAddJwtTokenWhenThereIsNoMatcherFunctionRegistered() : void {
        $this->comparator
            ->expects($this->once())
            ->method('getMatcherFunction')
            ->with('jwt')
            ->willReturn('json_encode');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Matcher registered for the @jwt() matcher function must be an instance of Imbo\BehatApiExtension\ArrayContainsComparator\Matcher\JWT');

        $this->context->addJwtToken('name', 'secret', new PyStringNode(['{"some":"data"}'], 1));
    }

    /**
     * @dataProvider getHttpMethods
     * @covers ::requestPath
     * @covers ::setRequestPath
     * @covers ::setRequestMethod
     * @covers ::sendRequest
     */
    public function testCanMakeRequestsUsingDifferentHttpMethods(string $method) : void {
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
     */
    public function testCanMakeRequestsWithQueryStringInThePath() : void {
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
     */
    public function testCanAssertWhatTheResponseCodeIs(int $code) : void {
        $this->mockHandler->append(new Response($code));
        $this->context->requestPath('/some/path');
        $this->assertNull($this->context->assertResponseCodeIs($code));
    }

    /**
     * @covers ::assertResponseCodeIsNot
     * @covers ::validateResponseCode
     */
    public function testCanAssertWhatTheResponseCodeIsNot() : void {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath('/some/path');
        $this->assertNull($this->context->assertResponseCodeIsNot(201));
    }

    /**
     * @dataProvider getGroupAndResponseCodes
     * @covers ::assertResponseIs
     * @covers ::requireResponse
     * @covers ::getResponseCodeGroupRange
     */
    public function testCanAssertWhichGroupTheResponseIsIn(string $group, array $codes) : void {
        foreach ($codes as $code) {
            $this->mockHandler->append(new Response($code, [], 'response body'));
            $this->context->requestPath('/some/path');
            $this->assertNull($this->context->assertResponseIs($group));
        }
    }

    /**
     * @dataProvider getGroupAndResponseCodes
     * @covers ::assertResponseIsNot
     * @covers ::assertResponseIs
     */
    public function testCanAssertWhichGroupTheResponseIsNotIn(string $group, array $codes) : void {
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

            foreach (array_filter($groups, function(string $g) use ($group) : bool { return $g !== $group; }) as $g) {
                // Assert that the response is not in any of the other groups
                $this->assertNull($this->context->assertResponseIsNot($g));
            }
        }
    }

    /**
     * @dataProvider getResponseCodesAndReasonPhrases
     * @covers ::assertResponseReasonPhraseIs
     */
    public function testCanAssertWhatTheResponseReasonPhraseIs(int $code, string $phrase) : void {
        $this->mockHandler->append(new Response($code, [], null, 1.1, $phrase));
        $this->context->requestPath('/some/path');
        $this->assertNull($this->context->assertResponseReasonPhraseIs($phrase));
    }

    /**
     * @covers ::assertResponseReasonPhraseIsNot
     */
    public function testCanAssertWhatTheResponseReasonPhraseIsNot() : void {
        $this->mockHandler->append(new Response());
        $this->context->requestPath('/some/path');
        $this->assertNull($this->context->assertResponseReasonPhraseIsNot('Not Modified'));
    }

    /**
     * @covers ::assertResponseReasonPhraseMatches
     */
    public function testCanAssertThatTheResponseReasonPhraseMatchesAnExpression() : void {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath('/some/path');
        $this->assertNull($this->context->assertResponseReasonPhraseMatches('/OK/'));
    }

    /**
     * @dataProvider getResponseCodesAndReasonPhrases
     * @covers ::assertResponseStatusLineIs
     */
    public function testCanAssertWhatTheResponseStatusLineIs(int $code, string $phrase) : void {
        $this->mockHandler->append(new Response($code, [], null, 1.1, $phrase));
        $this->context->requestPath('/some/path');
        $this->assertNull($this->context->assertResponseStatusLineIs(sprintf('%d %s', $code, $phrase)));
    }

    /**
     * @covers ::assertResponseStatusLineIsNot
     */
    public function testCanAssertWhatTheResponseStatusLineIsNot() : void {
        $this->mockHandler->append(new Response());
        $this->context->requestPath('/some/path');
        $this->assertNull($this->context->assertResponseStatusLineIsNot('304 Not Modified'));
    }

    /**
     * @covers ::assertResponseStatusLineMatches
     */
    public function testCanAssertThatTheResponseStatusLineMatchesAnExpression() : void {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath('/some/path');
        $this->assertNull($this->context->assertResponseStatusLineMatches('/200 OK/'));
    }

    /**
     * @covers ::assertResponseHeaderExists
     */
    public function testCanAssertThatAResponseHeaderExists() : void {
        $this->mockHandler->append(new Response(200, ['Content-Type' => 'application/json']));
        $this->context->requestPath('/some/path');
        $this->assertNull($this->context->assertResponseHeaderExists('Content-Type'));
    }

    /**
     * @covers ::assertResponseHeaderDoesNotExist
     */
    public function testCanAssertThatAResponseHeaderDoesNotExist() : void {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath('/some/path');
        $this->assertNull($this->context->assertResponseHeaderDoesNotExist('Content-Type'));
    }

    /**
     * @covers ::assertResponseHeaderIs
     */
    public function testCanAssertWhatAResponseHeaderIs() : void {
        $this->mockHandler->append(new Response(200, ['Content-Type' => 'application/json']));
        $this->context->requestPath('/some/path');
        $this->assertNull($this->context->assertResponseHeaderIs('Content-Type', 'application/json'));
    }

    /**
     * @covers ::assertResponseHeaderIsNot
     */
    public function testCanAssertWhatAResponseHeaderIsNot() : void {
        $this->mockHandler->append(new Response(200, ['Content-Length' => '123']));
        $this->context->requestPath('/some/path');
        $this->assertNull($this->context->assertResponseHeaderIsNot('Content-Type', '456'));
    }

    /**
     * @covers ::assertResponseHeaderMatches
     */
    public function testCanAssertThatAResponseHeaderMatchesAnExpression() : void {
        $this->mockHandler->append(new Response(200, ['Content-Type' => 'application/json']));
        $this->context->requestPath('/some/path');
        $this->assertNull($this->context->assertResponseHeaderMatches('Content-Type', '#^application/(json|xml)$#'));
    }

    /**
     * @covers ::assertResponseBodyIs
     */
    public function testCanAssertWhatTheResponseBodyIs() : void {
        $this->mockHandler->append(new Response(200, [], 'response body'));
        $this->context->requestPath('/some/path');
        $this->assertNull($this->context->assertResponseBodyIs(new PyStringNode(['response body'], 1)));
    }

    /**
     * @covers ::assertResponseBodyIsNot
     */
    public function testCanAssertWhatTheResponseBodyIsNot() : void {
        $this->mockHandler->append(new Response(200, [], 'response body'));
        $this->context->requestPath('/some/path');
        $this->assertNull($this->context->assertResponseBodyIsNot(new PyStringNode(['some other response body'], 1)));
    }

    /**
     * @covers ::assertResponseBodyMatches
     */
    public function testCanAssertThatTheResponseBodyMatchesAnExpression() : void {
        $this->mockHandler->append(new Response(200, [], '{"foo":"bar"}'));
        $this->context->requestPath('/some/path');
        $this->assertNull($this->context->assertResponseBodyMatches(new PyStringNode(['/^{"FOO": ?"BAR"}$/i'], 1)));
    }

    /**
     * @covers ::assertResponseBodyIsAnEmptyJsonArray
     * @covers ::getResponseBodyArray
     * @covers ::getResponseBody
     */
    public function testCanAssertThatTheResponseIsAnEmptyArray() : void {
        $this->mockHandler->append(new Response(200, [], json_encode([])));
        $this->context->requestPath('/some/path');
        $this->assertNull($this->context->assertResponseBodyIsAnEmptyJsonArray());
    }

    /**
     * @covers ::assertResponseBodyIsAnEmptyJsonObject
     */
    public function testCanAssertThatTheResponseIsAnEmptyObject() : void {
        $this->mockHandler->append(new Response(200, [], json_encode(new stdClass())));
        $this->context->requestPath('/some/path');
        $this->assertNull($this->context->assertResponseBodyIsAnEmptyJsonObject());
    }

    /**
     * @dataProvider getResponseBodyArrays
     * @covers ::assertResponseBodyJsonArrayLength
     * @covers ::getResponseBodyArray
     */
    public function testCanAssertThatTheArrayInTheResponseBodyHasACertainLength(array $body, int $lengthToUse, bool $willFail) : void {
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

        $this->assertNull($this->context->assertResponseBodyJsonArrayLength($lengthToUse));
    }

    /**
     * @dataProvider getResponseBodyArraysForAtLeast
     * @covers ::assertResponseBodyJsonArrayMinLength
     * @covers ::getResponseBody
     */
    public function testCanAssertTheMinLengthOfAnArrayInTheResponseBody(array $body, int $min) : void {
        $this->mockHandler->append(new Response(200, [], json_encode($body)));
        $this->context->requestPath('/some/path');
        $this->assertNull($this->context->assertResponseBodyJsonArrayMinLength($min));
    }

    /**
     * @dataProvider getResponseBodyArraysForAtMost
     * @covers ::assertResponseBodyJsonArrayMaxLength
     * @covers ::getResponseBody
     */
    public function testCanAssertTheMaxLengthOfAnArrayInTheResponseBody(array $body, int $max) : void {
        $this->mockHandler->append(new Response(200, [], json_encode($body)));
        $this->context->requestPath('/some/path');
        $this->assertNull($this->context->assertResponseBodyJsonArrayMaxLength($max));
    }

    /**
     * @covers ::setArrayContainsComparator
     * @covers ::assertResponseBodyContainsJson
     * @covers ::getResponseBody
     * @covers ::jsonDecode
     */
    public function testCanAssertThatTheResponseBodyContainsJson() : void {
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
     * @covers ::setRequestBody
     */
    public function testThrowsExceptionWhenTryingToCombineARequestBodyWithMultipartOrFormData() : void {
        $this->mockHandler->append(new Response(200));
        $this->context->addMultipartFileToRequest(__FILE__, 'file');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('It\'s not allowed to set a request body when using multipart/form-data or form parameters.');
        $this->context->setRequestBody('some body');
    }

    /**
     * @covers ::setRequestBodyToFileResource
     */
    public function testThrowsExceptionWhenAttachingANonExistingFileToTheRequestBody() : void {
        $this->mockHandler->append(new Response());
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('File does not exist: "/foo/bar"');
        $this->context->setRequestBodyToFileResource('/foo/bar');
    }

    /**
     * @covers ::setRequestBodyToFileResource
     */
    public function testThrowsExceptionWhenAttachingANonReadableFileToTheRequestBody() : void {
        $this->mockHandler->append(new Response());
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('File is not readable: "/non/readable/file"');
        $this->context->setRequestBodyToFileResource('/non/readable/file');
    }

    /**
     * @covers ::sendRequest
     */
    public function testThrowsExceptionWhenTheRequestCanNotBeSent() : void {
        $this->mockHandler->append(new RequestException('error', new Request('GET', 'path')));
        $this->expectException(RequestException::class);
        $this->expectExceptionMessage('error');
        $this->context->requestPath('path');
    }

    /**
     * @covers ::assertResponseCodeIs
     */
    public function testAssertingWhatTheResponseCodeIsCanFail() : void {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath('/some/path');
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Expected response code 400, got 200');
        $this->context->assertResponseCodeIs(400);
    }

    /**
     * @dataProvider getInvalidHttpResponseCodes
     * @covers ::assertResponseCodeIs
     * @covers ::validateResponseCode
     */
    public function testThrowsExceptionWhenSpecifyingAnInvalidCodeWhenAssertingWhatTheResponseCodeIs(int $code) : void {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath('/some/path');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Response code must be between 100 and 599, got %d.', $code));

        $this->context->assertResponseCodeIs($code);
    }

    /**
     * @covers ::assertResponseCodeIs
     * @covers ::requireResponse
     */
    public function testThrowsExceptionWhenAssertingWhatTheResponseCodeIsWhenNoResponseExists() : void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The request has not been made yet, so no response object exists.');
        $this->context->assertResponseCodeIs(200);
    }

    /**
     * @covers ::assertResponseCodeIsNot
     */
    public function testAssertingWhatTheResponseCodeIsNotCanFail() : void {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath('/some/path');
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Did not expect response code 200');
        $this->context->assertResponseCodeIsNot(200);
    }

    /**
     * @dataProvider getInvalidHttpResponseCodes
     * @covers ::assertResponseCodeIsNot
     * @covers ::validateResponseCode
     */
    public function testThrowsExceptionWhenSpecifyingAnInvalidCodeWhenAssertingWhatTheResponseCodeIsNot(int $code) : void {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath('/some/path');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Response code must be between 100 and 599, got %d.', $code));

        $this->context->assertResponseCodeIsNot($code);
    }

    /**
     * @covers ::assertResponseCodeIsNot
     * @covers ::requireResponse
     */
    public function testThrowsExceptionWhenAssertingWhatTheResponseCodeIsNotWhenNoResponseExists() : void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The request has not been made yet, so no response object exists.');
        $this->context->assertResponseCodeIsNot(200);
    }

    /**
     * @dataProvider getDataForResponseGroupFailures
     * @covers ::assertResponseIs
     * @covers ::getResponseCodeGroupRange
     * @covers ::getResponseGroup
     */
    public function testAssertingThatTheResponseIsInAGroupCanFail(int $responseCode, string $actualGroup, string $expectedGroup) : void {
        $this->mockHandler->append(new Response($responseCode));
        $this->context->requestPath('/some/path');

        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage(sprintf(
            'Expected response group "%s", got "%s" (response code: %d).',
            $expectedGroup,
            $actualGroup,
            $responseCode
        ));
        $this->context->assertResponseIs($expectedGroup);
    }

    /**
     * @covers ::assertResponseIs
     * @covers ::requireResponse
     */
    public function testThrowsExceptionWhenAssertingWhichGroupTheResponseIsInWhenNoResponseExists() : void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The request has not been made yet, so no response object exists.');
        $this->context->assertResponseIs('success');
    }

    /**
     * @covers ::assertResponseIs
     * @covers ::getResponseCodeGroupRange
     */
    public function testThrowsExceptionWhenAssertingThatTheResponseIsInAnInvalidGroup() : void {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath('/some/path');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid response code group: foobar');
        $this->context->assertResponseIs('foobar');
    }

    /**
     * @covers ::assertResponseIsNot
     * @covers ::assertResponseIs
     */
    public function testAssertingThatTheResponseIsNotInAGroupCanFail() : void {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath('/some/path');
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Did not expect response to be in the "success" group (response code: 200).');
        $this->context->assertResponseIsNot('success');
    }

    /**
     * @covers ::assertResponseIsNot
     * @covers ::getResponseCodeGroupRange
     */
    public function testThrowsExceptionWhenAssertingThatTheResponseIsNotInAnInvalidGroup() : void {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath('/some/path');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid response code group: foobar');
        $this->context->assertResponseIsNot('foobar');
    }

    /**
     * @covers ::assertResponseIsNot
     * @covers ::requireResponse
     */
    public function testThrowsExceptionWhenAssertingWhichGroupTheResponseIsNotInWhenNoResponseExists() : void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The request has not been made yet, so no response object exists.');
        $this->context->assertResponseIsNot('success');
    }

    /**
     * @covers ::assertResponseReasonPhraseIs
     */
    public function testAssertingWhatTheResponseReasonPhraseIsCanFail() : void {
        $this->mockHandler->append(new Response());
        $this->context->requestPath('/some/path');
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Expected response reason phrase "ok", got "OK".');
        $this->context->assertResponseReasonPhraseIs('ok');
    }

    /**
     * @covers ::assertResponseReasonPhraseIs
     * @covers ::requireResponse
     */
    public function testThrowsExceptionWhenAssertingWhatTheResponseReasonPhraseIsWhenNoResponseExist() : void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The request has not been made yet, so no response object exists.');
        $this->context->assertResponseReasonPhraseIs('OK');
    }

    /**
     * @covers ::assertResponseReasonPhraseIsNot
     */
    public function testAssertingWhatTheResponseReasonPhraseIsNotCanFail() : void {
        $this->mockHandler->append(new Response());
        $this->context->requestPath('/some/path');
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Did not expect response reason phrase "OK".');
        $this->context->assertResponseReasonPhraseIsNot('OK');
    }

    /**
     * @covers ::assertResponseReasonPhraseIsNot
     * @covers ::requireResponse
     */
    public function testThrowsExceptionWhenAssertingWhatTheResponseReasonPhraseIsNotWhenNoResponseExist() : void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The request has not been made yet, so no response object exists.');
        $this->context->assertResponseReasonPhraseIsNot('OK');
    }

    /**
     * @covers ::assertResponseReasonPhraseMatches
     */
    public function testAssertingThatTheResponseReasonPhraseMatchesAnExpressionCanFail() : void {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath('/some/path');
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Expected the response reason phrase to match the regular expression "/ok/", got "OK".');
        $this->context->assertResponseReasonPhraseMatches('/ok/');
    }

    /**
     * @covers ::assertResponseReasonPhraseMatches
     * @covers ::requireResponse
     */
    public function testThrowsExceptionWhenAssertingThatTheResponseReasonPhraseMatchesAnExpressionWhenThereIsNoResponse() : void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The request has not been made yet, so no response object exists.');
        $this->context->assertResponseReasonPhraseMatches('/ok/');
    }

    /**
     * @covers ::assertResponseStatusLineIs
     */
    public function testAssertingWhatTheResponseStatusLineIsCanFail() : void {
        $this->mockHandler->append(new Response());
        $this->context->requestPath('/some/path');
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Expected response status line "200 Foobar", got "200 OK".');
        $this->context->assertResponseStatusLineIs('200 Foobar');
    }

    /**
     * @covers ::assertResponseStatusLineIs
     * @covers ::requireResponse
     */
    public function testThrowsExceptionWhenAssertingWhatTheResponseStatusLineIsWhenNoResponseExist() : void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The request has not been made yet, so no response object exists.');
        $this->context->assertResponseStatusLineIs('200 OK');
    }

    /**
     * @covers ::assertResponseStatusLineIsNot
     */
    public function testAssertingWhatTheResponseStatusLineIsNotCanFail() : void {
        $this->mockHandler->append(new Response());
        $this->context->requestPath('/some/path');
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Did not expect response status line "200 OK".');
        $this->context->assertResponseStatusLineIsNot('200 OK');
    }

    /**
     * @covers ::assertResponseStatusLineIsNot
     * @covers ::requireResponse
     */
    public function testThrowsExceptionWhenAssertingWhatTheResponseStatusLineIsNotWhenNoResponseExist() : void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The request has not been made yet, so no response object exists.');
        $this->context->assertResponseStatusLineIsNot('200 OK');
    }

    /**
     * @covers ::assertResponseStatusLineMatches
     */
    public function testAssertingThatTheResponseStatusLineMatchesAnExpressionCanFail() : void {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath('/some/path');
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Expected the response status line to match the regular expression "/200 ok/", got "200 OK".');
        $this->context->assertResponseStatusLineMatches('/200 ok/');
    }

    /**
     * @covers ::assertResponseStatusLineMatches
     * @covers ::requireResponse
     */
    public function testThrowsExceptionWhenAssertingThatTheResponseStatusLineMatchesAnExpressionWhenNoResponseExist() : void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The request has not been made yet, so no response object exists.');
        $this->context->assertResponseStatusLineMatches('/200 OK/');
    }

    /**
     * @covers ::assertResponseHeaderExists
     */
    public function testAssertingThatAResponseHeaderExistsCanFail() : void {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath('/some/path');
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('The "Content-Type" response header does not exist.');
        $this->context->assertResponseHeaderExists('Content-Type');
    }

    /**
     * @covers ::assertResponseHeaderExists
     * @covers ::requireResponse
     */
    public function testThrowsExceptionWhenAssertingThatAResponseHeaderExistWhenNoResponseExist() : void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The request has not been made yet, so no response object exists.');
        $this->context->assertResponseHeaderExists('Connection');
    }

    /**
     * @covers ::assertResponseHeaderDoesNotExist
     */
    public function testAssertingThatAResponseHeaderDoesNotExistCanFail() : void {
        $this->mockHandler->append(new Response(200, ['Content-Type' => 'application/json']));
        $this->context->requestPath('/some/path');
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('The "Content-Type" response header should not exist.');
        $this->context->assertResponseHeaderDoesNotExist('Content-Type');
    }

    /**
     * @covers ::assertResponseHeaderDoesNotExist
     * @covers ::requireResponse
     */
    public function testThrowsExceptionWhenAssertingThatAResponseHeaderDoesNotExistWhenNoResponseExist() : void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The request has not been made yet, so no response object exists.');
        $this->context->assertResponseHeaderDoesNotExist('Connection');
    }

    /**
     * @covers ::assertResponseHeaderIs
     */
    public function testAssertingWhatAResponseHeaderIsCanFail() : void {
        $this->mockHandler->append(new Response(200, ['Content-Type' => 'application/json']));
        $this->context->requestPath('/some/path');
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Expected the "Content-Type" response header to be "application/xml", got "application/json".');
        $this->context->assertResponseHeaderIs('Content-Type', 'application/xml');
    }

    /**
     * @covers ::assertResponseHeaderIs
     * @covers ::requireResponse
     */
    public function testThrowsExceptionWhenAssertingWhatAResponseHeaderIsWhenNoResponseExist() : void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The request has not been made yet, so no response object exists.');
        $this->context->assertResponseHeaderIs('Connection', 'close');
    }

    /**
     * @covers ::assertResponseHeaderIsNot
     */
    public function testAssertingWhatAResponseHeaderIsNotCanFail() : void {
        $this->mockHandler->append(new Response(200, ['Content-Type' => '123']));
        $this->context->requestPath('/some/path');
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Did not expect the "content-type" response header to be "123".');
        $this->context->assertResponseHeaderIsNot('content-type', '123');
    }

    /**
     * @covers ::assertResponseHeaderIsNot
     * @covers ::requireResponse
     */
    public function testThrowsExceptionWhenAssertingWhatAResponseHeaderIsNotWhenNoResponseExist() : void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The request has not been made yet, so no response object exists.');
        $this->context->assertResponseHeaderIsNot('header', 'value');
    }

    /**
     * @covers ::assertResponseHeaderMatches
     */
    public function testAssertingThatAResponseHeaderMatchesAnExpressionCanFail() : void {
        $this->mockHandler->append(new Response(200, ['Content-Type' => 'application/json']));
        $this->context->requestPath('/some/path');
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Expected the "Content-Type" response header to match the regular expression "#^application/xml$#", got "application/json".');
        $this->context->assertResponseHeaderMatches('Content-Type', '#^application/xml$#');
    }

    /**
     * @covers ::assertResponseHeaderMatches
     * @covers ::requireResponse
     */
    public function testThrowsExceptionWhenAssertingThatAResponseHeaderMatchesAnExpressionWhenNoResponseExist() : void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The request has not been made yet, so no response object exists.');
        $this->context->assertResponseHeaderMatches('Connection', 'close');
    }

    /**
     * @covers ::assertResponseBodyIs
     */
    public function testAssertingWhatTheResponseBodyIsCanFail() : void {
        $this->mockHandler->append(new Response(200, [], 'response body'));
        $this->context->requestPath('/some/path');
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Expected response body "foo", got "response body".');
        $this->context->assertResponseBodyIs(new PyStringNode(['foo'], 1));
    }

    /**
     * @covers ::assertResponseBodyIs
     * @covers ::requireResponse
     */
    public function testThrowsExceptionWhenAssertingWhatTheResponseBodyIsWhenNoResponseExist() : void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The request has not been made yet, so no response object exists.');
        $this->context->assertResponseBodyIs(new PyStringNode(['some body'], 1));
    }

    /**
     * @covers ::assertResponseBodyIsNot
     */
    public function testAssertingWhatTheResponseBodyIsNotCanFail() : void {
        $this->mockHandler->append(new Response(200, [], 'response body'));
        $this->context->requestPath('/some/path');
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Did not expect response body to be "response body".');
        $this->context->assertResponseBodyIsNot(new PyStringNode(['response body'], 1));
    }

    /**
     * @covers ::assertResponseBodyIsNot
     * @covers ::requireResponse
     */
    public function testThrowsExceptionWhenAssertingWhatTheResponseBodyIsNotWhenNoResponseExist() : void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The request has not been made yet, so no response object exists.');
        $this->context->assertResponseBodyIsNot(new PyStringNode(['some body'], 1));
    }

    /**
     * @covers ::assertResponseBodyMatches
     */
    public function testAssertingThatTheResponseBodyMatchesAnExpressionCanFail() : void {
        $this->mockHandler->append(new Response(200, [], '{"foo":"bar"}'));
        $this->context->requestPath('/some/path');
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Expected response body to match regular expression "/^{"FOO": "BAR"}$/", got "{"foo":"bar"}".');
        $this->context->assertResponseBodyMatches(new PyStringNode(['/^{"FOO": "BAR"}$/'], 1));
    }

    /**
     * @covers ::assertResponseBodyMatches
     * @covers ::requireResponse
     */
    public function testThrowsExceptionWhenAssertingThatTheResponseBodyMatchesAnExpressionWhenNoResponseExist() : void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The request has not been made yet, so no response object exists.');
        $this->context->assertResponseBodyMatches(new PyStringNode(['/foo/'], 1));
    }

    /**
     * @covers ::assertResponseBodyIsAnEmptyJsonArray
     * @covers ::getResponseBodyArray
     * @covers ::getResponseBody
     */
    public function testAssertingThatTheResponseIsAnEmptyArrayCanFail() : void {
        $this->mockHandler->append(new Response(200, [], json_encode([1, 2, 3])));
        $this->context->requestPath('/some/path');
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Expected response body to be an empty JSON array, got "[');
        $this->context->assertResponseBodyIsAnEmptyJsonArray();
    }

    /**
     * @covers ::assertResponseBodyIsAnEmptyJsonArray
     * @covers ::getResponseBodyArray
     * @covers ::getResponseBody
     */
    public function testThrowsExceptionWhenAssertingThatTheResponseBodyIsAnEmptyArrayWhenTheBodyDoesNotContainAnArray() : void {
        $this->mockHandler->append(new Response(200, [], 123));
        $this->context->requestPath('/some/path');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The response body does not contain a valid JSON array / object.');
        $this->context->assertResponseBodyIsAnEmptyJsonArray();
    }

    /**
     * @covers ::assertResponseBodyIsAnEmptyJsonObject
     */
    public function testAssertingThatTheResponseIsAnEmptyObjectCanFail() : void {
        $object = new stdClass();
        $object->foo = 'bar';
        $this->mockHandler->append(new Response(200, [], json_encode($object)));
        $this->context->requestPath('/some/path');
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Expected response body to be an empty JSON object, got "{');
        $this->context->assertResponseBodyIsAnEmptyJsonObject();
    }

    /**
     * @covers ::assertResponseBodyIsAnEmptyJsonObject
     * @covers ::getResponseBody
     */
    public function testThrowsExceptionWhenAssertingThatTheResponseBodyIsAnEmptyObjectWhenTheBodyDoesNotContainAnObject() : void {
        $this->mockHandler->append(new Response(200, [], json_encode([])));
        $this->context->requestPath('/some/path');
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Expected response body to be a JSON object.');
        $this->context->assertResponseBodyIsAnEmptyJsonObject();
    }

    /**
     * @covers ::assertResponseBodyJsonArrayLength
     * @covers ::getResponseBodyArray
     * @covers ::getResponseBody
     */
    public function testAssertingThatTheResponseBodyIsAJsonArrayWithACertainLengthCanFail() : void {
        $this->mockHandler->append(new Response(200, [], json_encode([1, 2, 3])));
        $this->context->requestPath('/some/path');
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Expected response body to be a JSON array with 2 entries, got 3: "[');
        $this->context->assertResponseBodyJsonArrayLength(2);
    }

    /**
     * @covers ::assertResponseBodyJsonArrayLength
     * @covers ::requireResponse
     */
    public function testThrowsExceptionWhenAssertingTheLengthOfAJsonArrayInTheResponseBodyWhenNoResponseExist() : void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The request has not been made yet, so no response object exists.');
        $this->context->assertResponseBodyJsonArrayLength(5);
    }

    /**
     * @covers ::assertResponseBodyJsonArrayLength
     * @covers ::getResponseBodyArray
     */
    public function testThrowsExceptionWhenAssertingTheLengthOfAJsonArrayInTheResponseBodyAndTheBodyDoesNotContainAnArray() : void {
        $this->mockHandler->append(new Response(200, [], json_encode(['foo' => 'bar'])));
        $this->context->requestPath('/some/path');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The response body does not contain a valid JSON array.');
        $this->context->assertResponseBodyJsonArrayLength(0);
    }

    /**
     * @covers ::assertResponseBodyJsonArrayMinLength
     * @covers ::getResponseBody
     */
    public function testAssertingThatTheResponseBodyContainsAJsonArrayWithAMinimumLengthCanFail() : void {
        $this->mockHandler->append(new Response(200, [], json_encode([1, 2, 3])));
        $this->context->requestPath('/some/path');
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Expected response body to be a JSON array with at least 4 entries, got 3: "[');
        $this->context->assertResponseBodyJsonArrayMinLength(4);
    }

    /**
     * @covers ::assertResponseBodyJsonArrayMinLength
     * @covers ::requireResponse
     */
    public function testThrowsExceptionWhenAssertingTheMinimumLengthOfAnArrayInTheResponseBodyWhenNoResponseExist() : void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The request has not been made yet, so no response object exists.');
        $this->context->assertResponseBodyJsonArrayMinLength(5);
    }

    /**
     * @covers ::assertResponseBodyJsonArrayMinLength
     * @covers ::getResponseBody
     */
    public function testThrowsExceptionWhenAssertingTheMinimumLengthOfAnArrayInTheResponseBodyAndTheBodyDoesNotContainAnArray() : void {
        $this->mockHandler->append(new Response(200, [], json_encode(['foo' => 'bar'])));
        $this->context->requestPath('/some/path');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The response body does not contain a valid JSON array.');
        $this->context->assertResponseBodyJsonArrayMinLength(2);
    }

    /**
     * @covers ::assertResponseBodyJsonArrayMaxLength
     * @covers ::getResponseBody
     */
    public function testAssertingThatTheResponseBodyContainsAJsonArrayWithAMaximumLengthCanFail() : void {
        $this->mockHandler->append(new Response(200, [], json_encode([1, 2, 3])));
        $this->context->requestPath('/some/path');
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Expected response body to be a JSON array with at most 2 entries, got 3: "[');
        $this->context->assertResponseBodyJsonArrayMaxLength(2);
    }

    /**
     * @covers ::assertResponseBodyJsonArrayMaxLength
     * @covers ::requireResponse
     */
    public function testThrowsExceptionWhenAssertingTheMaximumLengthOfAnArrayInTheResponseBodyWhenNoResponseExist() : void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The request has not been made yet, so no response object exists.');
        $this->context->assertResponseBodyJsonArrayMaxLength(5);
    }

    /**
     * @covers ::assertResponseBodyJsonArrayMaxLength
     * @covers ::getResponseBody
     */
    public function testThrowsExceptionWhenAssertingTheMaximumLengthOfAnArrayInTheResponseBodyAndTheBodyDoesNotContainAnArray() : void {
        $this->mockHandler->append(new Response(200, [], json_encode(['foo' => 'bar'])));
        $this->context->requestPath('/some/path');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The response body does not contain a valid JSON array.');
        $this->context->assertResponseBodyJsonArrayMaxLength(2);
    }

    /**
     * @covers ::assertResponseBodyContainsJson
     * @covers ::getResponseBody
     */
    public function testAssertingThatTheResponseBodyContainsJsonCanFail() : void {
        $this->mockHandler->append(new Response(200, [], '{"foo":"bar"}'));
        $this->context->requestPath('/some/path');
        $this->comparator
            ->expects($this->once())
            ->method('compare')
            ->with(['bar' => 'foo'], ['foo' => 'bar'])
            ->will($this->throwException(new OutOfRangeException('error message')));

        $this->expectException(OutOfRangeException::class);
        $this->expectExceptionMessage('error message');
        $this->context->assertResponseBodyContainsJson(new PyStringNode(['{"bar":"foo"}'], 1));
    }

    /**
     * @covers ::setArrayContainsComparator
     * @covers ::assertResponseBodyContainsJson
     * @covers ::getResponseBody
     */
    public function testWillThrowExceptionWhenArrayContainsComparatorDoesNotReturnInACorrectMannerWhenCheckingTheResponseBodyForJson() : void {
        $this->mockHandler->append(new Response(200, [], '{"foo":"bar"}'));
        $this->context->requestPath('/some/path');
        $this->comparator
            ->expects($this->once())
            ->method('compare')
            ->with(['bar' => 'foo'], ['foo' => 'bar'])
            ->will($this->returnValue(false));

        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Comparator did not return in a correct manner. Marking assertion as failed.');
        $this->context->assertResponseBodyContainsJson(new PyStringNode(['{"bar":"foo"}'], 1));
    }

    /**
     * @covers ::assertResponseBodyContainsJson
     * @covers ::requireResponse
     */
    public function testThrowsExceptionWhenAssertingThatTheResponseContainsJsonAndNoResponseExist() : void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The request has not been made yet, so no response object exists.');
        $this->context->assertResponseBodyContainsJson(new PyStringNode(['{"foo":"bar"}'], 1));
    }

    /**
     * @covers ::assertResponseBodyContainsJson
     * @covers ::getResponseBody
     */
    public function testThrowsExceptionWhenAssertingThatTheResponseContainsJsonAndTheResponseContainsInvalidData() : void {
        $this->mockHandler->append(new Response(200, [], "{'foo':'bar'}"));
        $this->context->requestPath('/some/path');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The response body does not contain valid JSON data.');
        $this->context->assertResponseBodyContainsJson(new PyStringNode(['{"foo":"bar"}'], 1));
    }

    /**
     * @covers ::assertResponseBodyContainsJson
     * @covers ::jsonDecode
     */
    public function testThrowsExceptionWhenAssertingThatTheBodyContainsJsonAndTheParameterFromTheTestIsInvalid() : void {
        $this->mockHandler->append(new Response(200, [], '{"foo":"bar"}'));
        $this->context->requestPath('/some/path');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The supplied parameter is not a valid JSON object.');
        $this->context->assertResponseBodyContainsJson(new PyStringNode(["{'foo':'bar'}"], 1));
    }

    /**
     * @covers ::requestPath
     * @covers ::setRequestMethod
     * @see https://github.com/imbo/behat-api-extension/issues/51
     */
    public function testUsesHttpGetByDefaultWhenRequesting() : void {
        $this->mockHandler->append(new Response(200), new Response(200));

        $this->context->requestPath('/some/path', 'POST');
        $this->context->requestPath('/some/path');

        $this->assertSame(2, count($this->historyContainer));

        $this->assertSame(
            'POST',
            $this->historyContainer[0]['request']->getMethod(),
            'Expected first request to use HTTP POST'
        );
        $this->assertSame(
            'GET',
            $this->historyContainer[1]['request']->getMethod(),
            'Expected second request to use HTTP GET (default)'
        );
    }
}
