<?php
namespace Imbo\BehatApiExtension\ArrayContainsComparator\Matcher;

use Imbo\BehatApiExtension\ArrayContainsComparator;
use PHPUnit_Framework_TestCase;

/**
 * @coversDefaultClass Imbo\BehatApiExtension\ArrayContainsComparator\Matcher\JWT
 * @testdox JWT matcher
 */
class JWTTest extends PHPUnit_Framework_TestCase {
    /**
     * @var JWT
     */
    private $matcher;

    /**
     * Set up matcher instance
     */
    public function setup() {
        $this->matcher = new JWT(new ArrayContainsComparator());
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getJwt() {
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
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage No JWT registered for "some name".
     * @covers ::__invoke
     */
    public function testThrowsExceptionWhenMatchingAgainstJwtThatDoesNotExist() {
        $matcher = $this->matcher;
        $matcher('some jwt', 'some name');
    }

    /**
     * @expectedException \Imbo\BehatApiExtension\Exception\ArrayContainsComparatorException
     * @expectedExceptionMessage Haystack object is missing the "some" key.
     * @covers ::addToken
     * @covers ::__invoke
     */
    public function testThrowsExceptionWhenJwtDoesNotMatch() {
        $matcher = $this->matcher->addToken('some name', ['some' => 'data'], 'secret', 'HS256');
        $matcher(
            'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiYWRtaW4iOnRydWV9.TJVA95OrM7E2cBab30RMHrHDcEfxjoYZgeFONFh7HgQ',
            'some name'
        );
    }

    /**
     * @covers ::__invoke
     * @dataProvider getJwt
     *
     * @param string $jwt
     * @param string $name
     * @param array $payload
     * @param string $secret
     */
    public function testCanMatchJwt($jwt, $name, array $payload, $secret) {
        $matcher = $this->matcher->addToken($name, $payload, $secret);
        $matcher(
            $jwt,
            $name
        );
    }
}
