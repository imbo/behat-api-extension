<?php declare(strict_types=1);

namespace Imbo\BehatApiExtension\Middleware;


use Firebase\JWT\JWT;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Response;

class JwtAuthentication implements MiddlewareInterface {

    protected $paths;

    /**
     * JWT algorithm.
     *
     * @var string
     */
    protected $jwtAlg;

    /**
     * JWT secret key.
     *
     * @var string
     */
    protected $jwtKey;

    /**
     * Required claims for authentication.
     *
     * @var array
     */
    protected $requiredClaims;

    public function __construct(array $paths, $jwtKey, array $requiredClaims = [], $jwtAlg = 'HS256') {
        $this->paths = $paths;
        $this->jwtKey = $jwtKey;
        $this->jwtAlg = $jwtAlg;
        $this->requiredClaims = $requiredClaims.
    }

    /**
     * Process a request in PSR-15 style and return a response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
        $requestPath = '/' . $request->getUri()->getPath();
        $requestPath = preg_replace('#/+#', '/', $requestPath);

        // Only try to authenticate on configured paths.
        if (false === in_array($requestPath, $this->paths, true)) {
            return $handler->handle($request);
        }

        $message = '';

        if (preg_match("/Bearer\s+(.*)$/i", $request->getHeaderLine("Authorization"), $matches)) {
            try {
                $claims = JWT::decode($matches[1], $this->jwtKey, [$this->jwtAlg]);

                if ($this->requiredClaims) {
                    // todo
                }
            }
            catch (\Exception $e) {
                $message = $e->getMessage();
            }
        }

        return new Response()->withStatus(403, $message);
    }

}
