<?php
namespace Imbo\BehatApiExtension\Context;

use PHPUnit_Framework_TestCase,
    GuzzleHttp\Client,
    GuzzleHttp\Handler\MockHandler,
    GuzzleHttp\HandlerStack,
    GuzzleHttp\Psr7\Request,
    GuzzleHttp\Psr7\Response,
    GuzzleHttp\Middleware,
    GuzzleHttp\Exception\RequestException,
    Behat\Gherkin\Node\PyStringNode,
    RuntimeException;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
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
        $this->context->thenTheResponseBodyIs('some body');
    }

    /**
     * @covers ::thenTheResponseBodyIs
     */
    public function testThenTheResponseBodyIsWithMatchingBody() {
        $this->mockHandler->append(new Response(200, [], 'response body'));
        $this->context->whenIRequestPath('/some/path');
        $this->context->thenTheResponseBodyIs('response body');
    }

    /**
     * @expectedException Assert\InvalidArgumentException
     * @expectedExceptionMessage Value "response body" is not the same as expected value "foo".
     * @covers ::thenTheResponseBodyIs
     */
    public function testThenTheResponseBodyIsWithNonMatchingBody() {
        $this->mockHandler->append(new Response(200, [], 'response body'));
        $this->context->whenIRequestPath('/some/path');
        $this->context->thenTheResponseBodyIs('foo');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The request has not been made yet, so no response object exists.
     * @covers ::thenTheResponseBodyMatches
     * @covers ::requireResponse
     */
    public function testThenTheResponseBodyMatchesWhenNoResponseExists() {
        $this->context->thenTheResponseBodyMatches('/foo/');
    }

    /**
     * @covers ::thenTheResponseBodyMatches
     */
    public function testThenTheResponseBodyMatches() {
        $this->mockHandler->append(new Response(200, [], '{"foo":"bar"}'));
        $this->context->whenIRequestPath('/some/path');
        $this->context->thenTheResponseBodyMatches('/^{"FOO": ?"BAR"}$/i');
    }

    /**
     * @expectedException Assert\InvalidArgumentException
     * @expectedExceptionMessage Value "{"foo":"bar"}" does not match expression.
     * @covers ::thenTheResponseBodyMatches
     */
    public function testThenTheResponseBodyMatchesWithAPatternThatDoesNotMatch() {
        $this->mockHandler->append(new Response(200, [], '{"foo":"bar"}'));
        $this->context->whenIRequestPath('/some/path');
        $this->context->thenTheResponseBodyMatches('/^{"FOO": "BAR"}$/');
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
}
