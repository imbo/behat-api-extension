<?php declare(strict_types=1);
namespace Imbo\BehatApiExtension\ArrayContainsComparator\Matcher;

use Firebase;
use InvalidArgumentException;
use Imbo\BehatApiExtension\ArrayContainsComparator as Comparator;

/**
 * Match a JWT token
 */
class JWT {
    /**
     * Comparator for the array
     *
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
     * Allowed algorithms for the JWT decoder
     *
     * @var string[]
     */
    protected $allowedAlgorithms = [
        'HS256',
        'HS384',
        'HS512',
    ];

    public function __construct(Comparator $comparator) {
        $this->comparator = $comparator;
    }

    /**
     * Add a JWT token that can be matched
     */
    public function addToken(string $name, array $payload, string $secret) : self {
        $this->jwtTokens[$name] = [
            'payload' => $payload,
            'secret' => $secret,
        ];

        return $this;
    }

    /**
     * Match an array against a JWT
     *
     * @throws InvalidArgumentException
     */
    public function __invoke(string $jwt, string $name) : void {
        if (!isset($this->jwtTokens[$name])) {
            throw new InvalidArgumentException(sprintf('No JWT registered for "%s".', $name));
        }

        $token  = $this->jwtTokens[$name];
        $result = (array) Firebase\JWT\JWT::decode($jwt, $token['secret'], $this->allowedAlgorithms);

        if (!$this->comparator->compare($token['payload'], $result)) {
            throw new InvalidArgumentException('JWT mismatch.');
        }
    }
}
