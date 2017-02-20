<?php
namespace Imbo\BehatApiExtension\Exception;

use Exception;

/**
 * Array contains comparator exception
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 */
class ArrayContainsComparatorException extends AssertionFailedException {
    /**
     * @var array
     */
    private $needle = [];

    /**
     * @var array
     */
    private $haystack = [];

    /**
     * @var array
     */
    private $progress = [];

    /**
     * Set the needle
     *
     * @param array $needle
     * @return self
     */
    public function setNeedle(array $needle) {
        $this->needle = $needle;
        return $this;
    }

    /**
     * Get the needle
     *
     * @return array
     */
    public function getNeedle() {
        return $this->needle;
    }

    /**
     * Set the haystack
     *
     * @param array $haystack
     * @return self
     */
    public function setHaystack(array $haystack) {
        $this->haystack = $haystack;
        return $this;
    }

    /**
     * Get the haystack
     *
     * @return array
     */
    public function getHaystack() {
        return $this->haystack;
    }

    /**
     * Set the progress
     *
     * @param array $progress
     * @return self
     */
    public function setProgress(array $progress) {
        $this->progress = $progress;
        return $this;
    }

    /**
     * Get the progress
     *
     * @return array
     */
    public function getProgress() {
        return $this->progress;
    }
}
