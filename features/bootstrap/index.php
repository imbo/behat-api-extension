<?php
/**
 * Silex application used to test the Behat API extension
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 */

use Silex\Application;
use Silex\Provider\SecurityServiceProvider;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

require_once __DIR__ . '/../../vendor/autoload.php';

$app = new Application();

$app->register(new SecurityServiceProvider(), [
    'security.firewalls' => [
        'basicAuth' => [
            'pattern' => '^/basicAuth',
            'http' => true,
            'users' => [
                'foo' => [
                    'ROLE_ADMIN',
                    '$2y$13$jUzpuB1A0C0A5utO0hW/1eKngeCIxR8LO/Ios5Tay9b8zRUcLtCMO', // bar
                ],
                'bar' => [
                    'ROLE_ADMIN',
                    '$2y$10$3i9/lVd8UOFIJ6PAMFt8gu3/r5g0qeCJvoSlLCsvMTythye19F77a', // foo
                ],
            ],
        ],
    ],
]);

/**
 * Front page
 */
$app->match('/', function(Request $request) {
    return new JsonResponse([
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
    ], 200, [
        'X-Foo' => 'foo',
    ]);
});

/**
 * Echo the request body
 */
$app->match('/echo', function(Request $request) {
    $headers = [];

    // Set the same Content-Type header in the response as found in the request
    if ($contentType = $request->headers->get('Content-Type')) {
        $headers['Content-Type'] = $contentType;
    }

    return $request->query->has('json') ?
        new JsonResponse(json_decode($request->getContent(), true), 200, $headers) :
        new Response($request->getContent(), 200, $headers);
});

/**
 * Return information about uploaded files
 */
$app->post('/files', function(Request $request) {
    return new JsonResponse($_FILES);
});

/**
 * Return information about $_POST and $_FILES vars
 */
$app->post('/formData', function(Request $request) {
    return new JsonResponse(['_POST' => $_POST, '_FILES' => $_FILES]);
});

/**
 * Return the HTTP method
 */
$app->match('/echoHttpMethod', function(Request $request) {
    return new JsonResponse([
        'method' => $request->getMethod(),
    ]);
});

/**
 * Return the authenticated user name
 */
$app->match('/basicAuth', function(Application $app) {
    return new JsonResponse([
        'user' => $app['security.token_storage']->getToken()->getUser()->getUsername(),
    ]);
});

/**
 * Return a client error
 */
$app->match('/clientError', function(Application $app) {
    return new JsonResponse([
        'error' => 'client error',
    ], 400);
});

/**
 * Return a server error
 */
$app->match('/serverError', function(Application $app) {
    return new JsonResponse([
        'error' => 'server error',
    ], 500);
});

$app->match('/issue-13', function(Application $app) {
    return new JsonResponse([
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
    ]);
});

/**
 * Return a response with a custom reason phrase
 */
$app->get('/customReasonPhrase', function(Application $app) {
    return (new Response())->setStatusCode(
        isset($_GET['code']) ? $_GET['code'] : 200,
        isset($_GET['phrase']) ? $_GET['phrase'] : null
    );
});

// Run the application
$app->run();
