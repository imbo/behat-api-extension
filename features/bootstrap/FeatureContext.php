<?php declare(strict_types=1);
use Assert\Assertion;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class FeatureContext implements Context
{
    /**
     * PHP binary used to trigger Behat from the scenarios.
     */
    private ?string $phpBin = null;

    /**
     * Process instance for executing processes.
     */
    private ?Process $process = null;

    /**
     * The working directory where files can be created.
     */
    private ?string $workingDir = null;

    /**
     * Remove test dir (/tmp/behat-api-extension) before and after tests if it exists.
     *
     * @BeforeSuite
     *
     * @AfterSuite
     */
    public static function emptyTestDir(): void
    {
        $testDir = sys_get_temp_dir().\DIRECTORY_SEPARATOR.'behat-api-extension';

        if (is_dir($testDir)) {
            self::rmDir($testDir);
        }
    }

    /**
     * Prepare a scenario.
     *
     * @throws RuntimeException
     *
     * @BeforeScenario
     */
    public function prepareScenario(): void
    {
        $dir = sys_get_temp_dir().\DIRECTORY_SEPARATOR.'behat-api-extension'.\DIRECTORY_SEPARATOR.microtime(true);
        mkdir($dir.'/features/bootstrap', 0777, true);

        // Locate the php binary
        if (($bin = (new PhpExecutableFinder())->find()) === false) {
            throw new RuntimeException('Unable to find the PHP executable.');
        }

        $this->workingDir = $dir;
        $this->phpBin = $bin;
    }

    /**
     * Creates a file with specified name and content in the current working dir.
     *
     * @param string       $filename Name of the file relative to the working dir
     * @param PyStringNode $content  Content of the file
     * @param bool         $readable Whether or not the created file is readable
     *
     * @Given a file named :filename with:
     */
    public function createFile(string $filename, PyStringNode $content, bool $readable = true): void
    {
        $filename = rtrim((string) $this->workingDir, \DIRECTORY_SEPARATOR).\DIRECTORY_SEPARATOR.ltrim($filename, \DIRECTORY_SEPARATOR);
        $path = dirname($filename);
        $content = str_replace("'''", '"""', (string) $content);

        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        file_put_contents($filename, $content);

        if (!$readable) {
            chmod($filename, 0000);
        }
    }

    /**
     * Creates a non-readable file with specified name and content in the current working dir.
     *
     * @param string       $filename Name of the file relative to the working dir
     * @param PyStringNode $content  Content of the file
     *
     * @Given a non-readable file named :filename with:
     */
    public function createNonReadableFile(string $filename, PyStringNode $content): void
    {
        $this->createFile($filename, $content, false);
    }

    /**
     * Runs Behat.
     *
     * @throws RuntimeException
     *
     * @When /^I run "behat(?: ((?:\"|[^"])*))?"$/
     */
    public function runBehat(string $args = ''): void
    {
        if (!defined('BEHAT_BIN_PATH')) {
            throw new RuntimeException('Missing BEHAT_BIN_PATH constant');
        }

        $args = strtr($args, ['\'' => '"']);

        $this->process = Process::fromShellCommandline(
            sprintf(
                '%s %s %s %s',
                (string) $this->phpBin,
                escapeshellarg((string) BEHAT_BIN_PATH),
                $args,
                '--format-settings="{\"timer\": false}" --no-colors',
            ),
            $this->workingDir,
        );

        $this->process->start();
        $this->process->wait();
    }

    /**
     * Checks whether the command failed or passed, with output.
     *
     * @Then /^it should (fail|pass) with:$/
     */
    public function assertCommandResultWithOutput(string $result, PyStringNode $output): void
    {
        $this->assertCommandResult($result);
        $this->assertCommandOutputMatches($output);
    }

    /**
     * Assert command output contains a string.
     *
     * @Then the output should contain:
     */
    public function assertCommandOutputMatches(PyStringNode $content): void
    {
        Assertion::contains(
            $this->getOutput(),
            str_replace("'''", '"""', (string) $content),
            sprintf('Command output does not match. Actual output: %s', $this->getOutput()),
        );
    }

    /**
     * Checks whether the command failed or passed.
     *
     * @Then /^it should (fail|pass)$/
     */
    public function assertCommandResult(string $result): void
    {
        $exitCode = $this->getExitCode();

        // Escape % as the callback will pass this value to sprintf() if the assertion fails, and
        // sprintf might complain about too few arguments as the output might contain stuff like %s
        // or %d.
        $output = str_replace('%', '%%', $this->getOutput());

        if ('fail' === $result) {
            $callback = 'notEq';
            $errorMessage = sprintf(
                'Invalid exit code, did not expect 0. Command output: %s',
                $output,
            );
        } else {
            $callback = 'eq';
            $errorMessage = sprintf(
                'Expected exit code 0, got %d. Command output: %s',
                $exitCode,
                $output,
            );
        }

        Assertion::$callback(0, $exitCode, $errorMessage);
    }

    /**
     * Get the exit code of the process.
     *
     * @throws RuntimeException
     */
    private function getExitCode(): int
    {
        if (null === $this->process) {
            throw new RuntimeException('No process is running');
        }

        $code = $this->process->getExitCode();

        if (null === $code) {
            throw new RuntimeException('Process is not finished');
        }

        return $code;
    }

    /**
     * Get output from the process.
     *
     * @throws RuntimeException
     */
    private function getOutput(): string
    {
        if (null === $this->process) {
            throw new RuntimeException('No process is running');
        }

        $output = $this->process->getErrorOutput().$this->process->getOutput();

        return trim((string) preg_replace('/ +$/m', '', $output));
    }

    /**
     * Recursively delete a directory.
     *
     * @param string $path Path to a file or a directory
     */
    private static function rmdir(string $path): void
    {
        /** @var array<string> */
        $files = glob(sprintf('%s/*', $path));

        foreach ($files as $file) {
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
