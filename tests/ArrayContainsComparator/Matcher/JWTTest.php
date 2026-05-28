<?php declare(strict_types=1);

namespace Imbo\BehatApiExtension\ArrayContainsComparator\Matcher;

use Imbo\BehatApiExtension\ArrayContainsComparator;
use Imbo\BehatApiExtension\Exception\ArrayContainsComparatorException;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(JWT::class)]
class JWTTest extends TestCase
{
    private JWT $matcher;

    /**
     * Secret string used for tests.
     */
    public const SECRET = 'b5ffc083b648ba8b7387640c968c23dd1ebaad1c6fa88ce294dde241f81b546e64a5b907dca5b1ceff58d844fc69be5f5d2cfe3ebe6b0855e7bbe341e52c3012';

    protected function setUp(): void
    {
        $this->matcher = new JWT(new ArrayContainsComparator());
    }

    /**
     * @return array<array{jwt:string,name:string,payload:array<string,mixed>,secret:string}>
     */
    public static function getJwt(): array
    {
        return [
            [
                'jwt' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiYWRtaW4iOnRydWV9.s2V0YUgz2wbsyE21np_B-gCrgLB6HOe3MXOCsH4PXXM',
                'name' => 'my jwt',
                'payload' => [
                    'sub' => '1234567890',
                    'name' => 'John Doe',
                    'admin' => true,
                ],
                'secret' => self::SECRET,
            ],
            [
                'jwt' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJmb28iOiJiYXIifQ.ZkI5KsVQ3KMHxOovdQBoTUGVX-ccuPgufCHfSuXWZM0',
                'name' => 'my other jwt',
                'payload' => [
                    'foo' => 'bar',
                ],
                'secret' => self::SECRET,
            ],
        ];
    }

    public function testThrowsExceptionWhenMatchingAgainstJwtThatDoesNotExist(): void
    {
        $matcher = $this->matcher;
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No JWT registered for "some name".');
        $matcher('some jwt', 'some name');
    }

    public function testThrowsExceptionWhenJwtDoesNotMatch(): void
    {
        $matcher = $this->matcher->addToken('some name', ['some' => 'data'], self::SECRET);
        $this->expectException(ArrayContainsComparatorException::class);
        $this->expectExceptionMessage('Haystack object is missing the "some" key.');
        $matcher(
            'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJmb28iOiJiYXIifQ.ZkI5KsVQ3KMHxOovdQBoTUGVX-ccuPgufCHfSuXWZM0',
            'some name',
        );
    }

    #[DataProvider('getJwt')]
    public function testCanMatchJwt(string $jwt, string $name, array $payload, string $secret): void
    {
        $matcher = $this->matcher->addToken($name, $payload, $secret);
        $this->assertTrue($matcher(
            $jwt,
            $name,
        ));
    }

    public function testThrowsExceptionWhenComparatorDoesNotReturnSuccess(): void
    {
        $comparator = $this->createConfiguredStub(ArrayContainsComparator::class, [
            'compare' => false,
        ]);
        $matcher = (new JWT($comparator))->addToken(
            'token',
            [
                'sub' => '1234567890',
                'name' => 'John Doe',
                'admin' => true,
            ],
            self::SECRET,
        );
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JWT mismatch.');
        $matcher(
            'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiYWRtaW4iOnRydWV9.TJVA95OrM7E2cBab30RMHrHDcEfxjoYZgeFONFh7HgQ',
            'token',
        );
    }
}
