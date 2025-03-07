<?php declare(strict_types=1);

namespace Imbo\BehatApiExtension\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Response;

class JwtAuthentication implements MiddlewareInterface {

    /**
     * Paths to authenticate against.
     *
     * @var array<int, string>
     */
    protected array $paths;

    /**
     * JWT algorithm.
     */
    protected string $jwtAlg;

    /**
     * JWT secret key.
     */
    protected Key $jwtKey;

    /**
     * Required claims for authentication.
     *
     * @var array<int, mixed>
     */
    protected array $requiredClaims;

    /**
     * @param array<int, string> $paths
     * @param string $jwtKey
     * @param array<int, mixed> $requiredClaims
     * @param string $jwtAlg
     */
    public function __construct(array $paths, string $jwtKey, array $requiredClaims = [], string $jwtAlg = 'HS256') {
        $this->paths = $paths;
      $this->jwtAlg = $jwtAlg;
      $this->jwtKey = new Key($jwtKey, $this->jwtAlg);
      $this->requiredClaims = $requiredClaims;
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
                $claims = JWT::decode($matches[1], $this->jwtKey);

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
