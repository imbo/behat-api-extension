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
 * @covers Imbo\BehatApiExtension\Context\ApiContext
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
     * Get HTTP methods
     *
     * @return array[]
     */
    public function getHttpMethods() {
        return [
            ['GET'],
            ['PUT'],
            ['POST'],
            ['HEAD'],
            ['OPTIONS'],
            ['DELETE'],
        ];
    }

    /**
     * @dataProvider getHttpMethods
     */
    public function testRequestPathUsingDifferentHttpMethods($method) {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath('/some/path', $method);

        $this->assertSame(1, count($this->historyContainer));
        $this->assertSame($method, $this->historyContainer[0]['request']->getMethod());
    }

    public function testSendRequestWithJsonBody() {
        $this->mockHandler->append(new Response(200));
        $this->context->makeAndSendRequestWithJsonBody('/some/path', 'POST', new PyStringNode(['{"foo":"bar"}'], 1));

        $this->assertSame(1, count($this->historyContainer));

        $request = $this->historyContainer[0]['request'];
        $this->assertSame('POST', $request->getMethod(), 'Invalid request method');
        $this->assertSame('application/json', $request->getHeaderLine('content-type'), 'Content-Type request header is wrong');
    }

    /**
     * @expectedException Assert\InvalidArgumentException
     * @expectedExceptionMessage Value "{foo:"bar"}" is not a valid JSON string.
     */
    public function testSendRequestWithInvalidJsonBody() {
        $this->mockHandler->append(new Response(200));
        $this->context->makeAndSendRequestWithJsonBody('/some/path', 'POST', new PyStringNode(['{foo:"bar"}'], 1));
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The request has not been made yet, so no response object exists.
     */
    public function testAssertResponseCodeWithNoResponse() {
        $this->context->assertResponseCode(200);
    }

    /**
     * Get a a response code, along with an array of other response codes that the first parameter
     * does not match
     *
     * @return array[]
     */
    public function getResponseCodes() {
        return [
            [200, [300, 400, 500]],
            [300, [200, 400, 500]],
            [400, [200, 300, 500]],
            [500, [200, 300, 400]],
        ];
    }

    /**
     * @dataProvider getResponseCodes
     */
    public function testAssertResponseCode($code) {
        $this->mockHandler->append(new Response($code));
        $this->context->requestPath('/some/path');
        $this->context->assertResponseCode($code);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The request has not been made yet, so no response object exists.
     */
    public function testAssertResponseCodeIsNotWithNoResponse() {
        $this->context->assertResponseCodeIsNot(200);
    }

    /**
     * @dataProvider getResponseCodes
     */
    public function testAssertResponseCodeIsNot($code, $otherCodes) {
        $this->mockHandler->append(new Response($code));
        $this->context->requestPath('/some/path');

        foreach ($otherCodes as $otherCode) {
            $this->context->assertResponseCodeIsNot($otherCode);

            $this->expectException('Assert\InvalidArgumentException');
            $this->expectExceptionMessage('Did not expect response code ' . $code);

            $this->context->assertResponseCodeIsNot($code);
        }
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The request has not been made yet, so no response object exists.
     */
    public function testAssertResponseBodyMatchesWithNoResponse() {
        $this->context->assertResponseBodyMatches('some body');
    }

    public function testAssertResponseBodyMatches() {
        $this->mockHandler->append(new Response(200, [], 'response body'));
        $this->context->requestPath('/some/path');
        $this->context->assertResponseBodyMatches('response body');
    }

    /**
     * @expectedException Assert\InvalidArgumentException
     * @expectedExceptionMessage Value "response body" is not the same as expected value "foobar".
     */
    public function testAssertResponseBodyMatchesWhenBodyDoesNotMatch() {
        $this->mockHandler->append(new Response(200, [], 'response body'));
        $this->context->requestPath('/some/path');
        $this->context->assertResponseBodyMatches('foobar');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The request has not been made yet, so no response object exists.
     */
    public function testAssertResponseCodeIsInGroupWhenNoResponseExist() {
        $this->context->assertResponseCodeIsInGroup('success');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The request has not been made yet, so no response object exists.
     */
    public function testAssertResponseCodeIsNotInGroupWhenNoResponseExist() {
        $this->context->assertResponseCodeIsNotInGroup('success');
    }

    public function getResponseCodeAndGroup() {
        return [
            [100, 'informational'],
            [199, 'informational'],
            [200, 'success'],
            [299, 'success'],
            [300, 'redirection'],
            [399, 'redirection'],
            [400, 'client error'],
            [499, 'client error'],
            [500, 'server error'],
            [599, 'server error'],
        ];
    }

    /**
     * @dataProvider getResponseCodeAndGroup
     */
    public function testAssertResponseCodeIsInGroup($code, $group) {
        $this->mockHandler->append(new Response($code, [], 'response body'));
        $this->context->requestPath('/some/path');
        $this->context->assertResponseCodeIsInGroup($group);
    }

    /**
     * @dataProvider getResponseCodeAndGroup
     */
    public function testAssertResponseCodeIsNotInGroup($code, $group) {
        $groups = [
            'informational',
            'success',
            'redirection',
            'client error',
            'server error',
        ];
        $this->mockHandler->append(new Response($code, [], 'response body'));
        $this->context->requestPath('/some/path');

        foreach (array_filter($groups, function($g) use ($group) { return $g !== $group; }) as $g) {
            // Assert that the response is not in any of the other groups
            $this->context->assertResponseCodeIsNotInGroup($g);
        }
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Response was not supposed to be success (actual response code: 200)
     */
    public function testAssertResponseCodeIsNotInGroupWhenCodeIsInGroup() {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath('/some/path');
        $this->context->assertResponseCodeIsNotInGroup('success');
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid response code group: foobar
     */
    public function testAssertResponseCodeIsInGroupWithInvalidGroup() {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath('/some/path');
        $this->context->assertResponseCodeIsNotInGroup('foobar');
    }

    /**
     * Data provider that returns invalid http response codes
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
     * @dataProvider getInvalidHttpResponseCodes
     */
    public function testAssertResponseCodeIsAnInvalidCode($code) {
        $this->mockHandler->append(new Response(200));
        $this->context->requestPath('/some/path');

        $this->expectException('Assert\InvalidArgumentException');
        $this->expectExceptionMessage(sprintf('Response code must be between 100 and 599, got %d.', $code));

        $this->context->assertResponseCode($code);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage File does not exist: /foo/bar
     */
    public function testAddFileThatDoesNotExistToTheRequest() {
        $this->context->addFile('/foo/bar', 'foo');
    }

    public function testAddFilesToTheRequest() {
        $this->mockHandler->append(new Response(200));
        $files = [
            'file1' => __FILE__,
            'file2' => __DIR__ . '/../../README.md'
        ];

        foreach ($files as $name => $path) {
            $this->context->addFile($path, $name);
        }

        $this->context->makeAndSendRequest('/some/path', 'POST');

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

    public function testCanUseBasicAuth() {
        $this->mockHandler->append(new Response(200));

        $username = 'user';
        $password = 'pass';

        $this->context->setBasicAuth($username, $password);
        $this->context->makeAndSendRequest('/some/path', 'POST');

        $this->assertSame(1, count($this->historyContainer));

        $request = $this->historyContainer[0]['request'];
        $this->assertSame('Basic dXNlcjpwYXNz', $request->getHeaderLine('authorization'));
    }

    public function testCanAddOneOrMoreHeaders() {
        $this->mockHandler->append(new Response(200));
        $this->context->addHeader('foo', 'foo');
        $this->context->addHeader('bar', 'foo');
        $this->context->addHeader('bar', 'bar');
        $this->context->makeAndSendRequest('/some/path', 'POST');
        $this->assertSame(1, count($this->historyContainer));

        $request = $this->historyContainer[0]['request'];
        $this->assertSame('foo', $request->getHeaderLine('foo'));
        $this->assertSame('foo, bar', $request->getHeaderLine('bar'));
    }

    /**
     * @expectedException GuzzleHttp\Exception\RequestException
     * @expectedExceptionMessage error
     */
    public function testThrowsExceptionWhenErrorCommunicatingWithServer() {
        $this->mockHandler->append(new RequestException('error', new Request('GET', 'path')));
        $this->context->requestPath('path');
    }
}
