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
$app->match('echo', function(Request $request) {
    return $request->query->has('json') ?
        new JsonResponse(json_decode($request->getContent(), true)) :
        new Response($request->getContent());
});

/**
 * Return information about uploaded files
 */
$app->post('files', function(Request $request) {
    return new JsonResponse($_FILES);
});

/**
 * Return the HTTP method
 */
$app->match('echoHttpMethod', function(Request $request) {
    return new JsonResponse([
        'method' => $request->getMethod(),
    ]);
});

/**
 * Return the authenticated user name
 */
$app->match('basicAuth', function(Application $app) {
    return new JsonResponse([
        'user' => $app['security.token_storage']->getToken()->getUser()->getUsername(),
    ]);
});

/**
 * Return a client error
 */
$app->match('clientError', function(Application $app) {
    return new JsonResponse([
        'error' => 'client error',
    ], 400);
});

/**
 * Return a server error
 */
$app->match('serverError', function(Application $app) {
    return new JsonResponse([
        'error' => 'server error',
    ], 500);
});

// Run the application
$app->run();
