<?php declare(strict_types=1);
namespace Imbo\BehatApiExtension\Context;

use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Imbo\BehatApiExtension\ArrayContainsComparator;
use Imbo\BehatApiExtension\ArrayContainsComparator\Matcher\JWT;
use Imbo\BehatApiExtension\Exception\AssertionFailedException;
use InvalidArgumentException;
use OutOfRangeException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

/**
 * Namespaced version of file_exists that returns true for a fixed filename. All other paths are
 * checked by the global file_exists function.
 */
function file_exists(string $path): bool
{
    if ($path === '/non/readable/file') {
        return true;
    }

    return \file_exists($path);
}

/**
 * Namespaced version of is_readable that returns false for a fixed filename. All other paths are
 * checked by the global is_readable function.
 */
function is_readable(string $path): bool
{
    if ($path === '/none/readable/file') {
        return false;
    }

    return \is_readable($path);
}

#[CoversClass(ApiContext::class)]
class ApiContextTest extends TestCase
{
    private ApiContext $context;
    private MockHandler $mockHandler;
    private HandlerStack $handlerStack;
    private MockObject&ArrayContainsComparator $comparator;
    /** @var array<array{request:Request,response:Response}> */
    private array $historyContainer = [];

    public function setUp(): void
    {
        $this->historyContainer = [];

        $this->mockHandler = new MockHandler();
        $this->handlerStack = HandlerStack::create($this->mockHandler);
        $this->handlerStack->push(Middleware::history($this->historyContainer));
        $this->comparator = $this->createMock(ArrayContainsComparator::class);

        $this->context = new ApiContext();
        $this->context->initializeClient([
            'handler' => $this->handlerStack,
            'base_uri' => 'http://localhost:9876',
        ]);
        $this->assertSame($this->context, $this->context->setArrayContainsComparator($this->comparator));
    }

    /**
     * @return array<array{method:string}>
     */
    public static function getHttpMethods(): array
    {
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
     * @return array<array{code:int,others:array<int>}>
     */
    public static function getResponseCodes(): array
    {
        return [
            [
                'code' => 200,
            ],
            [
                'code' => 300,
            ],
            [
                'code' => 400,
            ],
            [
                'code' => 500,
            ],
        ];
    }

    /**
     * @return array<array{group:string,codes:array<int>}>
     */
    public static function getGroupAndResponseCodes(): array
    {
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
     * @return array<array<int>>
     */
    public static function getInvalidHttpResponseCodes(): array
    {
        return [
            [99],
            [600],
        ];
    }

    /**
     * @return array<array{body:array<int>,lengthToUse:int,willFail:bool}>
     */
    public static function getResponseBodyArrays(): array
    {
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
     * @return array<array{body:array<int>,min:int}>
     */
    public static function getResponseBodyArraysForAtLeast(): array
    {
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
     * @return array<array{body:array<int>,max:int}>
     */
    public static function getResponseBodyArraysForAtMost(): array
    {
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
     * @return array<array{code:int,phrase:string}>
     */
    public static function getResponseCodesAndReasonPhrases(): array
    {
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
     * @return array<array{baseUri:string,path:string,fullUri:string}>
     */
    public static function getUris(): array
    {
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
     * @return array<array{responseCode:int,actualGroup:string,expectedGroup:string}>
     */
    public static function getDataForResponseGroupFailures(): array
    {
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
     * @return array<array{data:string|PyStringNode,expected:string}>
     */
    public static function getRequestBodyValues(): array
    {
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
     * @return array<array{httpMethod:string}>
     */
    public static function getHttpMethodsForFormParametersTest(): array
    {
        return [
            ['httpMethod' => 'PUT'],
            ['httpMethod' => 'POST'],
            ['httpMethod' => 'PATCH'],
            ['httpMethod' => 'DELETE'],
        ];
    }

    public function testCanSetRequestHeaders(): void
    {
        $this->mockHandler->append(new Response(200));

        $this->assertSame(
            $this->context,
            $this->context
            ->setRequestHeader('foo', 'foo')
            ->setRequestHeader('bar', 'foo')
            ->setRequestHeader('bar', 'bar'),
        );
        $this->context->requestPath('/some/path', 'POST');
        $this->assertCount(1, $this->historyContainer);

        $request = $this->historyContainer[0]['request'];
        $this->assertSame('foo', $request->getHeaderLine('foo'));
        $this->assertSame('bar', $request->getHeaderLine('bar'));
    }

    public function testCanAddRequestHeaders(): void
    {
        $this->mockHandler->append(new Response(200));

        $this->assertSame(
            $this->context,
            $this->context
            ->addRequestHeader('foo', 'foo')
            ->addRequestHeader('bar', 'foo')
            ->addRequestHeader('bar', 'bar'),
        );
        $this->context->requestPath('/some/path', 'POST');
        $this->assertCount(1, $this->historyContainer);

        $request = $this->historyContainer[0]['request'];
        $this->assertSame('foo', $request->getHeaderLine('foo'));
        $this->assertSame('foo, bar', $request->getHeaderLine('bar'));
    }

    public function testSupportBasicHttpAuthentication(): void
    {
        $this->mockHandler->append(new Response(200));

        $username = 'user';
        $password = 'pass';

        $this->assertSame($this->context, $this->context->setBasicAuth($username, $password));
        $this->context->requestPath('/some/path', 'POST');

        $this->assertCount(1, $this->historyContainer);

        $request = $this->historyContainer[0]['request'];
        $this->assertSame('Basic dXNlcjpwYXNz', $request->getHeaderLine('authorization'));
    }

    public function testSupportOAuthWithPasswordGrant(): void
    {
        $this->mockHandler->append(new Response(200, [], '{"access_token": "some_access_token"}'));
        $this->mockHandler->append(new Response(200));

        $path         = '/some/path';
        $username     = 'user';
        $password     = 'pass';
        $scope        = 'scope';
        $clientId     = 'client id';
        $clientSecret = 'client secret';

        $this->assertSame(
            $this->context,
            $this->context->oauthWithPasswordGrantInScope(
                $path,
                $username,
                $password,
                $scope,
                $clientId,
                $clientSecret,
            ),
        );
        $this->assertCount(1, $this->historyContainer);

        parse_str($this->historyContainer[0]['request']->getBody()->getContents(), $requestBody);

        $this->assertSame('password', $requestBody['grant_type'], 'Incorrect grant type');
        $this->assertSame($username, $requestBody['username'], 'Incorrect username');
        $this->assertSame($password, $requestBody['password'], 'Incorrect password');
        $this->assertSame($scope, $requestBody['scope'], 'Incorrect scope');
        $this->assertSame($clientId, $requestBody['client_id'], 'Incorrect client ID');
        $this->assertSame($clientSecret, $requestBody['client_secret'], 'Incorrect client secret');

        // Create new request with Authorization header
        $this->context->requestPath('/some/path', 'POST');
        $this->assertCount(2, $this->historyContainer);
        $request = $this->historyContainer[1]['request'];
        $this->assertSame('Bearer some_access_token', $request->getHeaderLine('authorization'));
    }

    public function testThrowsExceptionWhenOauthAccessTokenRequestFails(): void
    {
        $this->mockHandler->append(new Response(401, [], '{"error": "some_error"}'));
        $this->expectExceptionObject(new RuntimeException(
            'Expected request for access token to pass, got status code 401 with the following response: {"error": "some_error"}',
        ));
        $this->context->oauthWithPasswordGrantInScope('/path', 'username', 'password', 'scope', 'client_id', 'client_secret');
    }

    public function testThrowsExceptionWhenOauthAccessTokenIsMissingFromResponse(): void
    {
        $this->mockHandler->append(new Response(200, [], '{"foo": "bar"}'));
        $this->expectExceptionObject(new RuntimeException(
            'Missing access_token from response body: {"foo":"bar"}',
        ));
        $this->context->oauthWithPasswordGrantInScope('/path', 'username', 'password', 'scope', 'client_id', 'client_secret');
    }

    public function testCanAddMultipleMultipartFilesToTheRequest(): void
    {
        $this->mockHandler->append(new Response(200));
        $files = [
            'file1' => __FILE__,
            'file2' => __DIR__ . '/../../README.md',
        ];

        foreach ($files as $name => $path) {
            $this->assertSame($this->context, $this->context->addMultipartFileToRequest($path, $name));
        }

        $this->context->requestPath('/some/path', 'POST');

        $this->assertCount(1, $this->historyContainer);

        $request = $this->historyContainer[0]['request'];

        /** @var MultipartStream */
        $requestBody = $request->getBody();
        $boundary = $requestBody->getBoundary();

        $this->assertSame(sprintf('multipart/form-data; boundary=%s', $boundary), $request->getHeaderLine('Content-Type'));
        $contents = $requestBody->getContents();

        foreach ($files as $path) {
            $this->assertStringContainsString(
                (string) file_get_contents($path),
                $contents,
            );
        }
    }

    public function testCanAddMultipartFormDataParametersToTheRequest(): void
    {
        $this->mockHandler->append(new Response(200));
        $this->assertSame($this->context, $this->context->setRequestMultipartFormParams(new TableNode([
            ['name', 'value'],
            ['foo', 'bar'],
            ['bar', 'foo'],
            ['bar', 'bar'],
        ])));

        $this->context->requestPath('/some/path', 'POST');

        $this->assertCount(1, $this->historyContainer);

        $request = $this->historyContainer[0]['request'];

        /** @var MultipartStream */
        $requestBody = $request->getBody();
        $boundary = $requestBody->getBoundary();

        $this->assertSame(sprintf('multipart/form-data; boundary=%s', $boundary), $request->getHeaderLine('Content-Type'));
    }

    public function testCanSetFormParametersInTheRequest(): void
    {
        $this->mockHandler->append(new Response(200));
        $this->assertSame($this->context, $this->context->setRequestFormParams(new TableNode([
            ['name', 'value'],
            ['foo', 'bar'],
            ['bar', 'foo'],
            ['bar', 'bar'],
        ])));
        $this->context->requestPath('/some/path');

        $this->assertCount(1, $this->historyContainer);

        $request = $this->historyContainer[0]['request'];

        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('application/x-www-form-urlencoded', $request->getHeaderLine('Content-Type'));
        $this->assertSame(37, (int) $request->getHeaderLine('Content-Length'));
        $this->assertSame('foo=bar&bar%5B0%5D=foo&bar%5B1%5D=bar', (string) $request->getBody());
    }

    #[DataProvider('getHttpMethodsForFormParametersTest')]
    public function testCanSetFormParametersInTheRequestWithCustomMethod(string $httpMethod): void
    {
        $this->mockHandler->append(new Response(200));
        $this->assertSame($this->context, $this->context->setRequestFormParams(new TableNode([
            ['name', 'value'],
            ['foo', 'bar'],
            ['bar', 'foo'],
            ['bar', 'bar'],
        ])));
        $this->context->requestPath('/some/path', $httpMethod);

        $this->assertCount(1, $this->historyContainer);

        $request = $this->historyContainer[0]['request'];

        $this->assertSame($httpMethod, $request->getMethod());
        $this->assertSame('application/x-www-form-urlencoded', $request->getHeaderLine('Content-Type'));
        $this->assertSame(37, (int) $request->getHeaderLine('Content-Length'));
        $this->assertSame('foo=bar&bar%5B0%5D=foo&bar%5B1%5D=bar', (string) $request->getBody());
    }

    public function testCanSetFormParametersAndAttachAFileInTheSameMultipartRequest(): void
    {
        $this->mockHandler->append(new Response(200));
        $this->context->setRequestFormParams(new TableNode([
            ['name', 'value'],
            ['foo', 'bar'],
            ['bar', 'foo'],
            ['bar', 'bar'],
        ]));
        $this->context->addMultipartFileToRequest(__FILE__, 'file');
        $this->context->requestPath('/some/path');

        $this->assertCount(1, $this->historyContainer);

        $request = $this->historyContainer[0]['request'];

        /** @var MultipartStream */
        $requestBody = $request->getBody();

        $boundary = $requestBody->getBoundary();

        $this->assertSame('POST', $request->getMethod());
        $this->assertSame(sprintf('multipart/form-data; boundary=%s', $boundary), $request->getHeaderLine('Content-Type'));

        $contents = $requestBody->getContents();

        $this->assertStringContainsString('Content-Disposition: form-data; name="file"; filename="ApiContextTest.php"', $contents);
        $this->assertStringContainsString((string) file_get_contents(__FILE__), $contents);

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

    public function testThrowsExceptionWhenAddingNonExistingFileAsMultipartPartToTheRequest(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('File does not exist: "/foo/bar"');
        $this->context->addMultipartFileToRequest('/foo/bar', 'foo');
    }

    public function testCanSetRequestBodyToAFile(): void
    {
        $this->mockHandler->append(new Response());
        $this->assertSame($this->context, $this->context->setRequestBodyToFileResource(__FILE__));
        $this->context->requestPath('/some/path', 'POST');

        $this->assertCount(1, $this->historyContainer);
        $request = $this->historyContainer[0]['request'];
        $this->assertSame(file_get_contents(__FILE__), (string) $request->getBody());
        $this->assertSame('text/x-php', $request->getHeaderLine('Content-Type'));
    }

    #[DataProvider('getRequestBodyValues')]
    public function testCanSetRequestBodyToAString(string|PyStringNode $data, string $expected): void
    {
        $this->mockHandler->append(new Response());
        $this->context->setRequestBody($data);
        $this->context->requestPath('/some/path', 'POST');

        $this->assertCount(1, $this->historyContainer);
        $request = $this->historyContainer[0]['request'];
        $this->assertSame($expected, (string) $request->getBody());
    }

    #[DataProvider('getUris')]
    public function testResolvesFilePathsCorrectlyWhenAttachingFilesToTheRequestBody(string $baseUri, string $path, string $fullUri): void
    {
        // Set a new client with the given base_uri (and not the one used in setUp())
        $this->assertSame($this->context, $this->context->initializeClient([
            'handler' => $this->handlerStack,
            'base_uri' => $baseUri,
        ]));

        $this->mockHandler->append(new Response());
        $this->assertSame($this->context, $this->context->setRequestBodyToFileResource(__FILE__));
        $this->context->requestPath($path);

        $this->assertCount(1, $this->historyContainer);
        $request = $this->historyContainer[0]['request'];
        $this->assertSame($fullUri, (string) $request->getUri());
    }

    public function testCanAddJwtTokensToTheJwtMatcher(): void
    {
        $name = 'some name';
        $payload = ['some' => 'data'];
        $secret = 'secret';

        /** @var MockObject&JWT */
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
            'Expected method to return own instance',
        );
    }

    public function testThrowsExceptionWhenTryingToAddJwtTokenWhenThereIsNoMatcherFunctionRegistered(): void
    {
        $this->comparator
            ->expects($this->once())
            ->method('getMatcherFunction')
            ->with('jwt')
            ->willReturn('json_encode');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Matcher registered for the @jwt() matcher function must be an instance of Imbo\BehatApiExtension\ArrayContainsComparator\Matcher\JWT');

        $this->context->addJwtToken('name', 'secret', new PyStringNode(['{"some":"data"}'], 1));
    }

    #[DataProvider('getHttpMethods')]
    public function testCanMakeRequestsUsingDifferentHttpMethods(string $method): void
    {
        $this->mockHandler->append(new Response(200));
        $this->assertSame($this->context, $this->context->requestPath('/some/path', $method));

        $this->assertCount(1, $this->historyContainer);
        $this->assertSame($method, $this->historyContainer[0]['request']->getMethod());
    }

    public function testCanMakeRequestsWithQueryStringInThePath(): void
    {
        $this->mockHandler->append(new Response(200));
        $this->assertSame(
            $this->context,
            $this->context->requestPath('/some/path?foo=bar&bar=foo&a[]=1&a[]=2'),
        );

        $this->assertCount(1, $this->historyContainer);

        $request = $this->historyContainer[0]['request'];

        $this->assertSame('foo=bar&bar=foo&a%5B%5D=1&a%5B%5D=2', $request->getUri()->getQuery());
    }

    public function testCanSetQueryStringParameters(): void
    {
        $this->mockHandler->append(new Response(200));
        $this->assertSame(
            $this->context,
            $this->context
                ->setQueryStringParameter('p1', 'v1')
                ->setQueryStringParameter('p2', 'v2')
                ->setQueryStringParameter('p3', 'v3')
                ->setQueryStringParameter('p4', 'v4')
                ->setQueryStringParameter('p4', 'v5')
                ->setQueryStringParameter('p1', new TableNode([
                    ['value'],
                    ['v6'],
                    ['v7'],
                ]))
                ->setQueryStringParameter('p2', new TableNode([
                    ['value'],
                    ['v8'],
                ]))
                ->setQueryStringParameters(new TableNode([
                    ['name', 'value'],
                    ['p3', 'v9'],
                    ['p5', 'v10'],
                ])),
        );
        $this->assertSame(
            $this->context,
            $this->context->requestPath('/some/path?wut=wat'),
        );

        $this->assertCount(1, $this->historyContainer);

        $request = $this->historyContainer[0]['request'];

        $this->assertSame('p1%5B0%5D=v6&p1%5B1%5D=v7&p2%5B0%5D=v8&p3=v9&p4=v5&p5=v10', $request->getUri()->getQuery());
    }

    #[DataProvider('getResponseCodes')]
    public function testCanAssertWhatTheResponseCodeIs(int $code): void
    {
        $this->mockHandler->append(new Response($code));
        $this->context->requestPath('/some/path');
        $this->assertTrue($this->context->assertResponseCodeIs($code));
    }

    public function testCanAssertWhatTheResponseCodeIsNot(): void
    {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath('/some/path');
        $this->assertTrue($this->context->assertResponseCodeIsNot(201));
    }

    /**
     * @param array<int> $codes
     */
    #[DataProvider('getGroupAndResponseCodes')]
    public function testCanAssertWhichGroupTheResponseIsIn(string $group, array $codes): void
    {
        foreach ($codes as $code) {
            $this->mockHandler->append(new Response($code, [], 'response body'));
            $this->context->requestPath('/some/path');
            $this->assertTrue($this->context->assertResponseIs($group));
        }
    }

    /**
     * @param array<int> $codes
     */
    #[DataProvider('getGroupAndResponseCodes')]
    public function testCanAssertWhichGroupTheResponseIsNotIn(string $group, array $codes): void
    {
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

            foreach (array_filter($groups, fn (string $g): bool => $g !== $group) as $g) {
                // Assert that the response is not in any of the other groups
                $this->assertTrue($this->context->assertResponseIsNot($g));
            }
        }
    }

    #[DataProvider('getResponseCodesAndReasonPhrases')]
    public function testCanAssertWhatTheResponseReasonPhraseIs(int $code, string $phrase): void
    {
        $this->mockHandler->append(new Response($code, [], null, '1.1', $phrase));
        $this->context->requestPath('/some/path');
        $this->assertTrue($this->context->assertResponseReasonPhraseIs($phrase));
    }

    public function testCanAssertWhatTheResponseReasonPhraseIsNot(): void
    {
        $this->mockHandler->append(new Response());
        $this->context->requestPath('/some/path');
        $this->assertTrue($this->context->assertResponseReasonPhraseIsNot('Not Modified'));
    }

    public function testCanAssertThatTheResponseReasonPhraseMatchesAnExpression(): void
    {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath('/some/path');
        $this->assertTrue($this->context->assertResponseReasonPhraseMatches('/OK/'));
    }

    #[DataProvider('getResponseCodesAndReasonPhrases')]
    public function testCanAssertWhatTheResponseStatusLineIs(int $code, string $phrase): void
    {
        $this->mockHandler->append(new Response($code, [], null, '1.1', $phrase));
        $this->context->requestPath('/some/path');
        $this->assertTrue($this->context->assertResponseStatusLineIs(sprintf('%d %s', $code, $phrase)));
    }

    public function testCanAssertWhatTheResponseStatusLineIsNot(): void
    {
        $this->mockHandler->append(new Response());
        $this->context->requestPath('/some/path');
        $this->assertTrue($this->context->assertResponseStatusLineIsNot('304 Not Modified'));
    }

    public function testCanAssertThatTheResponseStatusLineMatchesAnExpression(): void
    {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath('/some/path');
        $this->assertTrue($this->context->assertResponseStatusLineMatches('/200 OK/'));
    }

    public function testCanAssertThatAResponseHeaderExists(): void
    {
        $this->mockHandler->append(new Response(200, ['Content-Type' => 'application/json']));
        $this->context->requestPath('/some/path');
        $this->assertTrue($this->context->assertResponseHeaderExists('Content-Type'));
    }

    public function testCanAssertThatAResponseHeaderDoesNotExist(): void
    {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath('/some/path');
        $this->assertTrue($this->context->assertResponseHeaderDoesNotExist('Content-Type'));
    }

    public function testCanAssertWhatAResponseHeaderIs(): void
    {
        $this->mockHandler->append(new Response(200, ['Content-Type' => 'application/json']));
        $this->context->requestPath('/some/path');
        $this->assertTrue($this->context->assertResponseHeaderIs('Content-Type', 'application/json'));
    }

    public function testCanAssertWhatAResponseHeaderIsNot(): void
    {
        $this->mockHandler->append(new Response(200, ['Content-Length' => '123']));
        $this->context->requestPath('/some/path');
        $this->assertTrue($this->context->assertResponseHeaderIsNot('Content-Type', '456'));
    }

    public function testCanAssertThatAResponseHeaderMatchesAnExpression(): void
    {
        $this->mockHandler->append(new Response(200, ['Content-Type' => 'application/json']));
        $this->context->requestPath('/some/path');
        $this->assertTrue($this->context->assertResponseHeaderMatches('Content-Type', '#^application/(json|xml)$#'));
    }

    public function testCanAssertWhatTheResponseBodyIs(): void
    {
        $this->mockHandler->append(new Response(200, [], 'response body'));
        $this->context->requestPath('/some/path');
        $this->assertTrue($this->context->assertResponseBodyIs(new PyStringNode(['response body'], 1)));
    }

    public function testCanAssertWhatTheResponseBodyIsNot(): void
    {
        $this->mockHandler->append(new Response(200, [], 'response body'));
        $this->context->requestPath('/some/path');
        $this->assertTrue($this->context->assertResponseBodyIsNot(new PyStringNode(['some other response body'], 1)));
    }

    public function testCanAssertThatTheResponseBodyMatchesAnExpression(): void
    {
        $this->mockHandler->append(new Response(200, [], '{"foo":"bar"}'));
        $this->context->requestPath('/some/path');
        $this->assertTrue($this->context->assertResponseBodyMatches(new PyStringNode(['/^{"FOO": ?"BAR"}$/i'], 1)));
    }

    public function testCanAssertThatTheResponseIsAnEmptyArray(): void
    {
        $this->mockHandler->append(new Response(200, [], (string) json_encode([])));
        $this->context->requestPath('/some/path');
        $this->assertTrue($this->context->assertResponseBodyIsAnEmptyJsonArray());
    }

    public function testCanAssertThatTheResponseIsAnEmptyObject(): void
    {
        $this->mockHandler->append(new Response(200, [], (string) json_encode(new stdClass())));
        $this->context->requestPath('/some/path');
        $this->assertTrue($this->context->assertResponseBodyIsAnEmptyJsonObject());
    }

    public function testCanAssertThatTheResponseBodyIsEmpty(): void
    {
        $this->mockHandler->append(new Response(204));
        $this->context->requestPath('/some/path');
        $this->assertTrue($this->context->assertResponseBodyIsEmpty());
    }

    public function testCanAssertThatTheResponseBodyIsEmptyCanFail(): void
    {
        $this->mockHandler->append(new Response(200, [], 'some content'));
        $this->context->requestPath('/some/path');
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Expected response body to be empty, got "some content".');
        $this->assertTrue($this->context->assertResponseBodyIsEmpty());
    }

    public function testAssertThatTheResponseBodyIsEmptyThrowsExceptionOnMissingResponse(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The request has not been made yet, so no response object exists.');
        $this->context->assertResponseBodyIsEmpty();
    }

    /**
     * @param array<int> $body
     */
    #[DataProvider('getResponseBodyArrays')]
    public function testCanAssertThatTheArrayInTheResponseBodyHasACertainLength(array $body, int $lengthToUse, bool $willFail): void
    {
        $this->mockHandler->append(new Response(200, [], (string) json_encode($body)));
        $this->context->requestPath('/some/path');

        if ($willFail) {
            $this->expectException(AssertionFailedException::class);
            $this->expectExceptionMessage(sprintf(
                'Expected response body to be a JSON array with %d entr%s, got %d: "[',
                $lengthToUse,
                $lengthToUse === 1 ? 'y' : 'ies',
                count($body),
            ));
        }

        $this->assertTrue($this->context->assertResponseBodyJsonArrayLength($lengthToUse));
    }

    /**
     * @param array<int> $body
     */
    #[DataProvider('getResponseBodyArraysForAtLeast')]
    public function testCanAssertTheMinLengthOfAnArrayInTheResponseBody(array $body, int $min): void
    {
        $this->mockHandler->append(new Response(200, [], (string) json_encode($body)));
        $this->context->requestPath('/some/path');
        $this->assertTrue($this->context->assertResponseBodyJsonArrayMinLength($min));
    }

    /**
     * @param array<int> $body
     */
    #[DataProvider('getResponseBodyArraysForAtMost')]
    public function testCanAssertTheMaxLengthOfAnArrayInTheResponseBody(array $body, int $max): void
    {
        $this->mockHandler->append(new Response(200, [], (string) json_encode($body)));
        $this->context->requestPath('/some/path');
        $this->assertTrue($this->context->assertResponseBodyJsonArrayMaxLength($max));
    }

    public function testCanAssertThatTheResponseBodyContainsJson(): void
    {
        $this->mockHandler->append(new Response(200, [], '{"foo":"bar","bar":"foo"}'));
        $this->context->requestPath('/some/path');
        $this->comparator
            ->expects($this->once())
            ->method('compare')
            ->with(['bar' => 'foo', 'foo' => 'bar'], ['foo' => 'bar', 'bar' => 'foo'])
            ->willReturn(true);

        $this->assertTrue($this->context->assertResponseBodyContainsJson(new PyStringNode(['{"bar":"foo","foo":"bar"}'], 1)));
    }

    /**
     * @see https://github.com/imbo/behat-api-extension/issues/7
     */
    public function testThrowsExceptionWhenTryingToCombineARequestBodyWithMultipartOrFormData(): void
    {
        $this->mockHandler->append(new Response(200));
        $this->context->addMultipartFileToRequest(__FILE__, 'file');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('It\'s not allowed to set a request body when using multipart/form-data or form parameters.');
        $this->context->setRequestBody('some body');
    }

    public function testThrowsExceptionWhenAttachingANonExistingFileToTheRequestBody(): void
    {
        $this->mockHandler->append(new Response());
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('File does not exist: "/foo/bar"');
        $this->context->setRequestBodyToFileResource('/foo/bar');
    }

    public function testThrowsExceptionWhenAttachingANonReadableFileToTheRequestBody(): void
    {
        $this->mockHandler->append(new Response());
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('File is not readable: "/non/readable/file"');
        $this->context->setRequestBodyToFileResource('/non/readable/file');
    }

    public function testThrowsExceptionWhenTheRequestCanNotBeSent(): void
    {
        $this->mockHandler->append(new RequestException('error', new Request('GET', 'path')));
        $this->expectException(RequestException::class);
        $this->expectExceptionMessage('error');
        $this->context->requestPath('path');
    }

    public function testAssertingWhatTheResponseCodeIsCanFail(): void
    {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath('/some/path');
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Expected response code 400, got 200');
        $this->context->assertResponseCodeIs(400);
    }

    #[DataProvider('getInvalidHttpResponseCodes')]
    public function testThrowsExceptionWhenSpecifyingAnInvalidCodeWhenAssertingWhatTheResponseCodeIs(int $code): void
    {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath('/some/path');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Response code must be between 100 and 599, got %d.', $code));

        $this->context->assertResponseCodeIs($code);
    }

    public function testThrowsExceptionWhenAssertingWhatTheResponseCodeIsWhenNoResponseExists(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The request has not been made yet, so no response object exists.');
        $this->context->assertResponseCodeIs(200);
    }

    public function testAssertingWhatTheResponseCodeIsNotCanFail(): void
    {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath('/some/path');
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Did not expect response code 200');
        $this->context->assertResponseCodeIsNot(200);
    }

    #[DataProvider('getInvalidHttpResponseCodes')]
    public function testThrowsExceptionWhenSpecifyingAnInvalidCodeWhenAssertingWhatTheResponseCodeIsNot(int $code): void
    {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath('/some/path');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Response code must be between 100 and 599, got %d.', $code));

        $this->context->assertResponseCodeIsNot($code);
    }

    public function testThrowsExceptionWhenAssertingWhatTheResponseCodeIsNotWhenNoResponseExists(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The request has not been made yet, so no response object exists.');
        $this->context->assertResponseCodeIsNot(200);
    }

    #[DataProvider('getDataForResponseGroupFailures')]
    public function testAssertingThatTheResponseIsInAGroupCanFail(int $responseCode, string $actualGroup, string $expectedGroup): void
    {
        $this->mockHandler->append(new Response($responseCode));
        $this->context->requestPath('/some/path');

        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage(sprintf(
            'Expected response group "%s", got "%s" (response code: %d).',
            $expectedGroup,
            $actualGroup,
            $responseCode,
        ));
        $this->context->assertResponseIs($expectedGroup);
    }

    public function testThrowsExceptionWhenAssertingWhichGroupTheResponseIsInWhenNoResponseExists(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The request has not been made yet, so no response object exists.');
        $this->context->assertResponseIs('success');
    }

    public function testThrowsExceptionWhenAssertingThatTheResponseIsInAnInvalidGroup(): void
    {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath('/some/path');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid response code group: foobar');
        $this->context->assertResponseIs('foobar');
    }

    public function testAssertingThatTheResponseIsNotInAGroupCanFail(): void
    {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath('/some/path');
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Did not expect response to be in the "success" group (response code: 200).');
        $this->context->assertResponseIsNot('success');
    }

    public function testThrowsExceptionWhenAssertingThatTheResponseIsNotInAnInvalidGroup(): void
    {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath('/some/path');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid response code group: foobar');
        $this->context->assertResponseIsNot('foobar');
    }

    public function testThrowsExceptionWhenAssertingWhichGroupTheResponseIsNotInWhenNoResponseExists(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The request has not been made yet, so no response object exists.');
        $this->context->assertResponseIsNot('success');
    }

    public function testAssertingWhatTheResponseReasonPhraseIsCanFail(): void
    {
        $this->mockHandler->append(new Response());
        $this->context->requestPath('/some/path');
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Expected response reason phrase "ok", got "OK".');
        $this->context->assertResponseReasonPhraseIs('ok');
    }

    public function testThrowsExceptionWhenAssertingWhatTheResponseReasonPhraseIsWhenNoResponseExist(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The request has not been made yet, so no response object exists.');
        $this->context->assertResponseReasonPhraseIs('OK');
    }

    public function testAssertingWhatTheResponseReasonPhraseIsNotCanFail(): void
    {
        $this->mockHandler->append(new Response());
        $this->context->requestPath('/some/path');
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Did not expect response reason phrase "OK".');
        $this->context->assertResponseReasonPhraseIsNot('OK');
    }

    public function testThrowsExceptionWhenAssertingWhatTheResponseReasonPhraseIsNotWhenNoResponseExist(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The request has not been made yet, so no response object exists.');
        $this->context->assertResponseReasonPhraseIsNot('OK');
    }

    public function testAssertingThatTheResponseReasonPhraseMatchesAnExpressionCanFail(): void
    {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath('/some/path');
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Expected the response reason phrase to match the regular expression "/ok/", got "OK".');
        $this->context->assertResponseReasonPhraseMatches('/ok/');
    }

    public function testThrowsExceptionWhenAssertingThatTheResponseReasonPhraseMatchesAnExpressionWhenThereIsNoResponse(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The request has not been made yet, so no response object exists.');
        $this->context->assertResponseReasonPhraseMatches('/ok/');
    }

    public function testAssertingWhatTheResponseStatusLineIsCanFail(): void
    {
        $this->mockHandler->append(new Response());
        $this->context->requestPath('/some/path');
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Expected response status line "200 Foobar", got "200 OK".');
        $this->context->assertResponseStatusLineIs('200 Foobar');
    }

    public function testThrowsExceptionWhenAssertingWhatTheResponseStatusLineIsWhenNoResponseExist(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The request has not been made yet, so no response object exists.');
        $this->context->assertResponseStatusLineIs('200 OK');
    }

    public function testAssertingWhatTheResponseStatusLineIsNotCanFail(): void
    {
        $this->mockHandler->append(new Response());
        $this->context->requestPath('/some/path');
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Did not expect response status line "200 OK".');
        $this->context->assertResponseStatusLineIsNot('200 OK');
    }

    public function testThrowsExceptionWhenAssertingWhatTheResponseStatusLineIsNotWhenNoResponseExist(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The request has not been made yet, so no response object exists.');
        $this->context->assertResponseStatusLineIsNot('200 OK');
    }

    public function testAssertingThatTheResponseStatusLineMatchesAnExpressionCanFail(): void
    {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath('/some/path');
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Expected the response status line to match the regular expression "/200 ok/", got "200 OK".');
        $this->context->assertResponseStatusLineMatches('/200 ok/');
    }

    public function testThrowsExceptionWhenAssertingThatTheResponseStatusLineMatchesAnExpressionWhenNoResponseExist(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The request has not been made yet, so no response object exists.');
        $this->context->assertResponseStatusLineMatches('/200 OK/');
    }

    public function testAssertingThatAResponseHeaderExistsCanFail(): void
    {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath('/some/path');
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('The "Content-Type" response header does not exist.');
        $this->context->assertResponseHeaderExists('Content-Type');
    }

    public function testThrowsExceptionWhenAssertingThatAResponseHeaderExistWhenNoResponseExist(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The request has not been made yet, so no response object exists.');
        $this->context->assertResponseHeaderExists('Connection');
    }

    public function testAssertingThatAResponseHeaderDoesNotExistCanFail(): void
    {
        $this->mockHandler->append(new Response(200, ['Content-Type' => 'application/json']));
        $this->context->requestPath('/some/path');
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('The "Content-Type" response header should not exist.');
        $this->context->assertResponseHeaderDoesNotExist('Content-Type');
    }

    public function testThrowsExceptionWhenAssertingThatAResponseHeaderDoesNotExistWhenNoResponseExist(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The request has not been made yet, so no response object exists.');
        $this->context->assertResponseHeaderDoesNotExist('Connection');
    }

    public function testAssertingWhatAResponseHeaderIsCanFail(): void
    {
        $this->mockHandler->append(new Response(200, ['Content-Type' => 'application/json']));
        $this->context->requestPath('/some/path');
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Expected the "Content-Type" response header to be "application/xml", got "application/json".');
        $this->context->assertResponseHeaderIs('Content-Type', 'application/xml');
    }

    public function testThrowsExceptionWhenAssertingWhatAResponseHeaderIsWhenNoResponseExist(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The request has not been made yet, so no response object exists.');
        $this->context->assertResponseHeaderIs('Connection', 'close');
    }

    public function testAssertingWhatAResponseHeaderIsNotCanFail(): void
    {
        $this->mockHandler->append(new Response(200, ['Content-Type' => '123']));
        $this->context->requestPath('/some/path');
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Did not expect the "content-type" response header to be "123".');
        $this->context->assertResponseHeaderIsNot('content-type', '123');
    }

    public function testThrowsExceptionWhenAssertingWhatAResponseHeaderIsNotWhenNoResponseExist(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The request has not been made yet, so no response object exists.');
        $this->context->assertResponseHeaderIsNot('header', 'value');
    }

    public function testAssertingThatAResponseHeaderMatchesAnExpressionCanFail(): void
    {
        $this->mockHandler->append(new Response(200, ['Content-Type' => 'application/json']));
        $this->context->requestPath('/some/path');
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Expected the "Content-Type" response header to match the regular expression "#^application/xml$#", got "application/json".');
        $this->context->assertResponseHeaderMatches('Content-Type', '#^application/xml$#');
    }

    public function testThrowsExceptionWhenAssertingThatAResponseHeaderMatchesAnExpressionWhenNoResponseExist(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The request has not been made yet, so no response object exists.');
        $this->context->assertResponseHeaderMatches('Connection', 'close');
    }

    public function testAssertingWhatTheResponseBodyIsCanFail(): void
    {
        $this->mockHandler->append(new Response(200, [], 'response body'));
        $this->context->requestPath('/some/path');
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Expected response body "foo", got "response body".');
        $this->context->assertResponseBodyIs(new PyStringNode(['foo'], 1));
    }

    public function testThrowsExceptionWhenAssertingWhatTheResponseBodyIsWhenNoResponseExist(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The request has not been made yet, so no response object exists.');
        $this->context->assertResponseBodyIs(new PyStringNode(['some body'], 1));
    }

    public function testAssertingWhatTheResponseBodyIsNotCanFail(): void
    {
        $this->mockHandler->append(new Response(200, [], 'response body'));
        $this->context->requestPath('/some/path');
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Did not expect response body to be "response body".');
        $this->context->assertResponseBodyIsNot(new PyStringNode(['response body'], 1));
    }

    public function testThrowsExceptionWhenAssertingWhatTheResponseBodyIsNotWhenNoResponseExist(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The request has not been made yet, so no response object exists.');
        $this->context->assertResponseBodyIsNot(new PyStringNode(['some body'], 1));
    }

    public function testAssertingThatTheResponseBodyMatchesAnExpressionCanFail(): void
    {
        $this->mockHandler->append(new Response(200, [], '{"foo":"bar"}'));
        $this->context->requestPath('/some/path');
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Expected response body to match regular expression "/^{"FOO": "BAR"}$/", got "{"foo":"bar"}".');
        $this->context->assertResponseBodyMatches(new PyStringNode(['/^{"FOO": "BAR"}$/'], 1));
    }

    public function testThrowsExceptionWhenAssertingThatTheResponseBodyMatchesAnExpressionWhenNoResponseExist(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The request has not been made yet, so no response object exists.');
        $this->context->assertResponseBodyMatches(new PyStringNode(['/foo/'], 1));
    }

    public function testAssertingThatTheResponseIsAnEmptyArrayCanFail(): void
    {
        $this->mockHandler->append(new Response(200, [], (string) json_encode([1, 2, 3])));
        $this->context->requestPath('/some/path');
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Expected response body to be an empty JSON array, got "[');
        $this->context->assertResponseBodyIsAnEmptyJsonArray();
    }

    public function testThrowsExceptionWhenAssertingThatTheResponseBodyIsAnEmptyArrayWhenTheBodyDoesNotContainAnArray(): void
    {
        $this->mockHandler->append(new Response(200, [], '123'));
        $this->context->requestPath('/some/path');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The response body does not contain a valid JSON array / object.');
        $this->context->assertResponseBodyIsAnEmptyJsonArray();
    }

    public function testAssertingThatTheResponseIsAnEmptyObjectCanFail(): void
    {
        $object = new stdClass();
        $object->foo = 'bar';
        $this->mockHandler->append(new Response(200, [], (string) json_encode($object)));
        $this->context->requestPath('/some/path');
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Expected response body to be an empty JSON object, got "{');
        $this->context->assertResponseBodyIsAnEmptyJsonObject();
    }

    public function testThrowsExceptionWhenAssertingThatTheResponseBodyIsAnEmptyObjectWhenTheBodyDoesNotContainAnObject(): void
    {
        $this->mockHandler->append(new Response(200, [], (string) json_encode([])));
        $this->context->requestPath('/some/path');
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Expected response body to be a JSON object.');
        $this->context->assertResponseBodyIsAnEmptyJsonObject();
    }

    public function testAssertingThatTheResponseBodyIsAJsonArrayWithACertainLengthCanFail(): void
    {
        $this->mockHandler->append(new Response(200, [], (string) json_encode([1, 2, 3])));
        $this->context->requestPath('/some/path');
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Expected response body to be a JSON array with 2 entries, got 3: "[');
        $this->context->assertResponseBodyJsonArrayLength(2);
    }

    public function testThrowsExceptionWhenAssertingTheLengthOfAJsonArrayInTheResponseBodyWhenNoResponseExist(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The request has not been made yet, so no response object exists.');
        $this->context->assertResponseBodyJsonArrayLength(5);
    }

    public function testThrowsExceptionWhenAssertingTheLengthOfAJsonArrayInTheResponseBodyAndTheBodyDoesNotContainAnArray(): void
    {
        $this->mockHandler->append(new Response(200, [], (string) json_encode(['foo' => 'bar'])));
        $this->context->requestPath('/some/path');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The response body does not contain a valid JSON array.');
        $this->context->assertResponseBodyJsonArrayLength(0);
    }

    public function testAssertingThatTheResponseBodyContainsAJsonArrayWithAMinimumLengthCanFail(): void
    {
        $this->mockHandler->append(new Response(200, [], (string) json_encode([1, 2, 3])));
        $this->context->requestPath('/some/path');
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Expected response body to be a JSON array with at least 4 entries, got 3: "[');
        $this->context->assertResponseBodyJsonArrayMinLength(4);
    }

    public function testThrowsExceptionWhenAssertingTheMinimumLengthOfAnArrayInTheResponseBodyWhenNoResponseExist(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The request has not been made yet, so no response object exists.');
        $this->context->assertResponseBodyJsonArrayMinLength(5);
    }

    public function testThrowsExceptionWhenAssertingTheMinimumLengthOfAnArrayInTheResponseBodyAndTheBodyDoesNotContainAnArray(): void
    {
        $this->mockHandler->append(new Response(200, [], (string) json_encode(['foo' => 'bar'])));
        $this->context->requestPath('/some/path');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The response body does not contain a valid JSON array.');
        $this->context->assertResponseBodyJsonArrayMinLength(2);
    }

    public function testAssertingThatTheResponseBodyContainsAJsonArrayWithAMaximumLengthCanFail(): void
    {
        $this->mockHandler->append(new Response(200, [], (string) json_encode([1, 2, 3])));
        $this->context->requestPath('/some/path');
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Expected response body to be a JSON array with at most 2 entries, got 3: "[');
        $this->context->assertResponseBodyJsonArrayMaxLength(2);
    }

    public function testThrowsExceptionWhenAssertingTheMaximumLengthOfAnArrayInTheResponseBodyWhenNoResponseExist(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The request has not been made yet, so no response object exists.');
        $this->context->assertResponseBodyJsonArrayMaxLength(5);
    }

    public function testThrowsExceptionWhenAssertingTheMaximumLengthOfAnArrayInTheResponseBodyAndTheBodyDoesNotContainAnArray(): void
    {
        $this->mockHandler->append(new Response(200, [], (string) json_encode(['foo' => 'bar'])));
        $this->context->requestPath('/some/path');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The response body does not contain a valid JSON array.');
        $this->context->assertResponseBodyJsonArrayMaxLength(2);
    }

    public function testAssertingThatTheResponseBodyContainsJsonCanFail(): void
    {
        $this->mockHandler->append(new Response(200, [], '{"foo":"bar"}'));
        $this->context->requestPath('/some/path');
        $this->comparator
            ->expects($this->once())
            ->method('compare')
            ->with(['bar' => 'foo'], ['foo' => 'bar'])
            ->willThrowException(new OutOfRangeException('error message'));

        $this->expectException(OutOfRangeException::class);
        $this->expectExceptionMessage('error message');
        $this->context->assertResponseBodyContainsJson(new PyStringNode(['{"bar":"foo"}'], 1));
    }

    public function testWillThrowExceptionWhenArrayContainsComparatorDoesNotReturnInACorrectMannerWhenCheckingTheResponseBodyForJson(): void
    {
        $this->mockHandler->append(new Response(200, [], '{"foo":"bar"}'));
        $this->context->requestPath('/some/path');
        $this->comparator
            ->expects($this->once())
            ->method('compare')
            ->with(['bar' => 'foo'], ['foo' => 'bar'])
            ->willReturn(false);

        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Comparator did not return in a correct manner. Marking assertion as failed.');
        $this->context->assertResponseBodyContainsJson(new PyStringNode(['{"bar":"foo"}'], 1));
    }

    public function testThrowsExceptionWhenAssertingThatTheResponseContainsJsonAndNoResponseExist(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The request has not been made yet, so no response object exists.');
        $this->context->assertResponseBodyContainsJson(new PyStringNode(['{"foo":"bar"}'], 1));
    }

    public function testThrowsExceptionWhenAssertingThatTheResponseContainsJsonAndTheResponseContainsInvalidData(): void
    {
        $this->mockHandler->append(new Response(200, [], "{'foo':'bar'}"));
        $this->context->requestPath('/some/path');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The response body does not contain valid JSON data.');
        $this->context->assertResponseBodyContainsJson(new PyStringNode(['{"foo":"bar"}'], 1));
    }

    public function testThrowsExceptionWhenAssertingThatTheBodyContainsJsonAndTheParameterFromTheTestIsInvalid(): void
    {
        $this->mockHandler->append(new Response(200, [], '{"foo":"bar"}'));
        $this->context->requestPath('/some/path');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The supplied parameter is not a valid JSON object.');
        $this->context->assertResponseBodyContainsJson(new PyStringNode(["{'foo':'bar'}"], 1));
    }

    /**
     * @see https://github.com/imbo/behat-api-extension/issues/51
     */
    public function testUsesHttpGetByDefaultWhenRequesting(): void
    {
        $this->mockHandler->append(new Response(200), new Response(200));

        $this->context->requestPath('/some/path', 'POST');
        $this->context->requestPath('/some/path');

        $this->assertCount(2, $this->historyContainer);

        $this->assertSame(
            'POST',
            $this->historyContainer[0]['request']->getMethod(),
            'Expected first request to use HTTP POST',
        );
        $this->assertSame(
            'GET',
            $this->historyContainer[1]['request']->getMethod(),
            'Expected second request to use HTTP GET (default)',
        );
    }
}
