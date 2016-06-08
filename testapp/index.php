<?php
/**
 * Silex application used to test the Behat API extension
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 */

use Silex\Application,
    Silex\Provider,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpFoundation\JsonResponse,
    Symfony\Component\HttpFoundation\Request;

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Application();

$app->register(new Provider\SecurityServiceProvider(), [
    'security.firewalls' => [
        'auth' => [
            'pattern' => '^/auth',
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
$app->get('/', function(Request $request) {
    return new JsonResponse();
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
 * Return the HTTP method
 */
$app->match('getMethod', function(Request $request) {
    return new JsonResponse([
        'method' => $request->getMethod(),
    ]);
});

/**
 * Return the authenticated user name
 */
$app->match('auth', function(Application $app) {
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
