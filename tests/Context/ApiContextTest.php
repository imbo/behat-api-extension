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
 */
class ApiContextText extends PHPUnit_Framework_TestCase {
    private $mockHandler;
    private $handlerStack;
    private $historyContainer;
    private $context;
    private $client;
    private $baseUri = 'http://localhost:9876';

    public function setUp() {
        $this->historyContainer = [];
        $this->history = Middleware::history($this->historyContainer);

        $this->mockHandler = new MockHandler();
        $this->handlerStack = HandlerStack::create($this->mockHandler);
        $this->handlerStack->push($this->history);
        $this->client = new Client([
            'handler' => $this->handlerStack,
            'base_uri' => $this->baseUri,
        ]);

        $this->context = new ApiContext();
        $this->context->setClient($this->client);
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
                'lengthToUse' => 3,
                'willFail' => false,
            ],
            [
                'body' => [1, 2, 3],
                'lengthToUse' => 4,
                'willFail' => true,
            ],
            [
                'body' => [],
                'lengthToUse' => 2,
                'willFail' => true,
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
                'lengthToUse' => 3,
                'willFail' => false,
            ],
            [
                'body' => [1, 2, 3],
                'lengthToUse' => 4,
                'willFail' => false,
            ],
            [
                'body' => [],
                'lengthToUse' => 4,
                'willFail' => false,
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
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage File does not exist: /foo/bar
     * @covers ::givenIAttachAFileToTheRequest
     */
    public function testGivenIAttachAFileToTheRequestThatDoesNotExist() {
        $this->context->givenIAttachAFileToTheRequest('/foo/bar', 'foo');
    }

    /**
     * @covers ::givenIAttachAFileToTheRequest
     */
    public function testGivenIAttachAFileToTheRequest() {
        $this->mockHandler->append(new Response(200));
        $files = [
            'file1' => __FILE__,
            'file2' => __DIR__ . '/../../README.md'
        ];

        foreach ($files as $name => $path) {
            $this->context->givenIAttachAFileToTheRequest($path, $name);
        }

        $this->context->whenIRequestPath('/some/path', 'POST');

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
     * @covers ::givenIAuthenticateAs
     * @covers ::addRequestHeader
     */
    public function testGivenIAuthenticateAs() {
        $this->mockHandler->append(new Response(200));

        $username = 'user';
        $password = 'pass';

        $this->context->givenIAuthenticateAs($username, $password);
        $this->context->whenIRequestPath('/some/path', 'POST');

        $this->assertSame(1, count($this->historyContainer));

        $request = $this->historyContainer[0]['request'];
        $this->assertSame('Basic dXNlcjpwYXNz', $request->getHeaderLine('authorization'));
    }

    /**
     * @covers ::givenTheRequestHeaderIs
     * @covers ::addRequestHeader
     */
    public function testGivenTheRequestHeaderIs() {
        $this->mockHandler->append(new Response(200));
        $this->context->givenTheRequestHeaderIs('foo', 'foo');
        $this->context->givenTheRequestHeaderIs('bar', 'foo');
        $this->context->givenTheRequestHeaderIs('bar', 'bar');
        $this->context->whenIRequestPath('/some/path', 'POST');
        $this->assertSame(1, count($this->historyContainer));

        $request = $this->historyContainer[0]['request'];
        $this->assertSame('foo', $request->getHeaderLine('foo'));
        $this->assertSame('foo, bar', $request->getHeaderLine('bar'));
    }

    /**
     * @dataProvider getHttpMethods
     * @covers ::whenIRequestPath
     * @covers ::setRequestPath
     * @covers ::setRequestMethod
     * @covers ::sendRequest
     */
    public function testWhenIRequestPath($method) {
        $this->mockHandler->append(new Response(200));
        $this->context->whenIRequestPath('/some/path', $method);

        $this->assertSame(1, count($this->historyContainer));
        $this->assertSame($method, $this->historyContainer[0]['request']->getMethod());
    }

    /**
     * @covers ::whenIRequestPathWithBody
     * @covers ::setRequestMethod
     * @covers ::setRequestPath
     * @covers ::setRequestBody
     * @covers ::sendRequest
     */
    public function testWhenIRequestPathWithBody() {
        $this->mockHandler->append(new Response(200));
        $this->context->whenIRequestPathWithBody('/some/path', 'POST', new PyStringNode(['some body'], 1));

        $this->assertSame(1, count($this->historyContainer));

        $request = $this->historyContainer[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('some body', (string) $request->getBody());
    }

    /**
     * @covers ::whenIRequestPath
     * @covers ::setRequestMethod
     * @covers ::setRequestPath
     * @covers ::setRequestBody
     * @covers ::sendRequest
     */
    public function testWhenIRequestPathWithQueryParameters() {
        $this->mockHandler->append(new Response(200));
        $this->context->whenIRequestPath('/some/path?foo=bar&bar=foo&a[]=1&a[]=2');

        $this->assertSame(1, count($this->historyContainer));

        $request = $this->historyContainer[0]['request'];

        $this->assertSame('foo=bar&bar=foo&a%5B%5D=1&a%5B%5D=2', $request->getUri()->getQuery());
    }

    /**
     * @covers ::whenIRequestPathWithJsonBody
     * @covers ::setRequestHeader
     * @covers ::whenIRequestPathWithBody
     */
    public function testWhenIRequestPathWithValidJsonBody() {
        $this->mockHandler->append(new Response(200));
        $this->context->whenIRequestPathWithJsonBody('/some/path', 'POST', new PyStringNode(['{"foo":"bar"}'], 1));

        $this->assertSame(1, count($this->historyContainer));

        $request = $this->historyContainer[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('application/json', $request->getHeaderLine('content-type'));
        $this->assertSame('{"foo":"bar"}', (string) $request->getBody());
    }

    /**
     * @expectedException Assert\InvalidArgumentException
     * @expectedExceptionMessage Value "{foo:"bar"}" is not a valid JSON string.
     * @covers ::whenIRequestPathWithJsonBody
     */
    public function testWhenIRequestPathWithInvalidJsonBody() {
        $this->mockHandler->append(new Response(200));
        $this->context->whenIRequestPathWithJsonBody('/some/path', 'POST', new PyStringNode(['{foo:"bar"}'], 1));
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage File does not exist: /foo/bar
     * @covers ::whenISendFile
     */
    public function testWhenISendAFileThatDoesNotExist() {
        $this->context->whenISendFile('/foo/bar', '/path', 'POST');
    }

    /**
     * @dataProvider getFilesAndMimeTypes
     * @covers ::whenISendFile
     * @covers ::setRequestHeader
     * @covers ::whenIRequestPathWithBody
     */
    public function testWhenISendFile($filePath, $method, $expectedMimeType, $mimeTypeToSend = null) {
        $this->mockHandler->append(new Response(200));
        $this->context->whenISendFile($filePath, '/some/path', $method, $mimeTypeToSend);
        $this->assertSame(1, count($this->historyContainer));

        $request = $this->historyContainer[0]['request'];
        $this->assertSame($method, $request->getMethod());
        $this->assertSame('/some/path', $request->getUri()->getPath());
        $this->assertSame($expectedMimeType, $request->getHeaderLine('Content-Type'));
        $this->assertSame(file_get_contents($filePath), (string) $request->getBody());
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The request has not been made yet, so no response object exists.
     * @covers ::thenTheResponseCodeIs
     * @covers ::requireResponse
     */
    public function testThenTheResponseCodeIsWithMissingResponseInstance() {
        $this->context->thenTheResponseCodeIs(200);
    }

    /**
     * @dataProvider getResponseCodes
     * @covers ::thenTheResponseCodeIs
     * @covers ::validateResponseCode
     */
    public function testThenTheResponseCodeIs($code) {
        $this->mockHandler->append(new Response($code));
        $this->context->whenIRequestPath('/some/path');
        $this->context->thenTheResponseCodeIs($code);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The request has not been made yet, so no response object exists.
     * @covers ::thenTheResponseCodeIsNot
     * @covers ::requireResponse
     */
    public function testThenTheResponseCodeIsNotWithMissingResponseInstance() {
        $this->context->thenTheResponseCodeIsNot(200);
    }

    /**
     * @dataProvider getResponseCodes
     * @covers ::thenTheResponseCodeIsNot
     * @covers ::validateResponseCode
     */
    public function testThenTheResponseCodeIsNot($code, array $otherCodes) {
        $this->mockHandler->append(new Response($code));
        $this->context->whenIRequestPath('/some/path');

        foreach ($otherCodes as $otherCode) {
            $this->context->thenTheResponseCodeIsNot($otherCode);
        }

        $this->expectException('Assert\InvalidArgumentException');
        $this->expectExceptionMessage('Did not expect response code ' . $code);

        $this->context->thenTheResponseCodeIsNot($code);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The request has not been made yet, so no response object exists.
     * @covers ::thenTheResponseIs
     * @covers ::requireResponse
     */
    public function testThenTheResponseIsWhenThereIsNoResponseInstance() {
        $this->context->thenTheResponseIs('success');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The request has not been made yet, so no response object exists.
     * @covers ::thenTheResponseIsNot
     * @covers ::requireResponse
     */
    public function testThenTheResponseIsNotWhenThereIsNoResponseInstance() {
        $this->context->thenTheResponseIsNot('success');
    }

    /**
     * @dataProvider getGroupAndResponseCodes
     * @covers ::thenTheResponseIs
     * @covers ::requireResponse
     * @covers ::getResponseCodeGroupRange
     */
    public function testThenTheResponseIs($group, array $codes) {
        foreach ($codes as $code) {
            $this->mockHandler->append(new Response($code, [], 'response body'));
            $this->context->whenIRequestPath('/some/path');
            $this->context->thenTheResponseIs($group);
        }
    }

    /**
     * @dataProvider getGroupAndResponseCodes
     * @covers ::thenTheResponseIsNot
     * @covers ::thenTheResponseIs
     */
    public function testThenTheResponseIsNot($group, array $codes) {
        $groups = [
            'informational',
            'success',
            'redirection',
            'client error',
            'server error',
        ];

        foreach ($codes as $code) {
            $this->mockHandler->append(new Response($code));
            $this->context->whenIRequestPath('/some/path');

            foreach (array_filter($groups, function($g) use ($group) { return $g !== $group; }) as $g) {
                // Assert that the response is not in any of the other groups
                $this->context->thenTheResponseIsNot($g);
            }
        }
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Response was not supposed to be success (actual response code: 200)
     * @covers ::thenTheResponseIsNot
     * @covers ::thenTheResponseIs
     */
    public function testThenTheResponseIsNotWhenResponseIsInGroup() {
        $this->mockHandler->append(new Response(200));
        $this->context->whenIRequestPath('/some/path');
        $this->context->thenTheResponseIsNot('success');
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid response code group: foobar
     * @covers ::thenTheResponseIs
     * @covers ::getResponseCodeGroupRange
     */
    public function testThenTheResponseIsInInvalidGroup() {
        $this->mockHandler->append(new Response(200));
        $this->context->whenIRequestPath('/some/path');
        $this->context->thenTheResponseIsNot('foobar');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The request has not been made yet, so no response object exists.
     * @covers ::thenTheResponseBodyIs
     * @covers ::requireResponse
     */
    public function testThenTheResponseBodyIsWhenNoResponseExists() {
        $this->context->thenTheResponseBodyIs(new PyStringNode(['some body'], 1));
    }

    /**
     * @covers ::thenTheResponseBodyIs
     */
    public function testThenTheResponseBodyIsWithMatchingBody() {
        $this->mockHandler->append(new Response(200, [], 'response body'));
        $this->context->whenIRequestPath('/some/path');
        $this->context->thenTheResponseBodyIs(new PyStringNode(['response body'], 1));
    }

    /**
     * @expectedException Assert\InvalidArgumentException
     * @expectedExceptionMessage Value "response body" is not the same as expected value "foo".
     * @covers ::thenTheResponseBodyIs
     */
    public function testThenTheResponseBodyIsWithNonMatchingBody() {
        $this->mockHandler->append(new Response(200, [], 'response body'));
        $this->context->whenIRequestPath('/some/path');
        $this->context->thenTheResponseBodyIs(new PyStringNode(['foo'], 1));
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The request has not been made yet, so no response object exists.
     * @covers ::thenTheResponseBodyMatches
     * @covers ::requireResponse
     */
    public function testThenTheResponseBodyMatchesWhenNoResponseExists() {
        $this->context->thenTheResponseBodyMatches(new PyStringNode(['/foo/'], 1));
    }

    /**
     * @covers ::thenTheResponseBodyMatches
     */
    public function testThenTheResponseBodyMatches() {
        $this->mockHandler->append(new Response(200, [], '{"foo":"bar"}'));
        $this->context->whenIRequestPath('/some/path');
        $this->context->thenTheResponseBodyMatches(new PyStringNode(['/^{"FOO": ?"BAR"}$/i'], 1));
    }

    /**
     * @expectedException Assert\InvalidArgumentException
     * @expectedExceptionMessage Value "{"foo":"bar"}" does not match expression.
     * @covers ::thenTheResponseBodyMatches
     */
    public function testThenTheResponseBodyMatchesWithAPatternThatDoesNotMatch() {
        $this->mockHandler->append(new Response(200, [], '{"foo":"bar"}'));
        $this->context->whenIRequestPath('/some/path');
        $this->context->thenTheResponseBodyMatches(new PyStringNode(['/^{"FOO": "BAR"}$/'], 1));
    }

    /**
     * @dataProvider getInvalidHttpResponseCodes
     * @covers ::validateResponseCode
     */
    public function testThenTheResponseCodeIsUSingAnInvalidCode($code) {
        $this->mockHandler->append(new Response(200));
        $this->context->whenIRequestPath('/some/path');

        $this->expectException('Assert\InvalidArgumentException');
        $this->expectExceptionMessage(sprintf('Response code must be between 100 and 599, got %d.', $code));

        $this->context->thenTheResponseCodeIs($code);
    }

    /**
     * @expectedException GuzzleHttp\Exception\RequestException
     * @expectedExceptionMessage error
     * @covers ::sendRequest
     */
    public function testSendRequestWhenThereIsAnErrorCommunicatingWithTheServer() {
        $this->mockHandler->append(new RequestException('error', new Request('GET', 'path')));
        $this->context->whenIRequestPath('path');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The request has not been made yet, so no response object exists.
     * @covers ::thenTheResponseHeaderExists
     * @covers ::requireResponse
     */
    public function testThenTheResponseHeaderExistsWhenNoResponseExists() {
        $this->context->thenTheResponseHeaderExists('Connection');
    }

    /**
     * @covers ::thenTheResponseHeaderExists
     */
    public function testThenTheResponseHeaderExists() {
        $this->mockHandler->append(new Response(200, ['Content-Type' => 'application/json']));
        $this->context->whenIRequestPath('/some/path');
        $this->context->thenTheResponseHeaderExists('Content-Type');
    }

    /**
     * @expectedException Assert\InvalidArgumentException
     * @expectedExceptionMessage The "Content-Type" response header does not exist
     * @covers ::thenTheResponseHeaderExists
     */
    public function testThenTheResponseHeaderExistsWhenHeaderDoesNotExist() {
        $this->mockHandler->append(new Response(200));
        $this->context->whenIRequestPath('/some/path');
        $this->context->thenTheResponseHeaderExists('Content-Type');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The request has not been made yet, so no response object exists.
     * @covers ::thenTheResponseHeaderDoesNotExist
     * @covers ::requireResponse
     */
    public function testThenTheResponseHeaderDoesNotExistWhenNoResponseExists() {
        $this->context->thenTheResponseHeaderDoesNotExist('Connection');
    }

    /**
     * @covers ::thenTheResponseHeaderDoesNotExist
     */
    public function testThenTheResponseHeaderDoesNotExist() {
        $this->mockHandler->append(new Response(200));
        $this->context->whenIRequestPath('/some/path');
        $this->context->thenTheResponseHeaderDoesNotExist('Content-Type');
    }

    /**
     * @expectedException Assert\InvalidArgumentException
     * @expectedExceptionMessage The "Content-Type" response header should not exist
     * @covers ::thenTheResponseHeaderDoesNotExist
     */
    public function testThenTheResponseHeaderDoesNotExistWhenHeaderExists() {
        $this->mockHandler->append(new Response(200, ['Content-Type' => 'application/json']));
        $this->context->whenIRequestPath('/some/path');
        $this->context->thenTheResponseHeaderDoesNotExist('Content-Type');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The request has not been made yet, so no response object exists.
     * @covers ::thenTheResponseHeaderIs
     * @covers ::requireResponse
     */
    public function testThenTheResponseHeaderIsWhenNoResponseExists() {
        $this->context->thenTheResponseHeaderIs('Connection', 'close');
    }

    /**
     * @covers ::thenTheResponseHeaderIs
     */
    public function testThenTheResponseHeaderIs() {
        $this->mockHandler->append(new Response(200, ['Content-Type' => 'application/json']));
        $this->context->whenIRequestPath('/some/path');
        $this->context->thenTheResponseHeaderIs('Content-Type', 'application/json');
    }

    /**
     * @expectedException Assert\InvalidArgumentException
     * @expectedExceptionMessage Response header (Content-Type) mismatch. Expected "application/xml", got "application/json".
     * @covers ::thenTheResponseHeaderIs
     */
    public function testThenTheResponseHeaderIsWhenValueDoesNotMatch() {
        $this->mockHandler->append(new Response(200, ['Content-Type' => 'application/json']));
        $this->context->whenIRequestPath('/some/path');
        $this->context->thenTheResponseHeaderIs('Content-Type', 'application/xml');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The request has not been made yet, so no response object exists.
     * @covers ::thenTheResponseHeaderMatches
     * @covers ::requireResponse
     */
    public function testThenTheResponseHeaderMatchesWhenNoResponseExists() {
        $this->context->thenTheResponseHeaderMatches('Connection', 'close');
    }

    /**
     * @covers ::thenTheResponseHeaderMatches
     */
    public function testThenTheResponseHeaderMatches() {
        $this->mockHandler->append(new Response(200, ['Content-Type' => 'application/json']));
        $this->context->whenIRequestPath('/some/path');
        $this->context->thenTheResponseHeaderMatches('Content-Type', '#^application/(json|xml)$#');
    }

    /**
     * @expectedException Assert\InvalidArgumentException
     * @expectedExceptionMessage Response header (Content-Type) mismatch. "application/json" does not match "#^application/xml$#".
     * @covers ::thenTheResponseHeaderMatches
     */
    public function testThenTheResponseHeaderMatchesWhenValueDoesNotMatch() {
        $this->mockHandler->append(new Response(200, ['Content-Type' => 'application/json']));
        $this->context->whenIRequestPath('/some/path');
        $this->context->thenTheResponseHeaderMatches('Content-Type', '#^application/xml$#');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The request has not been made yet, so no response object exists.
     * @covers ::thenTheResponseBodyIsAnArrayOfLength
     * @covers ::requireResponse
     */
    public function testThenTheResponseBodyIsAnArrayOfLengthWhenNoRequestHasBeenMade() {
        $this->context->thenTheResponseBodyIsAnArrayOfLength(5);
    }



    /**
     * @dataProvider getResponseBodyArrays
     * @covers ::thenTheResponseBodyIsAnArrayOfLength
     */
    public function testThenTheResponseBodyIsAnArrayOfLength(array $body, $lengthToUse, $willFail) {
        $this->mockHandler->append(new Response(200, [], json_encode($body)));
        $this->context->whenIRequestPath('/some/path');

        if ($willFail) {
            $this->expectException('Assert\InvalidArgumentException');
            $this->expectExceptionMessage(sprintf('Wrong length for the array in the response body. Expected %d, got %d.', $lengthToUse, count($body)));
        }

        $this->context->thenTheResponseBodyIsAnArrayOfLength($lengthToUse);
    }

    /**
     * @covers ::thenTheResponseBodyIsAnArrayOfLength
     */
    public function testThenTheResponseBodyIsAnEmptyArray() {
        $this->mockHandler->append(new Response(200, [], json_encode([])));
        $this->context->whenIRequestPath('/some/path');
        $this->context->thenTheResponseBodyIsAnArrayOfLength(0);
    }

    /**
     * @expectedException Assert\InvalidArgumentException
     * @expectedExceptionMessage Wrong length for the array in the response body. Expected 0, got 3.
     * @covers ::thenTheResponseBodyIsAnArrayOfLength
     */
    public function testThenTheResponseBodyIsAnEmptyArrayWhenItsNot() {
        $this->mockHandler->append(new Response(200, [], json_encode([1, 2, 3])));
        $this->context->whenIRequestPath('/some/path');
        $this->context->thenTheResponseBodyIsAnArrayOfLength(0);
    }

    /**
     * @covers ::thenTheResponseBodyIsAnEmptyObject
     */
    public function testThenTheResponseBodyIsAnEmptyObject() {
        $this->mockHandler->append(new Response(200, [], json_encode(new stdClass())));
        $this->context->whenIRequestPath('/some/path');
        $this->context->thenTheResponseBodyIsAnEmptyObject();
    }

    /**
     * @covers ::thenTheResponseBodyIsAnEmptyObject
     * @expectedException Assert\InvalidArgumentException
     * @expectedExceptionMessage Response body is not an object.
     */
    public function testThenTheResponseBodyIsAnEmptyObjectWhenTheResponseBodyIsNotAnObject() {
        $this->mockHandler->append(new Response(200, [], json_encode([])));
        $this->context->whenIRequestPath('/some/path');
        $this->context->thenTheResponseBodyIsAnEmptyObject();
    }

    /**
     * @covers ::thenTheResponseBodyIsAnEmptyObject
     * @expectedException Assert\InvalidArgumentException
     * @expectedExceptionMessage Object in response body is not empty.
     */
    public function testThenTheResponseBodyIsAnEmptyObjectWhenItIsNot() {
        $object = new stdClass();
        $object->foo = 'bar';
        $this->mockHandler->append(new Response(200, [], json_encode($object)));
        $this->context->whenIRequestPath('/some/path');
        $this->context->thenTheResponseBodyIsAnEmptyObject();
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The response body does not contain a valid JSON array.
     * @covers ::thenTheResponseBodyIsAnArrayOfLength
     */
    public function testThenTheResponseBodyIsAnArrayOfLengthWithAnInvalidBody() {
        $this->mockHandler->append(new Response(200, [], json_encode(['foo' => 'bar'])));
        $this->context->whenIRequestPath('/some/path');
        $this->context->thenTheResponseBodyIsAnArrayOfLength(0);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The request has not been made yet, so no response object exists.
     * @covers ::thenTheResponseBodyIsAnArrayWithALengthOfAtLeast
     * @covers ::requireResponse
     */
    public function testThenTheResponseBodyIsAnArrayWithALengthOfAtLeastWhenNoRequestHaveBeenMade() {
        $this->context->thenTheResponseBodyIsAnArrayWithALengthOfAtLeast(5);
    }

    /**
     * @dataProvider getResponseBodyArraysForAtLeast
     * @covers ::thenTheResponseBodyIsAnArrayWithALengthOfAtLeast
     * @covers ::getResponseBody
     */
    public function testThenTheResponseBodyIsAnArrayWithALengthOfAtLeast(array $body, $lengthToUse, $willFail) {
        $this->mockHandler->append(new Response(200, [], json_encode($body)));
        $this->context->whenIRequestPath('/some/path');

        if ($willFail) {
            $this->expectException('Assert\InvalidArgumentException');
            $this->expectExceptionMessage(sprintf(
                'Array length should be at least %d, but length was %d',
                $lengthToUse,
                count($body)
            ));
        }

        $this->context->thenTheResponseBodyIsAnArrayWithALengthOfAtLeast($lengthToUse);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The response body does not contain a valid JSON array.
     * @covers ::thenTheResponseBodyIsAnArrayWithALengthOfAtLeast
     * @covers ::getResponseBody
     */
    public function testThenTheResponseBodyIsAnArrayWithALengthOfAtLeastWithAnInvalidBody() {
        $this->mockHandler->append(new Response(200, [], json_encode(['foo' => 'bar'])));
        $this->context->whenIRequestPath('/some/path');
        $this->context->thenTheResponseBodyIsAnArrayWithALengthOfAtLeast(2);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The request has not been made yet, so no response object exists.
     * @covers ::thenTheResponseBodyIsAnArrayWithALengthOfAtMost
     * @covers ::requireResponse
     */
    public function testThenTheResponseBodyIsAnArrayWithALengthOfAtMostWhenNoRequestHaveBeenMade() {
        $this->context->thenTheResponseBodyIsAnArrayWithALengthOfAtMost(5);
    }

    /**
     * @dataProvider getResponseBodyArraysForAtMost
     * @covers ::thenTheResponseBodyIsAnArrayWithALengthOfAtMost
     * @covers ::getResponseBody
     */
    public function testThenTheResponseBodyIsAnArrayWithALengthOfAtMost(array $body, $lengthToUse, $willFail) {
        $this->mockHandler->append(new Response(200, [], json_encode($body)));
        $this->context->whenIRequestPath('/some/path');

        if ($willFail) {
            $this->expectException('Assert\InvalidArgumentException');
            $this->expectExceptionMessage(sprintf(
                'Array length should be at least %d, but length was %d',
                $lengthToUse,
                count($body)
            ));
        }

        $this->context->thenTheResponseBodyIsAnArrayWithALengthOfAtMost($lengthToUse);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The response body does not contain a valid JSON array.
     * @covers ::thenTheResponseBodyIsAnArrayWithALengthOfAtMost
     * @covers ::getResponseBody
     */
    public function testThenTheResponseBodyIsAnArrayWithALengthOfAtMostWithAnInvalidBody() {
        $this->mockHandler->append(new Response(200, [], json_encode(['foo' => 'bar'])));
        $this->context->whenIRequestPath('/some/path');
        $this->context->thenTheResponseBodyIsAnArrayWithALengthOfAtMost(2);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The request has not been made yet, so no response object exists.
     * @covers ::thenTheResponseBodyContains
     * @covers ::requireResponse
     */
    public function testThenTheResponseBodyContainsWhenNoRequestHaveBeenMade() {
        $this->context->thenTheResponseBodyContains(new PyStringNode(['{"foo":"bar"}'], 1));
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The response body does not contain a valid JSON array / object.
     * @covers ::thenTheResponseBodyContains
     * @covers ::getResponseBody
     */
    public function testThenTheResponseBodyContainsWithInvalidJsonInBody() {
        $this->mockHandler->append(new Response(200, [], 'foobar'));
        $this->context->whenIRequestPath('/some/path');
        $this->context->thenTheResponseBodyContains(new PyStringNode(['{"foo":"bar"}'], 1));
    }

    /**
     * @expectedException Assert\InvalidArgumentException
     * @expectedExceptionMessage The supplied parameter is not a valid JSON object.
     * @covers ::thenTheResponseBodyContains
     */
    public function testThenTheResponseBodyContainsWithInvalidJsonParameter() {
        $this->mockHandler->append(new Response(200, [], '{"foo":"bar"}'));
        $this->context->whenIRequestPath('/some/path');
        $this->context->thenTheResponseBodyContains(new PyStringNode(['foobar'], 1));
    }

    /**
     * @covers ::thenTheResponseBodyContains
     * @covers ::getResponseBody
     */
    public function testThenTheResponseBodyContains() {
        $this->mockHandler->append(new Response(200, [], '{"foo":"bar","bar":"foo"}'));
        $this->context->whenIRequestPath('/some/path');
        $this->context->thenTheResponseBodyContains(new PyStringNode(['{"bar":"foo","foo":"bar"}'], 1));
    }

    /**
     * @expectedException OutOfRangeException
     * @expectedExceptionMessage Key is missing from the haystack: bar
     * @covers ::thenTheResponseBodyContains
     * @covers ::getResponseBody
     */
    public function testThenTheResponseBodyContainsOnFailure() {
        $this->mockHandler->append(new Response(200, [], '{"foo":"bar"}'));
        $this->context->whenIRequestPath('/some/path');
        $this->context->thenTheResponseBodyContains(new PyStringNode(['{"bar":"foo"}'], 1));
    }

    /**
     * @see https://github.com/imbo/behat-api-extension/issues/7
     * @covers ::setRequestBody
     */
    public function testDontAllowRequestBodyWithMultipartFormDataRequests() {
        $this->mockHandler->append(new Response(200));
        $this->context->givenIAttachAFileToTheRequest(__FILE__, 'file');

        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('It\'s not allowed to set a request body when using multipart/form-data or form parameters.');

        $this->context->whenIRequestPathWithBody('/some/path', 'POST', new PyStringNode(['some body'], 1));
    }

    /**
     * @covers ::givenTheFollowingFormParametersAreSet
     * @covers ::sendRequest
     */
    public function testGivenTheFollowingFormParametersAreSet() {
        $this->mockHandler->append(new Response(200));
        $this->context->givenTheFollowingFormParametersAreSet(new TableNode([
            ['name', 'value'],
            ['foo', 'bar'],
            ['bar', 'foo'],
            ['bar', 'bar'],
        ]));
        $this->context->whenIRequestPath('/some/path');

        $this->assertSame(1, count($this->historyContainer));

        $request = $this->historyContainer[0]['request'];

        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('application/x-www-form-urlencoded', $request->getHeaderLine('Content-Type'));
        $this->assertSame(37, (int) $request->getHeaderLine('Content-Length'));
        $this->assertSame('foo=bar&bar%5B0%5D=foo&bar%5B1%5D=bar', (string) $request->getBody());
    }

    /**
     * @covers ::sendRequest
     */
    public function testGivenTheFollowingFormParametersAreSetCombinedWithAttachingAFile() {
        $this->mockHandler->append(new Response(200));
        $this->context->givenTheFollowingFormParametersAreSet(new TableNode([
            ['name', 'value'],
            ['foo', 'bar'],
            ['bar', 'foo'],
            ['bar', 'bar'],
        ]));
        $this->context->givenIAttachAFileToTheRequest(__FILE__, 'file');
        $this->context->whenIRequestPath('/some/path');

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
     * @covers ::setRequestBody
     */
    public function testDontAllowRequestBodyWithFormParameters() {
        $this->mockHandler->append(new Response(200));
        $this->context->givenTheFollowingFormParametersAreSet(new TableNode([
            ['name', 'value'],
            ['foo', 'bar'],
        ]));

        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('It\'s not allowed to set a request body when using multipart/form-data or form parameters.');

        $this->context->whenIRequestPathWithBody('/some/path', 'POST', new PyStringNode(['some body'], 1));
    }

    /**
     * @dataProvider getResponseCodesAndReasonPhrases
     * @covers ::thenTheResponseReasonPhraseIs
     * @param int $code The HTTP response code
     * @param string $phrase The HTTP response reason phrase
     */
    public function testCanAssertResponseReasonPhrase($code, $phrase) {
        $this->mockHandler->append(new Response($code, [], null, 1.1, $phrase));
        $this->context->whenIRequestPath('/some/path');
        $this->context->thenTheResponseReasonPhraseIs($phrase);
    }

    /**
     * @covers ::thenTheResponseReasonPhraseIs
     * @expectedException Assert\InvalidArgumentException
     * @expectedExceptionMessage Invalid HTTP response reason phrase, expected "ok", got "OK"
     */
    public function testAssertResponseReasonPhraseCanFail() {
        $this->mockHandler->append(new Response());
        $this->context->whenIRequestPath('/some/path');
        $this->context->thenTheResponseReasonPhraseIs('ok');
    }

    /**
     * @dataProvider getResponseCodesAndReasonPhrases
     * @covers ::thenTheResponseStatusLineIs
     * @param int $code The HTTP response code
     * @param string $phrase The HTTP response reason phrase
     */
    public function testCanAssertResponseStatusLine($code, $phrase) {
        $this->mockHandler->append(new Response($code, [], null, 1.1, $phrase));
        $this->context->whenIRequestPath('/some/path');
        $this->context->thenTheResponseStatusLineIs(sprintf('%d %s', $code, $phrase));
    }

    /**
     * @covers ::thenTheResponseStatusLineIs
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid status line: "200". Must consist of a status code and a test, for instance "200 OK"
     */
    public function testAssertResponseStatusLineFailsOnInvalidStatusLine() {
        $this->mockHandler->append(new Response());
        $this->context->whenIRequestPath('/some/path');
        $this->context->thenTheResponseStatusLineIs('200');
    }

    /**
     * @covers ::thenTheResponseStatusLineIs
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Response status line did not match. Expected "200 Foobar", got "200 OK"
     */
    public function testAssertResponseStatusLineCanFail() {
        $this->mockHandler->append(new Response());
        $this->context->whenIRequestPath('/some/path');
        $this->context->thenTheResponseStatusLineIs('200 Foobar');
    }

    /**
     * @covers ::givenTheRequestBodyIs
     * @covers ::setRequestBody
     */
    public function testCanSetRequestBodyToAString() {
        $this->mockHandler->append(new Response());
        $this->context->givenTheRequestBodyIs('some string');
        $this->context->whenIRequestPath('/some/path', 'POST');

        $this->assertSame(1, count($this->historyContainer));
        $request = $this->historyContainer[0]['request'];
        $this->assertSame('some string', (string) $request->getBody());
    }

    /**
     * @covers ::givenTheRequestBodyContains
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage File does not exist: "/foo/bar"
     */
    public function testFailsWhenSettingRequestBodyToAFileThatDoesNotExist() {
        $this->mockHandler->append(new Response());
        $this->context->givenTheRequestBodyContains('/foo/bar');
    }

    /**
     * @covers ::givenTheRequestBodyContains
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage File is not readable: "/non/readable/file"
     */
    public function testFailsWhenSettingRequestBodyToAFileThatIsNotReadable() {
        $this->mockHandler->append(new Response());
        $this->context->givenTheRequestBodyContains('/non/readable/file');
    }

    /**
     * @covers ::givenTheRequestBodyContains
     * @covers ::setRequestBody
     */
    public function testCanSetRequestBodyToAFile() {
        $this->mockHandler->append(new Response());
        $this->context->givenTheRequestBodyContains(__FILE__);
        $this->context->whenIRequestPath('/some/path', 'POST');

        $this->assertSame(1, count($this->historyContainer));
        $request = $this->historyContainer[0]['request'];
        $this->assertSame(file_get_contents(__FILE__), (string) $request->getBody());
        $this->assertSame('text/x-php', $request->getHeaderLine('Content-Type'));
    }

    /**
     * @dataProvider getUris
     * @param string $baseUri
     * @param string $path
     * @param string $fullUri
     */
    public function testResolvesPathsCorrectly($baseUri, $path, $fullUri) {
        // Set a new client with the given base_uri (and not the one used in setUp())
        $this->context->setClient(new Client([
            'handler' => $this->handlerStack,
            'base_uri' => $baseUri,
        ]));

        $this->mockHandler->append(new Response());
        $this->context->givenTheRequestBodyContains(__FILE__);
        $this->context->whenIRequestPath($path);

        $this->assertSame(1, count($this->historyContainer));
        $request = $this->historyContainer[0]['request'];
        $this->assertSame($fullUri, (string) $request->getUri());
    }
}
