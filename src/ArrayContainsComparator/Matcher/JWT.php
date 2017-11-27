<?php
namespace Imbo\BehatApiExtension\ArrayContainsComparator\Matcher;

use Assert\Assertion;
use Firebase;
use InvalidArgumentException;
use Imbo\BehatApiExtension\ArrayContainsComparator as Comparator;

/**
 * Match a JWT token
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 */
class JWT {
    /**
     * @var Comparator
     */
    private $comparator;

    /**
     * JWT tokens present in the response body
     *
     * @var array
     */
    private $jwtTokens = [];

    /**
     * @param Comparator $comparator
     */
    public function __construct(Comparator $comparator)
    {
        $this->comparator = $comparator;
    }

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

        $token  = $this->jwtTokens[$name];
        $result = (array) Firebase\JWT\JWT::decode($jwt, $token['secret'], ['HS256', 'HS384', 'HS512']);

        if (!$this->comparator->compare($token['payload'], $result)) {
            throw new InvalidArgumentException(sprintf(
                'JWT mismatch.'
            ));
        }
    }
}
