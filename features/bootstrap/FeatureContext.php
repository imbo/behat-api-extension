<?php
use Behat\Behat\Context\SnippetAcceptingContext,
    Behat\Gherkin\Node\PyStringNode,
    Behat\Testwork\Hook\Scope\SuiteScope,
    Behat\Behat\Hook\Scope\BeforeScenarioScope,
    Symfony\Component\Process\PhpExecutableFinder,
    Symfony\Component\Process\Process,
    Assert\Assertion;

/**
 * Behat feature context class
 *
 * The feature context used to test the ApiContext steps. The way the tests are executed are
 * heavily inspired by https://github.com/Behat/WebApiExtension.
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 */
class FeatureContext implements SnippetAcceptingContext {
    /**
     * PHP binary used to trigger Behat from the scenarios
     *
     * @var string
     */
    private $phpBin;

    /**
     * Process instance for executing processes
     *
     * @var Process
     */
    private $process;

    /**
     * The working directory where files can be created
     *
     * @var string
     */
    private $workingDir;

    /**
     * Remove test dir (/tmp/behat-api-extension) before and after tests if it exists
     *
     * @param SuiteScope $scope
     * @BeforeSuite
     * @AfterSuite
     */
    public static function emptyTestDir(SuiteScope $scope) {
        $testDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'behat-api-extension';

        if (is_dir($testDir)) {
            self::rmDir($testDir);
        }
    }

    /**
     * Prepare a scenario
     *
     * @param BeforeScenarioScope $scope
     * @throws RuntimeException
     * @BeforeScenario
     */
    public function prepareScenario(BeforeScenarioScope $scope) {
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'behat-api-extension' . DIRECTORY_SEPARATOR . microtime(true);
        mkdir($dir . '/features/bootstrap', 0777, true);

        // Locate the php binary
        if (($bin = (new PhpExecutableFinder())->find()) === false) {
            throw new RuntimeException('Unable to find the PHP executable.');
        }

        $this->workingDir = $dir;
        $this->phpBin = $bin;
        $this->process = new Process(null);
    }

    /**
     * Creates a file with specified name and content in the current working dir
     *
     * @param string $filename Name of the file relative to the working dir
     * @param PyStringNode $content Content of the file
     * @Given /^a file named "([^"]*)" with:$/
     */
    public function createFile($filename, PyStringNode $content) {
        $filename = rtrim($this->workingDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($filename, DIRECTORY_SEPARATOR);
        $path = dirname($filename);
        $content = str_replace("'''", '"""', (string) $content);

        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        file_put_contents($filename, $content);
    }

    /**
     * Runs Behat
     *
     * @param string $argumentsString
     * @When /^I run "behat(?: ((?:\"|[^"])*))?"$/
     */
    public function runBehat($args = '') {
        $args = strtr($args, ['\'' => '"']);

        $this->process->setWorkingDirectory($this->workingDir);
        $this->process->setCommandLine(
            sprintf(
                '%s %s %s %s',
                $this->phpBin,
                escapeshellarg(BEHAT_BIN_PATH),
                $args,
                '--format-settings="{\"timer\": false}" --no-colors'
            )
        );
        $this->process->start();
        $this->process->wait();
    }

    /**
     * Checks whether the command failed or passed, with output
     *
     * @param string $result
     * @param PyStringNode $output
     * @Then /^it should (fail|pass) with:$/
     */
    public function assertCommandResultWithOutput($result, PyStringNode $output) {
        $this->assertCommandResult($result);
        $this->assertCommandOutputMatches($output);
    }

    /**
     * Assert command output contains a string
     *
     * @param PyStringNode $output
     * @Then /^the output should contain:$/
     */
    public function assertCommandOutputMatches(PyStringNode $content) {
        Assertion::contains(
            $this->getOutput(),
            str_replace("'''", '"""', (string) $content),
            sprintf('Command output does not match. Actual output: %s', $this->getOutput())
        );
    }

    /**
     * Checks whether the command failed or passed
     *
     * @param string $result
     * @Then /^it should (fail|pass)$/
     */
    public function assertCommandResult($result) {
        $callback = $result === 'fail' ? 'notEq' : 'eq';
        $exitCode = $this->getExitCode();

        Assertion::$callback(
            0,
            $exitCode,
            sprintf(
                'Invalid exit code, expected 0, got %d. Command output: %s',
                $exitCode,
                $this->getOutput()
            )
        );
    }

    /**
     * Get the exit code of the process
     *
     * @return int
     */
    private function getExitCode() {
        return $this->process->getExitCode();
    }

    /**
     * Get output from the process
     *
     * @return string
     */
    private function getOutput() {
        $output = $this->process->getErrorOutput() . $this->process->getOutput();

        return trim(preg_replace('/ +$/m', '', $output));
    }

    /**
     * Recursively delete a directory
     *
     * @param string $path Path to a file or a directory
     */
    private static function rmdir($path) {
        foreach (glob($path . '/*') as $file) {
            if (is_dir($file)) {
                self::rmdir($file);
            } else {
                unlink($file);
            }
        }

        // Remove the remaining directory
        rmdir($path);
    }
}
