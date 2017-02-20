<?php
namespace Imbo\BehatApiExtension\Exception;

use PHPUnit_Framework_TestCase;

/**
 * @coversDefaultClass Imbo\BehatApiExtension\Exception\ArrayContainsComparatorException
 * @testdox Array contains comparator exception
 */
class ArrayContainsComparatorExceptionTest extends PHPUnit_Framework_TestCase {
    /**
     * @var ArrayContainsComparatorException
     */
    private $exception;

    /**
     * Set up the SUT
     */
    public function setUp() {
        $this->exception = new ArrayContainsComparatorException();
    }

    /**
     * @covers ::setNeedle
     * @covers ::getNeedle
     */
    public function testCanSetAndGetNeedle() {
        $this->assertSame([], $this->exception->getNeedle());
        $this->assertSame($this->exception, $this->exception->setNeedle(['key' => 'value']));
        $this->assertSame(['key' => 'value'], $this->exception->getNeedle());
    }

    /**
     * @covers ::setHaystack
     * @covers ::getHaystack
     */
    public function testCanSetAndGetHaystack() {
        $this->assertSame([], $this->exception->getHaystack());
        $this->assertSame($this->exception, $this->exception->setHaystack(['key' => 'value']));
        $this->assertSame(['key' => 'value'], $this->exception->getHaystack());
    }

    /**
     * @covers ::setProgress
     * @covers ::getProgress
     */
    public function testCanSetAndGetProgress() {
        $this->assertSame([], $this->exception->getProgress());
        $this->assertSame($this->exception, $this->exception->setProgress(['key' => 'value']));
        $this->assertSame(['key' => 'value'], $this->exception->getProgress());
    }
}
