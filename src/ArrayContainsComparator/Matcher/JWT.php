<?php declare(strict_types=1);
namespace Imbo\BehatApiExtension\ArrayContainsComparator\Matcher;

use Firebase;
use Imbo\BehatApiExtension\ArrayContainsComparator as Comparator;
use InvalidArgumentException;

/**
 * Match a JWT token
 */
class JWT
{
    /**
     * Comparator for the array
     *
     * @var Comparator
     */
    private $comparator;

    /**
     * JWT tokens present in the response body
     *
     * @var array<string, array{payload: array, secret: string}>
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

    public function __construct(Comparator $comparator)
    {
        $this->comparator = $comparator;
    }

    /**
     * Add a JWT token that can be matched
     */
    public function addToken(string $name, array $payload, string $secret): self
    {
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
    public function __invoke(string $jwt, string $name): bool
    {
        if (!isset($this->jwtTokens[$name])) {
            throw new InvalidArgumentException(sprintf('No JWT registered for "%s".', $name));
        }

        $token  = $this->jwtTokens[$name];

        foreach ($this->allowedAlgorithms as $algorithm) {
            try {
                $result = (array) Firebase\JWT\JWT::decode($jwt, new Firebase\JWT\Key($token['secret'], $algorithm));

                if ($this->comparator->compare($token['payload'], $result)) {
                    return true;
                }
            } catch (\UnexpectedValueException $e) {
            }
        }

        throw new InvalidArgumentException('JWT mismatch.');
    }
}
