<?php
namespace Imbo\BehatApiExtension\ArrayContainsComparator\Matcher;

use Firebase;
use InvalidArgumentException;

/**
 * Match a JWT token
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 */
class JWT {
    /**
     * JWT tokens present in the response body
     *
     * @var array
     */
    private $jwtTokens = [];

    /**
     * Add a JWT token that can be matched
     *
     * @param string $name String identifying the token
     * @param array $payload The payload
     * @param string $secret The secret used to sign the token
     * @return self
     */
    public function addToken($name, array $payload, $secret) {
        $this->jwtTokens[$name] = [
            'payload' => $payload,
            'secret' => $secret,
        ];

        return $this;
    }

    /**
     * Match an array against a JWT
     *
     * @param string $jwt The encoded JWT
     * @param string $name The identifier of the decoded data, added using the addToken method
     * @throws InvalidArgumentException
     * @return void
     */
    public function __invoke($jwt, $name) {
        if (!isset($this->jwtTokens[$name])) {
            throw new InvalidArgumentException(sprintf(
                'No JWT registered for "%s".',
                $name
            ));
        }

        $token = $this->jwtTokens[$name];

        $result = (array) Firebase\JWT\JWT::decode($jwt, $token['secret'], ['HS256', 'HS384', 'HS512']);

        if ($result !== $token['payload']) {
            throw new InvalidArgumentException(sprintf(
                'JWT mismatch.'
            ));
        }
    }
}
