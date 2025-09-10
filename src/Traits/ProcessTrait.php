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

  use StringTrait;


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
   * Stream output of the process while it is running.
   */
  protected bool $processStreamOutput = FALSE;

  /**
   * Characters to prefix streaming standard output.
   */
  protected static string $processStreamingStandardOutputChars = '>> ';

  /**
   * Characters to prefix streaming error output.
   */
  protected static string $processStreamingErrorOutputChars = 'XX ';

  /**
   * Standard output header for formatted output.
   */
  protected static string $processStandardOutputHeader = 'vvvvvvvvvvvv Standard output vvvvvvvvvvvv';

  /**
   * Standard output footer for formatted output.
   */
  protected static string $processStandardOutputFooter = '^^^^^^^^^^^^ Standard output ^^^^^^^^^^^^';

  /**
   * Error output header for formatted output.
   */
  protected static string $processErrorOutputHeader = 'vvvvvvvvvvvv Error output vvvvvvvvvvvv';

  /**
   * Error output footer for formatted output.
   */
  protected static string $processErrorOutputFooter = '^^^^^^^^^^^ Error output ^^^^^^^^^^^^';

  /**
   * Dim the streaming output.
   *
   * This makes the streaming output less prominent in the console.
   */
  protected static bool $processStreamingOutputShouldDim = TRUE;

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
   *   The command to run. Can be a single command or command with arguments
   *   separated by spaces (e.g., "git status" or "ls -la").
   * @param array $arguments
   *   Additional command arguments.
   *   Note: All scalar arguments are converted to strings (TRUE→"1", FALSE→"").
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
    // Parse command string to extract command and arguments as a shortcut.
    $parsed_command = $this->processParseCommand($command);
    $base_command = array_shift($parsed_command);
    $parsed_arguments = $parsed_command;

    // Validate the base command contains only allowed characters.
    if (preg_match('/[^a-zA-Z0-9_\-.\/]/', $base_command)) {
      throw new \InvalidArgumentException(sprintf('Invalid command: %s. Only alphanumeric characters, dots, dashes, underscores and slashes are allowed.', $base_command));
    }

    $all_arguments = array_values(array_merge($parsed_arguments, $arguments));

    foreach ($all_arguments as &$arg) {
      if (!is_scalar($arg)) {
        throw new \InvalidArgumentException("All arguments must be scalar values.");
      }
      $arg = (string) $arg;
    }
    unset($arg);

    foreach ($env as &$env_value) {
      if (!is_scalar($env_value)) {
        throw new \InvalidArgumentException("All environment variables must be scalar values.");
      }
      $env_value = (string) $env_value;
    }
    unset($env_value);

    $cmd = array_merge([$base_command], $all_arguments);

    $inputs = empty($inputs) ? NULL : implode(PHP_EOL, $inputs) . PHP_EOL;

    if ($this->process instanceof Process) {
      $this->process->stop();
      $this->process = NULL;
    }

    $this->process = new Process(
      $cmd,
      $this->processCwd,
      $env,
      $inputs,
      $timeout
    );

    $this->process->setIdleTimeout($idle_timeout);

    try {
      $this->process->run($this->processStreamOutput ? $this->processStreamingOutputCallback() : NULL);
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
   * Parses a command string into command and arguments.
   *
   * Handles quoted arguments and escaping properly. Supports both single
   * and double quotes. Also supports the end-of-options marker (--) which
   * stops option parsing and treats all subsequent tokens as positional
   * arguments.
   *
   * Note: This parser intentionally allows backslash escaping inside single
   * quotes (e.g., 'It\'s working'), which deviates from POSIX shell behavior
   * where backslashes are literal inside single quotes. This provides more
   * intuitive escaping for users.
   *
   * @param string $command
   *   The command string to parse.
   *
   * @return array
   *   Array with command as first element and arguments as subsequent elements.
   */
  protected function processParseCommand(string $command): array {
    $command = trim($command);
    if (empty($command)) {
      throw new \InvalidArgumentException('Command cannot be empty.');
    }

    $parts = [];
    $current = '';
    $in_quotes = FALSE;
    $quote_char = '';
    $escaped = FALSE;
    $length = strlen($command);
    $has_content = FALSE;
    $end_of_options_found = FALSE;

    for ($i = 0; $i < $length; $i++) {
      $char = $command[$i];

      if ($escaped) {
        $current .= $char;
        $escaped = FALSE;
        $has_content = TRUE;
        continue;
      }

      if ($char === '\\') {
        $escaped = TRUE;
        continue;
      }

      if (!$in_quotes && ($char === '"' || $char === "'")) {
        $in_quotes = TRUE;
        $quote_char = $char;
        $has_content = TRUE;
        continue;
      }

      if ($in_quotes && $char === $quote_char) {
        $in_quotes = FALSE;
        $quote_char = '';
        continue;
      }

      if (!$in_quotes && ($char === ' ' || $char === "\t")) {
        if ($current !== '' || $has_content) {
          // Check for end-of-options marker (--) only if not already found
          // and not inside quotes.
          if (!$end_of_options_found && $current === '--') {
            $end_of_options_found = TRUE;
            // Add the -- marker to the parts array so it reaches the command.
            $parts[] = $current;
            $current = '';
            $has_content = FALSE;
            continue;
          }

          $parts[] = $current;
          $current = '';
          $has_content = FALSE;
        }
        continue;
      }

      $current .= $char;
      $has_content = TRUE;
    }

    if ($in_quotes) {
      throw new \InvalidArgumentException('Unclosed quote in command string.');
    }

    if ($escaped) {
      throw new \InvalidArgumentException('Trailing escape character in command string.');
    }

    if ($current !== '' || $has_content) {
      $parts[] = $current;
    }

    return $parts;
  }

  /**
   * Returns a callback to process streaming output from the running process.
   *
   * This callback formats the output with specific prefixes for standard
   * and error output, and handles line endings correctly.
   *
   * @return callable
   *   The output processing callback.
   */
  protected function processStreamingOutputCallback(): callable {
    return function ($type, $buffer): void {
      $prefix = $type === Process::ERR ? static::$processStreamingErrorOutputChars : static::$processStreamingStandardOutputChars;

      $parts = preg_split('/(\r\n|\n|\r)/', $buffer, -1, PREG_SPLIT_DELIM_CAPTURE);
      $counter = is_array($parts) ? count($parts) : 0;

      for ($i = 0; $i < $counter; $i += 2) {
        $line = $parts[$i] ?? '';
        $eol = $parts[$i + 1] ?? '';

        if ($line === '' && $eol === '') {
          continue;
        }

        $line = $prefix . $line . $eol;

        if (static::$processStreamingOutputShouldDim) {
          $line = static::dim($line);
        }

        fwrite(STDOUT, $line);
      }
    };
  }

  /**
   * Dim the given text for console output.
   *
   * @param string $text
   *   The text to dim.
   * @param string $eol
   *   The end-of-line character(s) to use. Default is "\n".
   *
   * @return string
   *   The dimmed text.
   */
  protected static function dim(string $text, string $eol = "\n"): string {
    // Ensure $eol is non-empty for explode().
    $eol = $eol ?: "\n";
    $lines = explode($eol, $text);
    $colored_lines = array_map(function (string $line): string {
      return sprintf("\033[%sm%s\033[%sm", 2, $line, 22);
    }, $lines);
    return implode($eol, $colored_lines);
  }

  /**
   * Asserts that the process executed successfully.
   *
   * Checks if the process completed with a successful exit code and provides
   * detailed error output if it failed.
   */
  public function assertProcessSuccessful(): void {
    $this->assertNotNull($this->process, 'Process is not initialized');
    if (!$this->process->isSuccessful()) {
      $this->fail('PROCESS FAILED' . PHP_EOL . $this->processFormatOutput());
    }
  }

  /**
   * Asserts that the process failed to execute.
   *
   * Checks if the process failed and provides detailed output if it
   * unexpectedly succeeded.
   */
  public function assertProcessFailed(): void {
    $this->assertNotNull($this->process, 'Process is not initialized');
    if ($this->process->isSuccessful()) {
      $this->fail('PROCESS SUCCEEDED but failure was expected' . PHP_EOL . $this->processFormatOutput());
    }
  }

  /**
   * Formats the output of the process for display.
   *
   * @return string
   *   The formatted output string.
   */
  protected function processFormatOutput(): string {
    if (!$this->process instanceof Process) {
      return 'Process is not initialized' . PHP_EOL;
    }

    $process_output = $this->process->getOutput();
    $process_error = $this->process->getErrorOutput();

    $output = PHP_EOL;
    $output .= 'Exit code: ' . $this->process->getExitCode() . PHP_EOL;
    $output .= PHP_EOL;

    if (!empty($process_output)) {
      $output .= static::$processStandardOutputHeader . PHP_EOL . PHP_EOL . trim($process_output) . PHP_EOL . PHP_EOL . static::$processStandardOutputFooter . PHP_EOL . PHP_EOL;
    }

    if (!empty($process_error)) {
      $output .= static::$processErrorOutputHeader . PHP_EOL . PHP_EOL . trim($process_error) . PHP_EOL . PHP_EOL . static::$processErrorOutputFooter . PHP_EOL . PHP_EOL;
    }

    return $output;
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
   * Supports four single-character prefixes for precise matching control:
   * - '+' = exact match present (string equals entire output)
   * - '*' = substring present (string found within output)
   * - '-' = exact match absent (string does not equal entire output)
   * - '!' = substring absent (string not found within output)
   *
   * Supports two modes:
   * - Shortcut mode: No prefixes, all strings treated as substring present
   * - Mixed mode: If any string has a prefix, ALL strings must have prefixes
   *
   * @param string|array $expected
   *   String or array of strings to check in the process output.
   *   Use '+ ' prefix for exact match present,
   *   '* ' prefix for substring present,
   *   '- ' prefix for exact match absent,
   *   '! ' prefix for substring absent.
   *   If any string has a prefix, ALL strings must have prefixes.
   *
   * @throws \RuntimeException
   *   When prefix usage is inconsistent (some have prefixes, others don't).
   */
  public function assertProcessOutputContainsOrNot(string|array $expected): void {
    $this->assertNotNull($this->process, 'Process is not initialized');

    $output = $this->process->getOutput();

    $this->assertStringContainsOrNot(
      $output,
      $expected,
      "Process output exact match failed for '%s'",
      "Process output does not contain '%s'",
      "Process output should not exactly match '%s'",
      "Process output contains '%s' but should not"
    );
  }

  /**
   * Asserts process error output contains or does not contain strings.
   *
   * Supports four single-character prefixes for precise matching control:
   * - '+' = exact match present (string equals entire error output)
   * - '*' = substring present (string found within error output)
   * - '-' = exact match absent (string does not equal entire error output)
   * - '!' = substring absent (string not found within error output)
   *
   * Supports two modes:
   * - Shortcut mode: No prefixes, all strings treated as substring present
   * - Mixed mode: If any string has a prefix, ALL strings must have prefixes
   *
   * @param string|array $expected
   *   String or array of strings to check in the process error output.
   *   Use '+ ' prefix for exact match present,
   *   '* ' prefix for substring present,
   *   '- ' prefix for exact match absent,
   *   '! ' prefix for substring absent.
   *   If any string has a prefix, ALL strings must have prefixes.
   *
   * @throws \RuntimeException
   *   When prefix usage is inconsistent (some have prefixes, others don't).
   */
  public function assertProcessErrorOutputContainsOrNot(string|array $expected): void {
    $this->assertNotNull($this->process, 'Process is not initialized');

    $output = $this->process->getErrorOutput();

    $this->assertStringContainsOrNot(
      $output,
      $expected,
      "Process error output exact match failed for '%s'",
      "Process error output does not contain '%s'",
      "Process error output should not exactly match '%s'",
      "Process error output contains '%s' but should not"
    );
  }

  /**
   * Asserts that combined process output contains expected string(s).
   *
   * Checks both standard output and error output for the presence of strings.
   *
   * @param array|string $expected
   *   Expected string or array of strings to check for in combined output.
   */
  public function assertProcessAnyOutputContains(array|string $expected): void {
    $this->assertNotNull($this->process, 'Process is not initialized');

    $output = $this->process->getOutput();
    $output .= $this->process->getErrorOutput();

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
   * Asserts that combined process output does not contain expected string(s).
   *
   * Checks both standard output and error output for the absence of strings.
   *
   * @param array|string $expected
   *   String or array of strings that should not be in combined output.
   */
  public function assertProcessAnyOutputNotContains(array|string $expected): void {
    $this->assertNotNull($this->process, 'Process is not initialized');

    $output = $this->process->getOutput();
    $output .= $this->process->getErrorOutput();

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
   * Asserts combined process output contains or does not contain strings.
   *
   * Checks both standard output and error output combined into a single string.
   * Supports four single-character prefixes for precise matching control:
   * - '+' = exact match present (string equals entire combined output)
   * - '*' = substring present (string found within combined output)
   * - '-' = exact match absent (string does not equal entire combined output)
   * - '!' = substring absent (string not found within combined output)
   *
   * Supports two modes:
   * - Shortcut mode: No prefixes, all strings treated as substring present
   * - Mixed mode: If any string has a prefix, ALL strings must have prefixes
   *
   * @param string|array $expected
   *   String or array of strings to check in combined process output.
   *   Use '+ ' prefix for exact match present,
   *   '* ' prefix for substring present,
   *   '- ' prefix for exact match absent,
   *   '! ' prefix for substring absent.
   *   If any string has a prefix, ALL strings must have prefixes.
   *
   * @throws \RuntimeException
   *   When prefix usage is inconsistent (some have prefixes, others don't).
   */
  public function assertProcessAnyOutputContainsOrNot(string|array $expected): void {
    $this->assertNotNull($this->process, 'Process is not initialized');

    $output = $this->process->getOutput();
    $output .= $this->process->getErrorOutput();

    $this->assertStringContainsOrNot(
      $output,
      $expected,
      "Process output exact match failed for '%s'",
      "Process output does not contain '%s'",
      "Process output should not exactly match '%s'",
      "Process output contains '%s' but should not"
    );
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
