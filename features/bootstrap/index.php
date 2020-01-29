<?php
namespace Imbo\BehatApiExtension;

use Slim\Factory\AppFactory;
use Tuupola\Middleware\HttpBasicAuthentication;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use stdClass;

require_once __DIR__ . '/../../vendor/autoload.php';

$app = AppFactory::create();
$app->add(new HttpBasicAuthentication([
    'path' => '/basicAuth',
    'realm' => 'Protected',
    'users' => [
        'foo' => 'bar',
    ]
]));

/**
 * Front page
 */
$app->any('/', function(Request $request, Response $response) : Response {
    $response->getBody()->write(json_encode([
        'null' => null,
        'string' => 'value',
        'integer' => 42,
        'float' => 4.2,
        'boolean true' => true,
        'boolean false' => false,
        'list' => [1, 2, 3, [1], ['foo' => 'bar']],
        'sub' => [
            'string' => 'value',
            'integer' => 42,
            'float' => 4.2,
            'boolean true' => true,
            'boolean false' => false,
            'list' => [1, 2, 3, [1], ['foo' => 'bar']],
        ],
        'types' => [
            'string' => 'string',
            'integer' => 123,
            'double' => 1.23,
            'array' => [1, '2', 3],
            'boolean' => true,
            'null' => null,
            'scalar' => '123',
        ],
    ]));
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withHeader('X-Foo', 'foo');
});

/**
 * Echo the request body
 */
$app->any('/echo', function(Request $request, Response $response) : Response {
    // Set the same Content-Type header in the response as found in the request
    if ($contentType = $request->getHeaderLine('Content-Type')) {
        $response = $response->withHeader('Content-Type', $contentType);
    }

    $requestBody = (string) $request->getBody();

    if (array_key_exists('json', $request->getQueryParams())) {
        $response->getBody()->write(json_encode(json_decode($requestBody, true)));
        $response = $response->withHeader('Content-Type', 'application/json');
    } else {
        $response->getBody()->write($requestBody);
    }

    return $response;
});

/**
 * Return information about uploaded files
 */
$app->post('/files', function(Request $request, Response $response) : Response {
    $response->getBody()->write(json_encode($_FILES));
    return $response->withHeader('Content-Type', 'application/json');
});

/**
 * Return information about the request
 */
$app->any('/requestInfo', function(Request $request, Response $response) : Response {
    $response->getBody()->write(json_encode([
        '_GET' => $_GET,
        '_POST' => $_POST,
        '_FILES' => $_FILES,
        '_SERVER' => $_SERVER,
        'requestBody' => (string) $request->getBody(),
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});

/**
 * Return the HTTP method
 */
$app->any('/echoHttpMethod', function(Request $request, Response $response) : Response {
    $response->getBody()->write(json_encode([
        'method' => $request->getMethod(),
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});

/**
 * Return the authenticated user name
 */
$app->any('/basicAuth', function(Request $request, Response $response) use ($app) {
    $response->getBody()->write(json_encode([
        'user' => explode(':', $request->getUri()->getUserInfo())[0],
    ]));

    return $response->withHeader('Content-Type', 'application/json');
});

/**
 * Return a client error
 */
$app->any('/clientError', function(Request $request, Response $response) : Response {
    $response->getBody()->write(json_encode([
        'error' => 'client error',
    ]));
    return $response
        ->withHeader('Content-Type', 'appliation/json')
        ->withStatus(400);
});

/**
 * @see https://github.com/imbo/behat-api-extension/issues/13
 */
$app->any('/issue-13', function(Request $request, Response $response) : Response {
    $response->getBody()->write(json_encode([
        'customer' => [
            'id' => '12345',
            'name' => 'Behat Testing API',
            'images' => [
                [
                    'id' => '5678',
                    'filename_client' => 'tech.ai',
                    'filename_preview' => 'testimage-converted.png',
                    'filename_print' => 'testimage.ai',
                    'url' => '\/media\/testimage-converted.png',
                    'created_time' => '2016-10-10 07 => 28 => 42'
                ], [
                    'id' => '7890',
                    'filename_client' => 'demo.ai',
                    'filename_preview' => 'demoimage-converted.png',
                    'filename_print' => 'demoimage.ai',
                    'url' => '\/media\/demoimage-converted.png',
                    'created_time' => '2016-10-10 07 => 38 => 22'
                ],
            ],
        ],
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});

/**
 * Return a response with a custom reason phrase
 */
$app->get('/customReasonPhrase', function(Request $request, Response $response) : Response {
    $params = $request->getQueryParams();

    return $response->withStatus(
        !empty($params['code']) ? (int) $params['code'] : 200,
        !empty($params['phrase']) ? $params['phrase'] : ''
    );
});

/**
 * Return a response with an empty array
 */
$app->get('/emptyArray', function(Request $request, Response $response) : Response {
    $response->getBody()->write(json_encode([]));
    return $response->withHeader('Content-Type', 'application/json');
});

/**
 * Return a response with an empty object
 */
$app->get('/emptyObject', function(Request $request, Response $response) : Response {
    $response->getBody()->write(json_encode(new stdClass()));
    return $response->withHeader('Content-Type', 'application/json');
});

/**
 * Return a response with 403 Forbidden
 */
$app->get('/403', function(Request $request, Response $response) : Response {
    return $response->withStatus(403);
});

// Run the application
$app->run();
