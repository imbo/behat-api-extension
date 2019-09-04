<?php declare(strict_types=1);
namespace Imbo\BehatApiExtension\ArrayContainsComparator\Matcher;

use Imbo\BehatApiExtension\ArrayContainsComparator;
use Imbo\BehatApiExtension\Exception\ArrayContainsComparatorException;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

/**
 * @coversDefaultClass Imbo\BehatApiExtension\ArrayContainsComparator\Matcher\JWT
 */
class JWTTest extends TestCase {
    private $matcher;

    public function setUp() : void {
        $this->matcher = new JWT(new ArrayContainsComparator());
    }

    public function getJwt() : array {
        return [
            [
                'jwt' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiYWRtaW4iOnRydWV9.TJVA95OrM7E2cBab30RMHrHDcEfxjoYZgeFONFh7HgQ',
                'name' => 'my jwt',
                'payload' => [
                    'sub' => '1234567890',
					'name' => 'John Doe',
					'admin' => true,
                ],
                'secret' => 'secret',
            ],
            [
                'jwt' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJmb28iOiJiYXIifQ.xnzcLUO-0DuBw9Do3JqtQPyclUpJtdPSG8B8GsglLJAn-hMH-NIQD5eoMbctwEGrkL5bvynD8PZ5kq-sGJTIlg',
                'name' => 'my other jwt',
                'payload' => [
                    'foo' => 'bar',
                ],
                'secret' => 'supersecret',
            ],
        ];
    }

    /**
     * @covers ::__invoke
     */
    public function testThrowsExceptionWhenMatchingAgainstJwtThatDoesNotExist() : void {
        $matcher = $this->matcher;
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No JWT registered for "some name".');
        $matcher('some jwt', 'some name');
    }

    /**
     * @covers ::addToken
     * @covers ::__invoke
     */
    public function testThrowsExceptionWhenJwtDoesNotMatch() : void {
        $matcher = $this->matcher->addToken('some name', ['some' => 'data'], 'secret', 'HS256');
        $this->expectException(ArrayContainsComparatorException::class);
        $this->expectExceptionMessage('Haystack object is missing the "some" key.');
        $matcher(
            'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiYWRtaW4iOnRydWV9.TJVA95OrM7E2cBab30RMHrHDcEfxjoYZgeFONFh7HgQ',
            'some name'
        );
    }

    /**
     * @covers ::__invoke
     * @dataProvider getJwt
     */
    public function testCanMatchJwt(string $jwt, string $name, array $payload, string $secret) : void {
        $matcher = $this->matcher->addToken($name, $payload, $secret);
        $this->assertNull($matcher(
            $jwt,
            $name
        ));
    }

    /**
     * @covers ::__construct
     * @covers ::__invoke
     */
    public function testThrowsExceptionWhenComparatorDoesNotReturnSuccess() : void {
        $comparator = $this->createConfiguredMock(ArrayContainsComparator::class, [
            'compare' => false,
        ]);
        $matcher = (new JWT($comparator))->addToken(
            'token',
            [
                'sub' => '1234567890',
                'name' => 'John Doe',
                'admin' => true,
            ],
            'secret'
        );
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JWT mismatch.');
        $matcher(
            'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiYWRtaW4iOnRydWV9.TJVA95OrM7E2cBab30RMHrHDcEfxjoYZgeFONFh7HgQ',
            'token'
        );
    }
}
