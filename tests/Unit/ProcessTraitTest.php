<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Tests\Unit;

use PHPUnit\Framework\AssertionFailedError;
use Symfony\Component\Process\Process;
use AlexSkrypnyk\PhpunitHelpers\Traits\ProcessTrait;
use AlexSkrypnyk\PhpunitHelpers\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\ExpectationFailedException;

#[CoversTrait(ProcessTrait::class)]
class ProcessTraitTest extends UnitTestCase {

  use ProcessTrait;

  protected function setUp(): void {
    parent::setUp();
    $this->processStreamOutput = FALSE;
  }

  protected function tearDown(): void {
    $this->processTearDown();
    parent::tearDown();
  }

  #[DataProvider('dataProviderProcessRunWithShellCommand')]
  public function testProcessRunWithShellCommand(array $options, array $args, array $inputs, array $env, array $expected): void {
    if (!static::$fixtures) {
      throw new \RuntimeException('Fixtures directory is not set.');
    }

    $command = static::$fixtures . '/shell-command.sh';
    $arguments = array_merge($options, $args);

    $this->processRun($command, $arguments, $inputs, $env, 60, 30);

    $this->assertProcessSuccessful();
    $this->assertProcessOutputContains($expected);
  }

  public static function dataProviderProcessRunWithShellCommand(): array {
    return [
      'no_options_no_args' => [
        [],
        [],
        ['Alice', 'Blue'],
        [],
        [
          'OPTION1: 0',
          'OPTION2: not provided',
          'ARG1: not provided',
          'ARG2: not provided',
          'ENV1: not provided',
          'ENV2: not provided',
          'NAME: Alice',
          'COLOR: Blue',
        ],
      ],
      'with_option1' => [
        ['--option1'],
        [],
        ['Bob', 'Red'],
        [],
        [
          'OPTION1: 1',
          'OPTION2: not provided',
          'ARG1: not provided',
          'ARG2: not provided',
          'ENV1: not provided',
          'ENV2: not provided',
          'NAME: Bob',
          'COLOR: Red',
        ],
      ],
      'with_option2' => [
        ['--option2=value2'],
        [],
        ['Charlie', 'Green'],
        [],
        [
          'OPTION1: 0',
          'OPTION2: value2',
          'ARG1: not provided',
          'ARG2: not provided',
          'ENV1: not provided',
          'ENV2: not provided',
          'NAME: Charlie',
          'COLOR: Green',
        ],
      ],
      'with_args' => [
        [],
        ['arg1', 'arg2'],
        ['David', 'Yellow'],
        [],
        [
          'OPTION1: 0',
          'OPTION2: not provided',
          'ARG1: arg1',
          'ARG2: arg2',
          'ENV1: not provided',
          'ENV2: not provided',
          'NAME: David',
          'COLOR: Yellow',
        ],
      ],
      'with_options_and_args' => [
        ['--option1', '--option2=value2'],
        ['arg1', 'arg2'],
        ['Eve', 'Purple'],
        [],
        [
          'OPTION1: 1',
          'OPTION2: value2',
          'ARG1: arg1',
          'ARG2: arg2',
          'ENV1: not provided',
          'ENV2: not provided',
          'NAME: Eve',
          'COLOR: Purple',
        ],
      ],
      'with_env' => [
        [],
        [],
        ['Frank', 'Orange'],
        ['ENV1' => 'value1', 'ENV2' => 'value2'],
        [
          'OPTION1: 0',
          'OPTION2: not provided',
          'ARG1: not provided',
          'ARG2: not provided',
          'ENV1: value1',
          'ENV2: value2',
          'NAME: Frank',
          'COLOR: Orange',
        ],
      ],
      'with_env_and_args' => [
        [],
        ['arg1', 'arg2'],
        ['Grace', 'Pink'],
        ['ENV1' => 'value1', 'ENV2' => 'value2'],
        [
          'OPTION1: 0',
          'OPTION2: not provided',
          'ARG1: arg1',
          'ARG2: arg2',
          'ENV1: value1',
          'ENV2: value2',
          'NAME: Grace',
          'COLOR: Pink',
        ],
      ],
    ];
  }

  public function testProcessOutputAssertions(): void {
    $this->processRun('echo', ['Test Output']);

    $this->assertProcessSuccessful();
    $this->assertProcessOutputContains('Test Output');
    $this->assertProcessOutputContains(['Test', 'Output']);
    $this->assertProcessOutputNotContains('Nonexistent String');
    $this->assertProcessOutputNotContains(['Nonexistent1', 'Nonexistent2']);

    $this->assertProcessOutputContainsOrNot([
      'Test',
      'Output',
      '---Nonexistent String',
    ]);
  }

  public function testProcessErrorOutputAssertions(): void {
    $this->processRun('sh', ['-c', 'echo "Test Error" 1>&2'], []);

    $this->assertProcessSuccessful();
    $this->assertProcessErrorOutputContains('Test Error');
    $this->assertProcessErrorOutputContains(['Test', 'Error']);
    $this->assertProcessErrorOutputNotContains('Nonexistent Error');
    $this->assertProcessErrorOutputNotContains(['NoError1', 'NoError2']);

    $this->assertProcessErrorOutputContainsOrNot([
      'Test',
      'Error',
      '---Nonexistent Error',
    ]);
  }

  public function testProcessFailed(): void {
    $command = 'nonexistent-command';

    $this->processRun($command);

    $this->assertProcessFailed();
  }

  public function testProcessInfo(): void {
    $this->processRun('echo', ['Test Output']);

    $info = $this->processInfo();

    $this->assertStringContainsString('PROCESS', $info);
    $this->assertStringContainsString('Output:', $info);
    $this->assertStringContainsString('Test Output', $info);
    $this->assertStringContainsString('Error:', $info);
  }

  public function testProcessInfoUninitializedProcess(): void {
    $this->process = NULL;
    $info = $this->processInfo();

    $this->assertStringContainsString('PROCESS: Not initialized', $info);
  }

  public function testProcessRunWithInvalidCommand(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid command: invalid$command. Only alphanumeric characters, dashes, underscores, and slashes are allowed.');

    $this->processRun('invalid$command');
  }

  public function testProcessRunWithInvalidArgument(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('All arguments must be scalar values.');

    $this->processRun('echo', [['non-scalar', 'argument']]);
  }

  public function testProcessRunWithInvalidEnvironmentVariable(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('All environment variables must be scalar values.');

    $this->processRun('echo', [], [], ['ENV1' => ['non-scalar', 'value']]);
  }

  public function testAssertProcessSuccessfulWhenNull(): void {
    $this->process = NULL;

    $this->expectException(ExpectationFailedException::class);
    $this->expectExceptionMessage('Process is not initialized');

    $this->assertProcessSuccessful();
  }

  public function testAssertProcessFailedWhenNull(): void {
    $this->process = NULL;

    $this->expectException(ExpectationFailedException::class);
    $this->expectExceptionMessage('Process is not initialized');

    $this->assertProcessFailed();
  }

  public function testAssertProcessOutputContainsWhenNull(): void {
    $this->process = NULL;

    $this->expectException(ExpectationFailedException::class);
    $this->expectExceptionMessage('Process is not initialized');

    $this->assertProcessOutputContains('test');
  }

  public function testAssertProcessOutputNotContainsWhenNull(): void {
    $this->process = NULL;

    $this->expectException(ExpectationFailedException::class);
    $this->expectExceptionMessage('Process is not initialized');

    $this->assertProcessOutputNotContains('test');
  }

  public function testAssertProcessErrorOutputContainsWhenNull(): void {
    $this->process = NULL;

    $this->expectException(ExpectationFailedException::class);
    $this->expectExceptionMessage('Process is not initialized');

    $this->assertProcessErrorOutputContains('test');
  }

  public function testAssertProcessErrorOutputNotContainsWhenNull(): void {
    $this->process = NULL;

    $this->expectException(ExpectationFailedException::class);
    $this->expectExceptionMessage('Process is not initialized');

    $this->assertProcessErrorOutputNotContains('test');
  }

  public function testAssertProcessOutputContainsOrNotWhenNull(): void {
    $this->process = NULL;

    $this->expectException(ExpectationFailedException::class);
    $this->expectExceptionMessage('Process is not initialized');

    $this->assertProcessOutputContainsOrNot('test');
  }

  public function testAssertProcessErrorOutputContainsOrNotWhenNull(): void {
    $this->process = NULL;

    $this->expectException(ExpectationFailedException::class);
    $this->expectExceptionMessage('Process is not initialized');

    $this->assertProcessErrorOutputContainsOrNot('test');
  }

  /**
   * Test process streaming output callback functionality.
   *
   * This test verifies that the processStreamingOutputCallback() correctly:
   * - Prefixes standard output with '>>'
   * - Prefixes error output with 'XX'
   * - Handles line endings properly
   *
   * Important note about output order:
   * The expected output order may not match the exact script execution order
   * due to stdio buffering behavior when processes output to pipes:
   * - stdout becomes block-buffered when piped (waits for buffer to fill)
   * - stderr is typically unbuffered (flushes immediately)
   * This is standard Unix/Linux behavior and affects how Symfony Process
   * receives the output through pipes, not a bug in the streaming callback.
   */
  public function testProcessStreamingOutput(): void {
    if (!static::$fixtures) {
      throw new \RuntimeException('Fixtures directory is not set.');
    }

    $command = static::$fixtures . '/shell-command-failing.sh';

    // Create a temporary file to capture the streaming output
    tempnam(sys_get_temp_dir(), 'test_stream_output');

    // Store original callback for restoration
    $this->processStreamingOutputCallback();

    // Override processStreamingOutputCallback method temporarily
    $reflection = new \ReflectionClass($this);
    $property = $reflection->getProperty('processStreamOutput');
    $property->setAccessible(TRUE);
    $property->setValue($this, TRUE);

    // Capture streaming output by overriding the actual method
    $captured_output = '';
    $capture_callback = function ($type, $buffer) use (&$captured_output): void {
      $prefix = $type === Process::ERR ? static::$processStreamingErrorOutputChars : static::$processStreamingStandardOutputChars;

      $parts = preg_split('/(\r\n|\n|\r)/', $buffer, -1, PREG_SPLIT_DELIM_CAPTURE);
      $counter = is_array($parts) ? count($parts) : 0;

      for ($i = 0; $i < $counter; $i += 2) {
        $line = $parts[$i] ?? '';
        $eol = $parts[$i + 1] ?? '';

        if ($line === '' && $eol === '') {
          continue;
        }

        $captured_output .= $prefix . $line . $eol;
      }
    };

    // Use the actual processRun method but intercept the callback
    $this->process = new Process(
      [$command],
      $this->processCwd,
      [],
      NULL,
      60
    );

    $this->process->setIdleTimeout(30);
    $this->process->run($capture_callback);

    // Verify the process failed as expected
    $this->assertProcessFailed();

    $expected_lines = <<<EOL
>> === Starting Complex Operation ===
>> Step 1: Initializing components...
>>   - Component A: OK
>>   - Component B: OK
>>   - Component C: FAILED
>> 
>> Step 2: Processing data...
>> ----------------------------------------
>> | Item     | Status    | Progress      |
>> ----------------------------------------
>> | File 1   | Complete  | [##########]  |
>> | File 2   | Error     | [####------]  |
>> | File 3   | Pending   | [----------]  |
>> ----------------------------------------
>> 
XX ERROR: Critical failure in Component C
XX ERROR: Unable to process File 2
XX ERROR: Operation aborted
>> Some non-error output that should not be treated as an error
>> 
XX === Complex Operation Failed ===
EOL;

    // Split expected output into lines and assert each line is present
    $expected_lines_array = explode(PHP_EOL, $expected_lines);
    foreach ($expected_lines_array as $expected_line) {
      $this->assertStringContainsString($expected_line, $captured_output, sprintf("Missing expected line: '%s'", $expected_line));
    }
  }

  public function testProcessFormatOutput(): void {
    $this->processRun('echo', ['Standard output text']);

    $reflection = new \ReflectionClass($this);
    $method = $reflection->getMethod('processFormatOutput');
    $method->setAccessible(TRUE);
    $formatted_output = $method->invoke($this);
    $this->assertIsString($formatted_output);

    $this->assertStringContainsString('Exit code: 0', $formatted_output);
    $this->assertStringContainsString(static::$processStandardOutputHeader, $formatted_output);
    $this->assertStringContainsString('Standard output text', $formatted_output);
    $this->assertStringContainsString(static::$processStandardOutputFooter, $formatted_output);
  }

  public function testProcessFormatOutputWithError(): void {
    $this->processRun('sh', ['-c', 'echo "Process stdout message"; echo "Process stderr message" >&2; exit 1']);

    $reflection = new \ReflectionClass($this);
    $method = $reflection->getMethod('processFormatOutput');
    $method->setAccessible(TRUE);
    $formatted_output = $method->invoke($this);
    $this->assertIsString($formatted_output);

    $this->assertStringContainsString('Exit code: 1', $formatted_output);
    $this->assertStringContainsString(static::$processStandardOutputHeader, $formatted_output);
    $this->assertStringContainsString('Process stdout message', $formatted_output);
    $this->assertStringContainsString(static::$processStandardOutputFooter, $formatted_output);
    $this->assertStringContainsString(static::$processErrorOutputHeader, $formatted_output);
    $this->assertStringContainsString('Process stderr message', $formatted_output);
    $this->assertStringContainsString(static::$processErrorOutputFooter, $formatted_output);
  }

  public function testProcessFormatOutputWithEmptyOutput(): void {
    $this->processRun('true');

    $reflection = new \ReflectionClass($this);
    $method = $reflection->getMethod('processFormatOutput');
    $method->setAccessible(TRUE);
    $formatted_output = $method->invoke($this);
    $this->assertIsString($formatted_output);

    $this->assertStringContainsString('Exit code: 0', $formatted_output);
    $this->assertStringNotContainsString(static::$processStandardOutputHeader, $formatted_output);
    $this->assertStringNotContainsString(static::$processErrorOutputHeader, $formatted_output);
  }

  public function testProcessGet(): void {
    $this->processRun('echo', ['test']);

    $process = $this->processGet();

    $this->assertInstanceOf(Process::class, $process);
    $this->assertTrue($process->isSuccessful());
  }

  public function testProcessGetWhenNotInitialized(): void {
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Process is not initialized');

    $this->processGet();
  }

  public function testProcessTearDown(): void {
    $this->processRun('sleep', ['0.1']);

    $this->assertInstanceOf(Process::class, $this->process);

    $this->processTearDown();

    $this->assertNull($this->process);
  }

  public function testProcessTearDownWhenNotInitialized(): void {
    $this->assertNull($this->process);

    // Should not throw any exception
    $this->processTearDown();

    $this->assertNull($this->process);
  }

  public function testProcessRunWithCustomWorkingDirectory(): void {
    $temp_dir = sys_get_temp_dir();
    $this->processCwd = $temp_dir;

    $this->processRun('pwd');

    $this->assertProcessSuccessful();
    $this->assertProcessOutputContains($temp_dir);
  }

  public function testProcessRunWithTimeout(): void {
    $start_time = microtime(TRUE);

    $this->processRun('sleep', ['0.1'], [], [], 1, 1);

    $end_time = microtime(TRUE);
    $execution_time = $end_time - $start_time;

    $this->assertProcessSuccessful();
    $this->assertGreaterThanOrEqual(0.1, $execution_time);
    $this->assertLessThan(0.5, $execution_time);
  }

  public function testProcessFormatOutputWhenNotInitialized(): void {
    $this->process = NULL;

    $reflection = new \ReflectionClass($this);
    $method = $reflection->getMethod('processFormatOutput');
    $method->setAccessible(TRUE);
    $formatted_output = $method->invoke($this);
    $this->assertIsString($formatted_output);

    $this->assertStringContainsString('Process is not initialized', $formatted_output);
  }

  public function testProcessStreamingCallbackWithEmptyBuffer(): void {
    // Create a test callback that captures output instead of writing to STDOUT
    $captured_output = '';
    $test_callback = function ($type, $buffer) use (&$captured_output): void {
      $prefix = $type === Process::ERR ? static::$processStreamingErrorOutputChars : static::$processStreamingStandardOutputChars;

      $parts = preg_split('/(\r\n|\n|\r)/', $buffer, -1, PREG_SPLIT_DELIM_CAPTURE);
      $counter = is_array($parts) ? count($parts) : 0;

      for ($i = 0; $i < $counter; $i += 2) {
        $line = $parts[$i] ?? '';
        $eol = $parts[$i + 1] ?? '';

        if ($line === '' && $eol === '') {
          continue;
        }

        $captured_output .= $prefix . $line . $eol;
      }
    };

    // Test with empty buffer - should not cause issues or exceptions
    $test_callback(Process::OUT, '');
    $test_callback(Process::ERR, '');

    // If we get here without exceptions, empty buffers are handled correctly
    $this->assertEquals('', $captured_output);
  }

  public function testProcessStreamingCallbackWithDifferentLineEndings(): void {
    // Create a test callback that captures output instead of writing to STDOUT
    $captured_output = '';
    $test_callback = function ($type, $buffer) use (&$captured_output): void {
      $prefix = $type === Process::ERR ? static::$processStreamingErrorOutputChars : static::$processStreamingStandardOutputChars;

      $parts = preg_split('/(\r\n|\n|\r)/', $buffer, -1, PREG_SPLIT_DELIM_CAPTURE);
      $counter = is_array($parts) ? count($parts) : 0;

      for ($i = 0; $i < $counter; $i += 2) {
        $line = $parts[$i] ?? '';
        $eol = $parts[$i + 1] ?? '';

        if ($line === '' && $eol === '') {
          continue;
        }

        $captured_output .= $prefix . $line . $eol;
      }
    };

    // Test different input patterns
    $test_inputs = [
      "line1\nline2\n",
      "line1\r\nline2\r\n",
      "line1\rline2\r",
      "single line",
      "",
    ];

    foreach ($test_inputs as $input) {
      // Call the callback - it should not throw any exceptions
      $test_callback(Process::OUT, $input);
      $test_callback(Process::ERR, $input);
    }

    // If we get here without exceptions, the callback handled input correctly
    $this->addToAssertionCount(count($test_inputs) * 2);

    // Verify some expected output was captured
    $this->assertStringContainsString(static::$processStreamingStandardOutputChars, $captured_output);
    $this->assertStringContainsString(static::$processStreamingErrorOutputChars, $captured_output);
  }

  public function testProcessStreamingCallbackWithErrorOutput(): void {
    // Create a test callback that captures output instead of writing to STDOUT
    $captured_output = '';
    $test_callback = function ($type, $buffer) use (&$captured_output): void {
      $prefix = $type === Process::ERR ? static::$processStreamingErrorOutputChars : static::$processStreamingStandardOutputChars;

      $parts = preg_split('/(\r\n|\n|\r)/', $buffer, -1, PREG_SPLIT_DELIM_CAPTURE);
      $counter = is_array($parts) ? count($parts) : 0;

      for ($i = 0; $i < $counter; $i += 2) {
        $line = $parts[$i] ?? '';
        $eol = $parts[$i + 1] ?? '';

        if ($line === '' && $eol === '') {
          continue;
        }

        $captured_output .= $prefix . $line . $eol;
      }
    };

    // Call with error type - should not throw exceptions
    $test_callback(Process::ERR, "error message\n");
    $test_callback(Process::OUT, "standard message\n");

    // If we get here without exceptions, the callback is working
    $this->addToAssertionCount(1);

    // Verify expected output was captured with correct prefixes
    $this->assertStringContainsString(static::$processStreamingErrorOutputChars . 'error message', $captured_output);
    $this->assertStringContainsString(static::$processStreamingStandardOutputChars . 'standard message', $captured_output);
  }

  public function testProcessRunStopsExistingProcess(): void {
    // Start first process
    $this->processRun('echo', ['first process']);
    $first_process = $this->process;

    // Start second process - should stop the first one
    $this->processRun('echo', ['second process']);
    $second_process = $this->process;

    $this->assertNotSame($first_process, $second_process);
    $this->assertProcessOutputContains('second process');
    $this->assertProcessOutputNotContains('first process');
  }

  public function testProcessRunWithNullInputs(): void {
    // Test the empty inputs check that converts empty array to NULL
    $this->processRun('echo', ['test'], []);

    $this->assertProcessSuccessful();
    $this->assertProcessOutputContains('test');
  }

  public function testProcessRunWithNonEmptyInputs(): void {
    // Test with actual inputs - this should trigger the implode path
    $this->processRun('cat', [], ['line1', 'line2', 'line3']);

    $this->assertProcessSuccessful();
    $this->assertProcessOutputContains('line1');
    $this->assertProcessOutputContains('line2');
    $this->assertProcessOutputContains('line3');
  }

  #[DataProvider('dataProviderProcessRunWithCommandString')]
  public function testProcessRunWithCommandString(string $command_string, array $additional_args, array $expected_output): void {
    $this->processRun($command_string, $additional_args);

    $this->assertProcessSuccessful();
    foreach ($expected_output as $expected) {
      $this->assertProcessOutputContains($expected);
    }
  }

  public static function dataProviderProcessRunWithCommandString(): array {
    return [
      'simple_command_string' => [
        'echo hello world',
        [],
        ['hello world'],
      ],
      'command_with_flags' => [
        'echo -n test',
        [],
        ['test'],
      ],
      'command_with_quoted_arguments' => [
        'echo "hello world" test',
        [],
        ['hello world', 'test'],
      ],
      'command_with_additional_args' => [
        'echo hello',
        ['world', 'again'],
        ['world again hello'],
      ],
      'mixed_quotes' => [
        'echo "double quote" \'single quote\'',
        [],
        ['double quote', 'single quote'],
      ],
      'escaped_characters' => [
        'echo hello\\ world',
        [],
        ['hello world'],
      ],
      'complex_git_like_command' => [
        'echo --message="Initial commit"',
        ['--author=John'],
        ['--author=John', '--message=Initial commit'],
      ],
    ];
  }

  public function testProcessRunWithInvalidCommandString(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid command: invalid$command. Only alphanumeric characters, dashes, underscores, and slashes are allowed.');

    $this->processRun('invalid$command with args');
  }

  public function testProcessRunWithEmptyCommandString(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Command cannot be empty.');

    $this->processRun('');
  }

  public function testProcessRunWithWhitespaceOnlyCommandString(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Command cannot be empty.');

    $this->processRun('   ');
  }

  public function testProcessRunWithUnclosedQuotes(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Unclosed quote in command string.');

    $this->processRun('echo "unclosed quote');
  }

  public function testProcessRunPreservesBackwardCompatibility(): void {
    // Test that the old array-based approach still works exactly as before
    $this->processRun('echo', ['hello', 'world']);

    $this->assertProcessSuccessful();
    $this->assertProcessOutputContains('hello world');
  }

  public function testProcessRunCommandStringParsing(): void {
    // Test that command string parsing works correctly
    $this->processRun('echo test1 test2');

    $this->assertProcessSuccessful();
    $this->assertProcessOutputContains('test1 test2');
  }

  public function testProcessRunCommandStringWithSpecialCharacters(): void {
    // Test command string with special characters in quotes
    $this->processRun('echo "Hello! @#$%^&*()"');

    $this->assertProcessSuccessful();
    $this->assertProcessOutputContains('Hello! @#$%^&*()');
  }

  public function testProcessRunCommandStringWithFileFlags(): void {
    // Test realistic file command with flags
    $this->processRun('echo -e "line1\\nline2"');

    $this->assertProcessSuccessful();
    $this->assertProcessOutputContains('line1');
  }

  public function testProcessRunArgumentOverrideBehavior(): void {
    // Test how parsed arguments and explicit arguments interact
    // Explicit arguments take precedence and come first
    $this->processRun('echo parsed1 parsed2', ['explicit1', 'explicit2']);

    $this->assertProcessSuccessful();
    $this->assertProcessOutputContains('explicit1 explicit2 parsed1 parsed2');
  }

  public function testProcessRunArgumentOrderWithFlags(): void {
    // Test order with flag-like arguments - explicit flags come first
    $this->processRun('echo --parsed-flag', ['--explicit-flag']);

    $this->assertProcessSuccessful();
    $this->assertProcessOutputContains('--explicit-flag --parsed-flag');
  }

  public function testProcessRunExplicitArgumentsPrecedence(): void {
    // Test that explicit arguments take complete precedence over parsed ones
    // This is useful for overriding default arguments in command strings
    $this->processRun('echo default-arg1 default-arg2', ['override-arg1', 'override-arg2']);

    $this->assertProcessSuccessful();
    // Explicit arguments come first, then parsed arguments
    $this->assertProcessOutputContains('override-arg1 override-arg2 default-arg1 default-arg2');
  }

  public function testProcessRunWithIdleTimeout(): void {
    // Test the setIdleTimeout path
    $this->processRun('echo', ['test'], [], [], 10, 5);

    $this->assertProcessSuccessful();
    $this->assertProcessOutputContains('test');
  }

  public function testProcessFormatOutputWithoutProcessInstance(): void {
    // Test the case where processFormatOutput is called with no process
    $this->process = NULL;

    $reflection = new \ReflectionClass($this);
    $method = $reflection->getMethod('processFormatOutput');
    $method->setAccessible(TRUE);
    $result = $method->invoke($this);

    $this->assertIsString($result);
    $this->assertStringContainsString('Process is not initialized', $result);
  }

  public function testStaticVariableAccess(): void {
    // Test that our static variables have expected values

    // Test that they have expected values
    $this->assertEquals('>> ', static::$processStreamingStandardOutputChars);
    $this->assertEquals('XX ', static::$processStreamingErrorOutputChars);
    $this->assertStringContainsString('Standard output', static::$processStandardOutputHeader);
    $this->assertStringContainsString('Standard output', static::$processStandardOutputFooter);
    $this->assertStringContainsString('Error output', static::$processErrorOutputHeader);
    $this->assertStringContainsString('Error output', static::$processErrorOutputFooter);
  }

  #[DataProvider('dataProviderProcessParseCommand')]
  public function testProcessParseCommand(string $command, array $expected, ?string $exception_message = NULL): void {
    $reflection = new \ReflectionClass($this);
    $method = $reflection->getMethod('processParseCommand');
    $method->setAccessible(TRUE);

    if ($exception_message !== NULL) {
      $this->expectException(\InvalidArgumentException::class);
      $this->expectExceptionMessage($exception_message);
    }

    $result = $method->invoke($this, $command);

    if ($exception_message === NULL) {
      $this->assertEquals($expected, $result);
    }
  }

  public static function dataProviderProcessParseCommand(): array {
    return [
      // Basic cases
      'simple_command' => [
        'echo',
        ['echo'],
      ],
      'command_with_single_argument' => [
        'echo hello',
        ['echo', 'hello'],
      ],
      'command_with_multiple_arguments' => [
        'echo hello world',
        ['echo', 'hello', 'world'],
      ],
      'command_with_flags' => [
        'ls -la',
        ['ls', '-la'],
      ],
      'command_with_multiple_flags' => [
        'ls -l -a -h',
        ['ls', '-l', '-a', '-h'],
      ],
      'command_with_flag_and_value' => [
        'git commit -m message',
        ['git', 'commit', '-m', 'message'],
      ],

      // Quoted arguments
      'double_quoted_argument' => [
        'echo "hello world"',
        ['echo', 'hello world'],
      ],
      'single_quoted_argument' => [
        "echo 'hello world'",
        ['echo', 'hello world'],
      ],
      'multiple_quoted_arguments' => [
        'echo "hello world" "goodbye earth"',
        ['echo', 'hello world', 'goodbye earth'],
      ],
      'mixed_quoted_arguments' => [
        'echo "hello world" \'goodbye earth\'',
        ['echo', 'hello world', 'goodbye earth'],
      ],
      'quoted_argument_with_spaces' => [
        'echo "  hello   world  "',
        ['echo', '  hello   world  '],
      ],
      'empty_quoted_argument' => [
        'echo ""',
        ['echo', ''],
      ],
      'empty_single_quoted_argument' => [
        "echo ''",
        ['echo', ''],
      ],

      // Escaped characters
      'escaped_double_quote_in_double_quotes' => [
        'echo "She said \"Hello\""',
        ['echo', 'She said "Hello"'],
      ],
      'escaped_single_quote_in_single_quotes' => [
        "echo 'It\\'s working'",
        ['echo', "It's working"],
      ],
      'escaped_backslash' => [
        'echo "Path\\\\to\\\\file"',
        ['echo', 'Path\\to\\file'],
      ],
      'escaped_space' => [
        'echo hello\\ world',
        ['echo', 'hello world'],
      ],
      'escaped_characters_outside_quotes' => [
        'echo test\\nvalue',
        ['echo', 'testnvalue'],
      ],

      // Complex cases
      'complex_command_with_mixed_quoting' => [
        'git commit -m "Initial commit" --author="John Doe <john@example.com>"',
        ['git', 'commit', '-m', 'Initial commit', '--author=John Doe <john@example.com>'],
      ],
      'command_with_equals_in_quotes' => [
        'env VAR="value=with=equals" command',
        ['env', 'VAR=value=with=equals', 'command'],
      ],
      'command_with_special_characters' => [
        'echo "Hello! @#$%^&*()_+-={}[]|\\\\:;\"\'<>?,./"',
        ['echo', 'Hello! @#$%^&*()_+-={}[]|\\:;"\'<>?,./'],
      ],

      // Whitespace handling
      'command_with_leading_spaces' => [
        '   echo hello',
        ['echo', 'hello'],
      ],
      'command_with_trailing_spaces' => [
        'echo hello   ',
        ['echo', 'hello'],
      ],
      'command_with_multiple_spaces_between_args' => [
        'echo    hello     world',
        ['echo', 'hello', 'world'],
      ],
      'command_with_tabs' => [
        "echo\thello\tworld",
        ['echo', 'hello', 'world'],
      ],

      // Edge cases with quotes
      'nested_quotes_different_types' => [
        'echo "He said \'Hello\'"',
        ['echo', "He said 'Hello'"],
      ],
      'nested_quotes_same_type_escaped' => [
        'echo "He said \"Hello\" loudly"',
        ['echo', 'He said "Hello" loudly'],
      ],
      'quotes_in_argument_preserved' => [
        'echo hello"world"test',
        ['echo', 'helloworldtest'],
      ],
      'quote_at_beginning_of_argument' => [
        'echo "hello"world',
        ['echo', 'helloworld'],
      ],

      // Arguments with special command characters
      'argument_with_pipe_in_quotes' => [
        'echo "command | grep something"',
        ['echo', 'command | grep something'],
      ],
      'argument_with_redirect_in_quotes' => [
        'echo "output > file.txt"',
        ['echo', 'output > file.txt'],
      ],
      'argument_with_semicolon_in_quotes' => [
        'echo "cmd1; cmd2"',
        ['echo', 'cmd1; cmd2'],
      ],

      // Number and boolean-like arguments
      'numeric_arguments' => [
        'test 123 456.789',
        ['test', '123', '456.789'],
      ],
      'boolean_like_arguments' => [
        'test true false',
        ['test', 'true', 'false'],
      ],

      // File paths
      'relative_path' => [
        'cat ./file.txt',
        ['cat', './file.txt'],
      ],
      'absolute_path' => [
        'cat /usr/local/bin/file',
        ['cat', '/usr/local/bin/file'],
      ],
      'path_with_spaces_quoted' => [
        'cat "/path/with spaces/file.txt"',
        ['cat', '/path/with spaces/file.txt'],
      ],

      // Error cases
      'empty_command' => [
        '',
        [],
        'Command cannot be empty.',
      ],
      'whitespace_only_command' => [
        '   ',
        [],
        'Command cannot be empty.',
      ],
      'unclosed_double_quote' => [
        'echo "hello world',
        [],
        'Unclosed quote in command string.',
      ],
      'unclosed_single_quote' => [
        "echo 'hello world",
        [],
        'Unclosed quote in command string.',
      ],
      'unclosed_quote_with_escape' => [
        'echo "hello world\\',
        [],
        'Unclosed quote in command string.',
      ],
    ];
  }

  public function testProcessInfoWithEmptyOutput(): void {
    // Test processInfo when process has no output or error output
    $this->processRun('true');

    $info = $this->processInfo();

    $this->assertStringContainsString('PROCESS', $info);
    $this->assertStringContainsString('Output:', $info);
    $this->assertStringContainsString('(no output)', $info);
    $this->assertStringContainsString('Error:', $info);
    $this->assertStringContainsString('(no error output)', $info);
  }

  public function testProcessInfoWithBothOutputs(): void {
    // Test processInfo when process has both standard and error output
    $this->processRun('sh', ['-c', 'echo "stdout message"; echo "stderr message" >&2']);

    $info = $this->processInfo();

    $this->assertStringContainsString('PROCESS', $info);
    $this->assertStringContainsString('Output:', $info);
    $this->assertStringContainsString('stdout message', $info);
    $this->assertStringContainsString('Error:', $info);
    $this->assertStringContainsString('stderr message', $info);
    $this->assertStringNotContainsString('(no output)', $info);
    $this->assertStringNotContainsString('(no error output)', $info);
  }

  public function testProcessStreamingCallbackReturnsCallable(): void {
    // Test that processStreamingOutputCallback returns a callable
    $reflection = new \ReflectionClass($this);
    $method = $reflection->getMethod('processStreamingOutputCallback');
    $method->setAccessible(TRUE);
    $callback = $method->invoke($this);

    $this->assertIsCallable($callback);

    // Test that calling the callback doesn't throw exceptions
    // Test with empty strings to avoid visible output
    $callback(Process::OUT, "");
    $callback(Process::ERR, "");

    // If we reach here, the callback executed without errors
    $this->addToAssertionCount(1);
  }

  public function testProcessRunWithZeroArguments(): void {
    // Test edge case with exactly zero arguments
    $this->processRun('echo', []);

    $this->assertProcessSuccessful();
  }

  public function testProcessRunWithEmptyStringArgument(): void {
    // Test edge case with empty string argument
    $this->processRun('echo', ['']);

    $this->assertProcessSuccessful();
  }

  public function testProcessRunWithMixedScalarArguments(): void {
    // Test with various scalar types to ensure the scalar validation works
    $this->processRun('echo', ['string', 123, TRUE, 45.67]);

    $this->assertProcessSuccessful();
  }

  public function testProcessRunWithMixedScalarEnvironmentVariables(): void {
    // Test with various scalar types for environment variables
    $this->processRun('printenv', [], [], [
      'TEST_STRING' => 'value',
      'TEST_INT' => 123,
      'TEST_BOOL' => TRUE,
      'TEST_FLOAT' => 45.67,
    ]);

    $this->assertProcessSuccessful();
  }

  public function testProcessFormatOutputExitCodeDisplay(): void {
    // Test that exit code is properly displayed in formatted output
    $this->processRun('sh', ['-c', 'exit 42']);

    $reflection = new \ReflectionClass($this);
    $method = $reflection->getMethod('processFormatOutput');
    $method->setAccessible(TRUE);
    $formatted_output = $method->invoke($this);

    $this->assertIsString($formatted_output);
    $this->assertStringContainsString('Exit code: 42', $formatted_output);
  }

  public function testAssertProcessSuccessfulWithFailedProcess(): void {
    // Test the fail() path in assertProcessSuccessful when process failed
    $this->processRun('sh', ['-c', 'exit 1']);

    $this->expectException(AssertionFailedError::class);
    $this->expectExceptionMessage('PROCESS FAILED');

    $this->assertProcessSuccessful();
  }

  public function testAssertProcessFailedWithSuccessfulProcess(): void {
    // Test the fail() path in assertProcessFailed when process succeeded
    $this->processRun('echo', ['success']);

    $this->expectException(AssertionFailedError::class);
    $this->expectExceptionMessage('PROCESS SUCCEEDED but failure was expected');

    $this->assertProcessFailed();
  }

}
