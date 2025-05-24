<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Traits;

use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

/**
 * Trait ProcessTrait.
 *
 * Runs a test process and provides assertions for its output.
 *
 * @mixin \PHPUnit\Framework\TestCase
 */
trait ProcessTrait {

  /**
   * The currently running process.
   */
  protected ?Process $process = NULL;

  /**
   * The current working directory for the process.
   *
   * Use NULL to use the working dir of the current PHP process.
   */
  protected ?string $processCwd = NULL;

  /**
   * Show output of the process.
   */
  protected bool $processShowOutput = FALSE;

  /**
   * Gets the currently running process.
   *
   * @return \Symfony\Component\Process\Process
   *   The currently running process.
   */
  public function processGet(): Process {
    if (!$this->process instanceof Process) {
      throw new \RuntimeException('Process is not initialized');
    }
    return $this->process;
  }

  /**
   * Tears down the process.
   *
   * Stops the currently running process and resets the process variable.
   */
  protected function processTearDown(): void {
    if ($this->process instanceof Process) {
      $this->process->stop();
      $this->process = NULL;
    }
  }

  /**
   * Run a process.
   *
   * @param string $command
   *   The command to run.
   * @param array $arguments
   *   Command arguments.
   * @param array $inputs
   *   Array of inputs for interactive processes.
   * @param array $env
   *   Additional environment variables.
   * @param int $timeout
   *   Process timeout in seconds.
   * @param int $idle_timeout
   *   Process idle timeout in seconds.
   *
   * @return \Symfony\Component\Process\Process
   *   The completed process.
   */
  public function processRun(
    string $command,
    array $arguments = [],
    array $inputs = [],
    array $env = [],
    int $timeout = 60,
    int $idle_timeout = 30,
  ): Process {
    if (preg_match('/[^a-zA-Z0-9_\-\.\/]/', $command)) {
      throw new \InvalidArgumentException(sprintf('Invalid command: %s. Only alphanumeric characters, dashes, underscores, and slashes are allowed.', $command));
    }

    foreach ($arguments as $arg) {
      if (!is_scalar($arg)) {
        throw new \InvalidArgumentException("All arguments must be scalar values.");
      }
    }

    foreach ($env as $env_value) {
      if (!is_scalar($env_value)) {
        throw new \InvalidArgumentException("All environment variables must be scalar values.");
      }
    }

    $cmd = array_merge([$command], $arguments);

    $inputs = empty($inputs) ? NULL : implode(PHP_EOL, $inputs) . PHP_EOL;

    $this->process = new Process(
      $cmd,
      $this->processCwd,
      $env,
      $inputs,
      $timeout
    );

    $this->process->setIdleTimeout($idle_timeout);

    try {
      $this->process->run(function ($type, $buffer): void {
        // @codeCoverageIgnoreStart
        if ($this->processShowOutput) {
          fwrite(STDOUT, $buffer);
        }
        // @codeCoverageIgnoreEnd
      });
    }
    // @codeCoverageIgnoreStart
    catch (ProcessTimedOutException $processTimedOutException) {
      print 'PROCESS TIMED OUT: ' . PHP_EOL . $processTimedOutException->getMessage() . PHP_EOL;
    }
    catch (\Exception $exception) {
      print 'PROCESS ERROR: ' . PHP_EOL . $exception->getMessage() . PHP_EOL;
    }
    // @codeCoverageIgnoreEnd
    return $this->process;
  }

  /**
   * Asserts that the process executed successfully.
   *
   * Checks if the process completed with a successful exit code and provides
   * detailed error output if it failed.
   */
  public function assertProcessSuccessful(): void {
    $this->assertNotNull($this->process, 'Process is not initialized');
    $this->assertTrue($this->process->isSuccessful(), sprintf(
      'Process failed with exit code %d: %s%sOutput:%s%s',
      $this->process->getExitCode(),
      $this->process->getErrorOutput(),
      PHP_EOL,
      PHP_EOL,
      $this->process->getOutput()
    ));
  }

  /**
   * Asserts that the process failed to execute.
   *
   * Checks if the process failed and provides detailed output if it
   * unexpectedly succeeded.
   */
  public function assertProcessFailed(): void {
    $this->assertNotNull($this->process, 'Process is not initialized');
    $this->assertFalse($this->process->isSuccessful(), sprintf(
      'Process succeeded when failure was expected.%sOutput:%s%s',
      PHP_EOL,
      PHP_EOL,
      $this->process->getOutput()
    ));
  }

  /**
   * Asserts that the process output contains expected string(s).
   *
   * @param array|string $expected
   *   Expected string or array of strings to check for in the process output.
   */
  public function assertProcessOutputContains(array|string $expected): void {
    $this->assertNotNull($this->process, 'Process is not initialized');
    $output = $this->process->getOutput();

    $expected = is_array($expected) ? $expected : [$expected];

    foreach ($expected as $value) {
      if (is_string($value)) {
        $this->assertStringContainsString($value, $output, sprintf(
          "Process output does not contain '%s'.%sOutput:%s%s",
          $value,
          PHP_EOL,
          PHP_EOL,
          $output
        ));
      }
    }
  }

  /**
   * Asserts that the process output does not contain expected string(s).
   *
   * @param array|string $expected
   *   String or array of strings that should not be in the process output.
   */
  public function assertProcessOutputNotContains(array|string $expected): void {
    $this->assertNotNull($this->process, 'Process is not initialized');
    $output = $this->process->getOutput();

    $expected = is_array($expected) ? $expected : [$expected];

    foreach ($expected as $value) {
      if (is_string($value)) {
        $this->assertStringNotContainsString($value, $output, sprintf(
          "Process output contains '%s' but should not.%sOutput:%s%s",
          $value,
          PHP_EOL,
          PHP_EOL,
          $output
        ));
      }
    }
  }

  /**
   * Asserts that the process error output contains an expected string.
   *
   * @param string $expected
   *   Expected string to check for in the process error output.
   */
  public function assertProcessErrorOutputContains(array|string $expected): void {
    $this->assertNotNull($this->process, 'Process is not initialized');
    $output = $this->process->getErrorOutput();

    $expected = is_array($expected) ? $expected : [$expected];

    foreach ($expected as $value) {
      if (is_string($value)) {
        $this->assertStringContainsString($value, $output, sprintf(
          "Process error output does not contain '%s'.%sOutput:%s%s",
          $value,
          PHP_EOL,
          PHP_EOL,
          $output
        ));
      }
    }
  }

  /**
   * Asserts that the process error output does not contain an expected string.
   *
   * @param string $expected
   *   String that should not be in the process error output.
   */
  public function assertProcessErrorOutputNotContains(array|string $expected): void {
    $this->assertNotNull($this->process, 'Process is not initialized');
    $output = $this->process->getErrorOutput();

    $expected = is_array($expected) ? $expected : [$expected];

    foreach ($expected as $value) {
      if (is_string($value)) {
        $this->assertStringNotContainsString($value, $output, sprintf(
          "Process error output contains '%s' but should not.%sOutput:%s%s",
          $value,
          PHP_EOL,
          PHP_EOL,
          $output
        ));
      }
    }
  }

  /**
   * Asserts process output contains or does not contain specified strings.
   *
   * For strings that should NOT be in the output, prefix them with '---'.
   *
   * @param string|array $expected
   *   String or array of strings to check in the process output.
   *   Prefix with '---' for strings that should not be present.
   */
  public function assertProcessOutputContainsOrNot(string|array $expected): void {
    $this->assertNotNull($this->process, 'Process is not initialized');
    $output = $this->process->getOutput();

    $expected = is_array($expected) ? $expected : [$expected];

    foreach ($expected as $value) {
      if (str_starts_with($value, '---')) {
        $value = substr($value, 4);

        $this->assertStringNotContainsString($value, $output, sprintf(
          "Process output contains '%s' but should not.%sOutput:%s%s",
          $value,
          PHP_EOL,
          PHP_EOL,
          $output
        ));
      }
      else {
        $this->assertStringContainsString($value, $output, sprintf(
          "Process output does not contain '%s'.%sOutput:%s%s",
          $value,
          PHP_EOL,
          PHP_EOL,
          $output
        ));
      }
    }
  }

  /**
   * Asserts process error output contains or does not contain strings.
   *
   * For strings that should NOT be in the error output, prefix them
   * with '---'.
   *
   * @param string|array $expected
   *   String or array of strings to check in the process error output.
   *   Prefix with '---' for strings that should not be present.
   */
  public function assertProcessErrorOutputContainsOrNot(string|array $expected): void {
    $this->assertNotNull($this->process, 'Process is not initialized');
    $output = $this->process->getErrorOutput();

    $expected = is_array($expected) ? $expected : [$expected];

    foreach ($expected as $value) {
      if (str_starts_with($value, '---')) {
        $value = substr($value, 4);

        $this->assertStringNotContainsString($value, $output, sprintf(
          "Process error output contains '%s' but should not.%sOutput:%s%s",
          $value,
          PHP_EOL,
          PHP_EOL,
          $output
        ));
      }
      else {
        $this->assertStringContainsString($value, $output, sprintf(
          "Process error output does not contain '%s'.%sOutput:%s%s",
          $value,
          PHP_EOL,
          PHP_EOL,
          $output
        ));
      }
    }
  }

  /**
   * Print the process info.
   *
   * @return string
   *   The locations' info.
   */
  public function processInfo(): string {
    if (!$this->process instanceof Process) {
      return 'PROCESS: Not initialized' . PHP_EOL;
    }
    $lines[] = 'PROCESS';
    $lines[] = 'Output:';
    $output = $this->process->getOutput();
    $lines[] = $output ?: '(no output)';
    $lines[] = 'Error:';
    $error = $this->process->getErrorOutput();
    $lines[] = $error ?: '(no error output)';
    return implode(PHP_EOL, $lines) . PHP_EOL;
  }

}
