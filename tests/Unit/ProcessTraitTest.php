<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Tests\Unit;

use AlexSkrypnyk\PhpunitHelpers\Tests\Fixtures\StreamCaptureFilter;
use AlexSkrypnyk\PhpunitHelpers\Traits\ProcessTrait;
use AlexSkrypnyk\PhpunitHelpers\UnitTestCase;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\ExpectationFailedException;
use Symfony\Component\Process\Process;

#[CoversTrait(ProcessTrait::class)]
final class ProcessTraitTest extends UnitTestCase {

  use ProcessTrait;

  protected function setUp(): void {
    parent::setUp();
    $this->processStreamingOutput = FALSE;
  }

  protected function tearDown(): void {
    $this->processTearDown();
    parent::tearDown();
  }

  #[DataProvider('dataProviderProcessRunWithShellCommand')]
  public function testProcessRunWithShellCommand(array $options, array $args, array $inputs, array $env, array $expected): void {
    if (!self::$fixtures) {
      throw new \RuntimeException('Fixtures directory is not set.');
    }

    $command = self::$fixtures . '/shell-command.sh';
    $arguments = array_merge($options, $args);

    $this->processRun($command, $arguments, $inputs, $env, 60, 30);

    $this->assertProcessSuccessful();
    $this->assertProcessOutputContains($expected);
  }

  public static function dataProviderProcessRunWithShellCommand(): \Iterator {
    yield 'no_options_no_args' => [
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
    ];
    yield 'with_option1' => [
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
    ];
    yield 'with_option2' => [
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
    ];
    yield 'with_args' => [
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
    ];
    yield 'with_options_and_args' => [
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
    ];
    yield 'with_env' => [
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
    ];
    yield 'with_env_and_args' => [
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
      '* Test',
      '* Output',
      '! Nonexistent String',
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
      '* Test',
      '* Error',
      '! Nonexistent Error',
    ]);
  }

  public function testProcessAnyOutputAssertions(): void {
    $this->processRun('sh', ['-c', 'echo "Standard Output"; echo "Error Output" 1>&2']);

    $this->assertProcessSuccessful();

    $this->assertProcessAnyOutputContains('Standard Output');
    $this->assertProcessAnyOutputContains('Error Output');
    $this->assertProcessAnyOutputContains(['Standard', 'Error']);
    $this->assertProcessAnyOutputContains(['Standard Output', 'Error Output']);

    $this->assertProcessAnyOutputNotContains('Nonexistent String');
    $this->assertProcessAnyOutputNotContains(['NotFound1', 'NotFound2']);

    $this->assertProcessAnyOutputContainsOrNot([
      '* Standard Output',
      '* Error Output',
      '! Nonexistent String',
      '! NotFound',
    ]);
  }

  public function testProcessAnyOutputAssertionsStandardOutputOnly(): void {
    $this->processRun('echo', ['Only Standard']);

    $this->assertProcessSuccessful();
    $this->assertProcessAnyOutputContains('Only Standard');
    $this->assertProcessAnyOutputNotContains('Error Content');
  }

  public function testProcessAnyOutputAssertionsErrorOutputOnly(): void {
    $this->processRun('sh', ['-c', 'echo "Only Error" 1>&2']);

    $this->assertProcessSuccessful();
    $this->assertProcessAnyOutputContains('Only Error');
    $this->assertProcessAnyOutputNotContains('Standard Content');
  }

  public function testProcessAnyOutputContainsOrNotShortcutMode(): void {
    $this->processRun('echo', ['Test Output']);

    $this->assertProcessSuccessful();

    // Without prefixes, every string must be present.
    $this->assertProcessAnyOutputContainsOrNot([
      'Test Output',
      'Test',
      'Output',
    ]);
  }

  public function testProcessAnyOutputContainsOrNotInconsistentPrefixUsage(): void {
    $this->processRun('echo', ['Test Output']);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('All strings must have valid prefixes in mixed mode');

    $this->assertProcessAnyOutputContainsOrNot([
      '* Test Output',
      'Missing prefix',
    ]);
  }

  public function testProcessErrorOutputContainsOrNotInconsistentPrefixUsage(): void {
    $this->processRun('sh', ['-c', 'echo "Test Error" 1>&2']);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('All strings must have valid prefixes in mixed mode');

    $this->assertProcessErrorOutputContainsOrNot([
      '! Nonexistent',
      'Test Error',
    ]);
  }

  public function testProcessOutputContainsOrNotInconsistentPrefixUsage(): void {
    $this->processRun('echo', ['Test Output']);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('All strings must have valid prefixes in mixed mode');

    $this->assertProcessOutputContainsOrNot([
      '* Test',
      'Output',
    ]);
  }

  public function testProcessOutputContainsOrNotExactMatch(): void {
    $this->processRun('echo', ['Hello World']);

    $this->assertProcessSuccessful();

    $this->assertProcessOutputContainsOrNot([
      '+ Hello World',
    ]);

    $this->assertProcessOutputContainsOrNot([
      '- Not exact match',
    ]);
  }

  public function testProcessErrorOutputContainsOrNotExactMatch(): void {
    $this->processRun('sh', ['-c', 'echo "Error Message" 1>&2']);

    $this->assertProcessSuccessful();

    $this->assertProcessErrorOutputContainsOrNot([
      '+ Error Message',
    ]);

    $this->assertProcessErrorOutputContainsOrNot([
      '- Not this error',
    ]);
  }

  public function testProcessAnyOutputContainsOrNotExactMatch(): void {
    $this->processRun('sh', ['-c', 'echo "Standard"; echo "Error" 1>&2']);

    $this->assertProcessSuccessful();

    $this->assertProcessAnyOutputContainsOrNot([
      '+ Standard' . "\n" . 'Error',
    ]);

    $this->assertProcessAnyOutputContainsOrNot([
      '- Not the output',
    ]);
  }

  public function testProcessOutputContainsOrNotExactMatchMultiline(): void {
    $this->processRun('sh', ['-c', 'echo "Line 1"; echo "Line 2"; echo "Line 3"']);

    $this->assertProcessSuccessful();

    $this->assertProcessOutputContainsOrNot([
      '+ Line 1' . "\n" . 'Line 2' . "\n" . 'Line 3',
    ]);

    $this->assertProcessOutputContainsOrNot([
      '* Line 2',
    ]);

    // Exact match of a single line fails when the output has multiple lines.
    $this->assertProcessOutputContainsOrNot([
      '- Line 1',
    ]);
  }

  public function testProcessFailed(): void {
    $command = 'nonexistent-command';

    $this->processRun($command);

    $this->assertProcessFailed();
  }

  public function testProcessRunWithInvalidCommand(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid command: invalid$command. Only alphanumeric characters, dots, dashes, underscores and slashes are allowed.');

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

  #[DataProvider('dataProviderProcessAssertionsWhenNotInitialized')]
  public function testProcessAssertionsWhenNotInitialized(string $method, array $arguments): void {
    $this->process = NULL;

    $this->expectException(ExpectationFailedException::class);
    $this->expectExceptionMessage('Process is not initialized.');

    $callable = [$this, $method];
    if (!is_callable($callable)) {
      throw new \RuntimeException(sprintf('Assertion method %s does not exist.', $method));
    }

    $callable(...$arguments);
  }

  public static function dataProviderProcessAssertionsWhenNotInitialized(): \Iterator {
    yield 'successful' => ['assertProcessSuccessful', []];
    yield 'failed' => ['assertProcessFailed', []];
    yield 'output_contains' => ['assertProcessOutputContains', ['test']];
    yield 'output_not_contains' => ['assertProcessOutputNotContains', ['test']];
    yield 'error_output_contains' => ['assertProcessErrorOutputContains', ['test']];
    yield 'error_output_not_contains' => ['assertProcessErrorOutputNotContains', ['test']];
    yield 'output_contains_or_not' => ['assertProcessOutputContainsOrNot', ['test']];
    yield 'error_output_contains_or_not' => ['assertProcessErrorOutputContainsOrNot', ['test']];
    yield 'any_output_contains' => ['assertProcessAnyOutputContains', ['test']];
    yield 'any_output_not_contains' => ['assertProcessAnyOutputNotContains', ['test']];
    yield 'any_output_contains_or_not' => ['assertProcessAnyOutputContainsOrNot', ['test']];
  }

  /**
   * Tests streaming output callback prefixing and line-ending handling.
   *
   * The expected output order can differ from the script execution order:
   * piped stdout is block-buffered while stderr is typically unbuffered.
   * This is standard Unix/Linux behavior that affects how Symfony Process
   * receives the output through pipes, not a bug in the streaming callback.
   */
  public function testProcessStreamingOutput(): void {
    if (!self::$fixtures) {
      throw new \RuntimeException('Fixtures directory is not set.');
    }

    $command = self::$fixtures . '/shell-command-failing.sh';

    $this->processStreamingOutput = TRUE;

    $captured_output = '';
    $capture_callback = $this->makeStreamingCaptureCallback($captured_output);

    // Built directly rather than through processRun() so the capture callback
    // can be passed to run().
    $this->process = new Process(
      [$command],
      $this->processCwd,
      [],
      NULL,
      60
    );

    $this->process->setIdleTimeout(30);
    $this->process->run($capture_callback);

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

    $expected_lines_array = explode(PHP_EOL, $expected_lines);
    foreach ($expected_lines_array as $expected_line) {
      $this->assertStringContainsString($expected_line, $captured_output, sprintf("Missing expected line: '%s'", $expected_line));
    }
  }

  public function testProcessFormatOutput(): void {
    $this->processRun('echo', ['Standard output text']);

    $formatted_output = self::callProtectedMethod($this, 'processFormatOutput');
    $this->assertIsString($formatted_output);

    $this->assertStringContainsString('EXIT CODE: 0', $formatted_output);
    $this->assertStringContainsString(self::$processStandardOutputHeader, $formatted_output);
    $this->assertStringContainsString('Standard output text', $formatted_output);
    $this->assertStringContainsString(self::$processStandardOutputFooter, $formatted_output);
  }

  public function testProcessFormatOutputWithError(): void {
    $this->processRun('sh', ['-c', 'echo "Process stdout message"; echo "Process stderr message" >&2; exit 1']);

    $formatted_output = self::callProtectedMethod($this, 'processFormatOutput');
    $this->assertIsString($formatted_output);

    $this->assertStringContainsString('EXIT CODE: 1', $formatted_output);
    $this->assertStringContainsString(self::$processStandardOutputHeader, $formatted_output);
    $this->assertStringContainsString('Process stdout message', $formatted_output);
    $this->assertStringContainsString(self::$processStandardOutputFooter, $formatted_output);
    $this->assertStringContainsString(self::$processErrorOutputHeader, $formatted_output);
    $this->assertStringContainsString('Process stderr message', $formatted_output);
    $this->assertStringContainsString(self::$processErrorOutputFooter, $formatted_output);
  }

  public function testProcessFormatOutputWithEmptyOutput(): void {
    $this->processRun('true');

    $formatted_output = self::callProtectedMethod($this, 'processFormatOutput');
    $this->assertIsString($formatted_output);

    $this->assertStringContainsString('EXIT CODE: 0', $formatted_output);
    $this->assertStringNotContainsString(self::$processStandardOutputHeader, $formatted_output);
    $this->assertStringNotContainsString(self::$processErrorOutputHeader, $formatted_output);
  }

  public function testProcessGet(): void {
    $this->processRun('echo', ['test']);

    $process = $this->processGet();

    $this->assertInstanceOf(Process::class, $process);
    $this->assertTrue($process->isSuccessful());
  }

  public function testProcessGetWhenNotInitialized(): void {
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Process is not initialized.');

    $this->processGet();
  }

  public function testProcessTearDown(): void {
    $this->processRun('sleep', ['0.1']);

    $this->assertInstanceOf(Process::class, $this->process);

    $this->processTearDown();

    $this->assertInstanceOf(Process::class, $this->process);
    $this->assertFalse($this->process->isRunning());
  }

  public function testProcessTearDownWhenNotInitialized(): void {
    $this->assertNull($this->process);

    // The processTearDown() call must not throw.
    $this->processTearDown();

    $this->assertNull($this->process);
  }

  public function testProcessRunWithCustomCwd(): void {
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

    $formatted_output = self::callProtectedMethod($this, 'processFormatOutput');
    $this->assertIsString($formatted_output);

    $this->assertStringContainsString('Process is not initialized.', $formatted_output);
  }

  public function testProcessStreamingCallbackWithEmptyBuffer(): void {
    // The callback captures output instead of writing to STDOUT.
    $captured_output = '';
    $test_callback = $this->makeStreamingCaptureCallback($captured_output);

    $test_callback(Process::OUT, '');
    $test_callback(Process::ERR, '');

    $this->assertSame('', $captured_output);
  }

  public function testProcessStreamingCallbackWithDifferentLineEndings(): void {
    // The callback captures output instead of writing to STDOUT.
    $captured_output = '';
    $test_callback = $this->makeStreamingCaptureCallback($captured_output);

    $test_inputs = [
      "line1\nline2\n",
      "line1\r\nline2\r\n",
      "line1\rline2\r",
      'single line',
      '',
    ];

    foreach ($test_inputs as $input) {
      $test_callback(Process::OUT, $input);
      $test_callback(Process::ERR, $input);
    }

    $this->assertStringContainsString(self::$processStreamingStandardOutputChars, $captured_output);
    $this->assertStringContainsString(self::$processStreamingErrorOutputChars, $captured_output);
  }

  public function testProcessStreamingCallbackWithErrorOutput(): void {
    // The callback captures output instead of writing to STDOUT.
    $captured_output = '';
    $test_callback = $this->makeStreamingCaptureCallback($captured_output);

    $test_callback(Process::ERR, "error message\n");
    $test_callback(Process::OUT, "standard message\n");

    $this->assertStringContainsString(self::$processStreamingErrorOutputChars . 'error message', $captured_output);
    $this->assertStringContainsString(self::$processStreamingStandardOutputChars . 'standard message', $captured_output);
  }

  public function testProcessRunStopsExistingProcess(): void {
    $this->processRun('echo', ['first process']);
    $first_process = $this->process;

    $this->processRun('echo', ['second process']);
    $second_process = $this->process;

    $this->assertNotSame($first_process, $second_process);
    $this->assertProcessOutputContains('second process');
    $this->assertProcessOutputNotContains('first process');
  }

  public function testProcessRunWithNullInputs(): void {
    // An empty inputs array is converted to NULL.
    $this->processRun('echo', ['test'], []);

    $this->assertProcessSuccessful();
    $this->assertProcessOutputContains('test');
  }

  public function testProcessRunWithNonEmptyInputs(): void {
    if (DIRECTORY_SEPARATOR === '\\') {
      $this->markTestSkipped('Requires POSIX utilities.');
    }

    // Non-empty inputs trigger the implode path.
    $this->processRun('cat', [], ['line1', 'line2', 'line3']);

    $this->assertProcessSuccessful();
    $this->assertProcessOutputContains('line1');
    $this->assertProcessOutputContains('line2');
    $this->assertProcessOutputContains('line3');
  }

  #[DataProvider('dataProviderProcessRunWithCommandString')]
  public function testProcessRunWithCommandString(string $command_string, array $additional_args, array $expected_output): void {
    if (DIRECTORY_SEPARATOR === '\\' && str_starts_with($command_string, 'printf')) {
      $this->markTestSkipped('Requires POSIX utilities.');
    }

    $this->processRun($command_string, $additional_args);

    $this->assertProcessSuccessful();
    foreach ($expected_output as $expected) {
      $this->assertProcessOutputContains($expected);
    }
  }

  public static function dataProviderProcessRunWithCommandString(): \Iterator {
    yield 'simple_command_string' => [
      'echo hello world',
      [],
      ['hello world'],
    ];
    yield 'command_with_flags' => [
      'printf %s test',
      [],
      ['test'],
    ];
    yield 'command_with_quoted_arguments' => [
      'echo "hello world" test',
      [],
      ['hello world', 'test'],
    ];
    yield 'command_with_additional_args' => [
      'echo hello',
      ['world', 'again'],
      ['world', 'again', 'hello'],
    ];
    yield 'mixed_quotes' => [
      'echo "double quote" \'single quote\'',
      [],
      ['double quote', 'single quote'],
    ];
    yield 'escaped_characters' => [
      'echo hello\\ world',
      [],
      ['hello world'],
    ];
    yield 'complex_git_like_command' => [
      'echo --message="Initial commit"',
      ['--author=John'],
      ['--author=John', '--message=Initial commit'],
    ];
    yield 'array_arguments_form' => [
      'echo',
      ['hello', 'world'],
      ['hello world'],
    ];
    yield 'multiword_command_string' => [
      'echo test1 test2',
      [],
      ['test1 test2'],
    ];
    yield 'special_characters' => [
      'echo "Hello! @#$%^&*()"',
      [],
      ['Hello! @#$%^&*()'],
    ];
    yield 'file_flags' => [
      'echo -e "line1\\nline2"',
      [],
      ['line1'],
    ];
    yield 'parsed_then_explicit_arguments' => [
      'echo parsed1 parsed2',
      ['explicit1', 'explicit2'],
      ['parsed1 parsed2 explicit1 explicit2'],
    ];
    yield 'flag_argument_order' => [
      'echo --parsed-flag',
      ['--explicit-flag'],
      ['--parsed-flag --explicit-flag'],
    ];
    yield 'explicit_arguments_appended' => [
      'echo default-arg1 default-arg2',
      ['override-arg1', 'override-arg2'],
      ['default-arg1 default-arg2 override-arg1 override-arg2'],
    ];
    yield 'end_of_options_with_explicit_args' => [
      'echo command -- after-marker',
      ['explicit'],
      ['command explicit -- after-marker'],
    ];
  }

  public function testProcessRunWithInvalidCommandString(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid command: invalid$command. Only alphanumeric characters, dots, dashes, underscores and slashes are allowed.');

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

  public function testProcessRunWithIdleTimeout(): void {
    $this->processRun('echo', ['test'], [], [], 10, 5);

    $this->assertProcessSuccessful();
    $this->assertProcessOutputContains('test');
  }

  public function testProcessStaticPropertyDefaults(): void {
    $this->assertSame('>> ', self::$processStreamingStandardOutputChars);
    $this->assertSame('XX ', self::$processStreamingErrorOutputChars);
    $this->assertStringContainsString('STANDARD OUTPUT', self::$processStandardOutputHeader);
    $this->assertStringContainsString('STANDARD OUTPUT', self::$processStandardOutputFooter);
    $this->assertStringContainsString('ERROR OUTPUT', self::$processErrorOutputHeader);
    $this->assertStringContainsString('ERROR OUTPUT', self::$processErrorOutputFooter);
  }

  #[DataProvider('dataProviderProcessParseCommand')]
  public function testProcessParseCommand(string $command, array $expected, ?string $exception_message = NULL): void {
    if ($exception_message !== NULL) {
      $this->expectException(\InvalidArgumentException::class);
      $this->expectExceptionMessage($exception_message);
    }

    $result = self::callProtectedMethod($this, 'processParseCommand', [$command]);

    if ($exception_message === NULL) {
      $this->assertSame($expected, $result);
    }
  }

  public static function dataProviderProcessParseCommand(): \Iterator {
    // Basic cases.
    yield 'simple_command' => [
      'echo',
      ['echo'],
    ];
    yield 'command_with_single_argument' => [
      'echo hello',
      ['echo', 'hello'],
    ];
    yield 'command_with_multiple_arguments' => [
      'echo hello world',
      ['echo', 'hello', 'world'],
    ];
    yield 'command_with_flags' => [
      'ls -la',
      ['ls', '-la'],
    ];
    yield 'command_with_multiple_flags' => [
      'ls -l -a -h',
      ['ls', '-l', '-a', '-h'],
    ];
    yield 'command_with_flag_and_value' => [
      'git commit -m message',
      ['git', 'commit', '-m', 'message'],
    ];
    // Quoted arguments.
    yield 'double_quoted_argument' => [
      'echo "hello world"',
      ['echo', 'hello world'],
    ];
    yield 'single_quoted_argument' => [
      "echo 'hello world'",
      ['echo', 'hello world'],
    ];
    yield 'multiple_quoted_arguments' => [
      'echo "hello world" "goodbye earth"',
      ['echo', 'hello world', 'goodbye earth'],
    ];
    yield 'mixed_quoted_arguments' => [
      'echo "hello world" \'goodbye earth\'',
      ['echo', 'hello world', 'goodbye earth'],
    ];
    yield 'quoted_argument_with_spaces' => [
      'echo "  hello   world  "',
      ['echo', '  hello   world  '],
    ];
    yield 'empty_quoted_argument' => [
      'echo ""',
      ['echo', ''],
    ];
    yield 'empty_single_quoted_argument' => [
      "echo ''",
      ['echo', ''],
    ];
    // Escaped characters.
    yield 'escaped_double_quote_in_double_quotes' => [
      'echo "She said \"Hello\""',
      ['echo', 'She said "Hello"'],
    ];
    yield 'escaped_single_quote_in_single_quotes' => [
      "echo 'It\\'s working'",
      ['echo', "It's working"],
    ];
    yield 'escaped_backslash' => [
      'echo "Path\\\\to\\\\file"',
      ['echo', 'Path\\to\\file'],
    ];
    yield 'escaped_space' => [
      'echo hello\\ world',
      ['echo', 'hello world'],
    ];
    yield 'escaped_characters_outside_quotes' => [
      'echo test\\nvalue',
      ['echo', 'testnvalue'],
    ];
    // Complex cases.
    yield 'complex_command_with_mixed_quoting' => [
      'git commit -m "Initial commit" --author="John Doe <john@example.com>"',
      ['git', 'commit', '-m', 'Initial commit', '--author=John Doe <john@example.com>'],
    ];
    yield 'command_with_equals_in_quotes' => [
      'env VAR="value=with=equals" command',
      ['env', 'VAR=value=with=equals', 'command'],
    ];
    yield 'command_with_special_characters' => [
      'echo "Hello! @#$%^&*()_+-={}[]|\\\\:;\"\'<>?,./"',
      ['echo', 'Hello! @#$%^&*()_+-={}[]|\\:;"\'<>?,./'],
    ];
    // Whitespace handling.
    yield 'command_with_leading_spaces' => [
      '   echo hello',
      ['echo', 'hello'],
    ];
    yield 'command_with_trailing_spaces' => [
      'echo hello   ',
      ['echo', 'hello'],
    ];
    yield 'command_with_multiple_spaces_between_args' => [
      'echo    hello     world',
      ['echo', 'hello', 'world'],
    ];
    yield 'command_with_tabs' => [
      "echo\thello\tworld",
      ['echo', 'hello', 'world'],
    ];
    // Edge cases with quotes.
    yield 'nested_quotes_different_types' => [
      'echo "He said \'Hello\'"',
      ['echo', "He said 'Hello'"],
    ];
    yield 'nested_quotes_same_type_escaped' => [
      'echo "He said \"Hello\" loudly"',
      ['echo', 'He said "Hello" loudly'],
    ];
    yield 'quotes_in_argument_preserved' => [
      'echo hello"world"test',
      ['echo', 'helloworldtest'],
    ];
    yield 'quote_at_beginning_of_argument' => [
      'echo "hello"world',
      ['echo', 'helloworld'],
    ];
    // Arguments with special command characters.
    yield 'argument_with_pipe_in_quotes' => [
      'echo "command | grep something"',
      ['echo', 'command | grep something'],
    ];
    yield 'argument_with_redirect_in_quotes' => [
      'echo "output > file.txt"',
      ['echo', 'output > file.txt'],
    ];
    yield 'argument_with_semicolon_in_quotes' => [
      'echo "cmd1; cmd2"',
      ['echo', 'cmd1; cmd2'],
    ];
    // Number and boolean-like arguments.
    yield 'numeric_arguments' => [
      'test 123 456.789',
      ['test', '123', '456.789'],
    ];
    yield 'boolean_like_arguments' => [
      'test true false',
      ['test', 'true', 'false'],
    ];
    // File paths.
    yield 'relative_path' => [
      'cat ./file.txt',
      ['cat', './file.txt'],
    ];
    yield 'absolute_path' => [
      'cat /usr/local/bin/file',
      ['cat', '/usr/local/bin/file'],
    ];
    yield 'path_with_spaces_quoted' => [
      'cat "/path/with spaces/file.txt"',
      ['cat', '/path/with spaces/file.txt'],
    ];
    // End-of-options marker (--) cases.
    yield 'end_of_options_basic' => [
      'run -- -abc',
      ['run', '--', '-abc'],
    ];
    yield 'end_of_options_mixed_options_and_arguments' => [
      'start -v -- --dry-run -f config.yml',
      ['start', '-v', '--', '--dry-run', '-f', 'config.yml'],
    ];
    yield 'end_of_options_no_options_present' => [
      'do -- foo bar',
      ['do', '--', 'foo', 'bar'],
    ];
    yield 'end_of_options_quoted_delimiter_should_not_split' => [
      'exec "-- --not-an-option"',
      ['exec', '-- --not-an-option'],
    ];
    yield 'end_of_options_with_multiple_dashes_after' => [
      'deploy -f -- --force --verbose --dry-run',
      ['deploy', '-f', '--', '--force', '--verbose', '--dry-run'],
    ];
    yield 'end_of_options_with_flags_before_and_after' => [
      'build --clean -- --no-cache --parallel',
      ['build', '--clean', '--', '--no-cache', '--parallel'],
    ];
    yield 'end_of_options_at_end_of_command' => [
      'command arg1 arg2 --',
      ['command', 'arg1', 'arg2', '--'],
    ];
    yield 'end_of_options_only_double_dash' => [
      'command --',
      ['command', '--'],
    ];
    yield 'end_of_options_with_quoted_args_after' => [
      'test -- "quoted arg" \'single quoted\'',
      ['test', '--', 'quoted arg', 'single quoted'],
    ];
    yield 'end_of_options_with_special_characters_after' => [
      'cmd -- --option=value -x "complex arg with spaces"',
      ['cmd', '--', '--option=value', '-x', 'complex arg with spaces'],
    ];
    yield 'end_of_options_multiple_markers_only_first_counts' => [
      'test -- first -- second',
      ['test', '--', 'first', '--', 'second'],
    ];
    yield 'end_of_options_with_escaping_before_marker' => [
      'echo hello\\ world --',
      ['echo', 'hello world', '--'],
    ];
    yield 'end_of_options_with_complex_args_after' => [
      'deploy -f -- --env=prod --config="/path/with spaces/config.yml" -x',
      ['deploy', '-f', '--', '--env=prod', '--config=/path/with spaces/config.yml', '-x'],
    ];
    yield 'end_of_options_command_subcommand_pattern' => [
      'cli subcommand -- arguments --option',
      ['cli', 'subcommand', '--', 'arguments', '--option'],
    ];
    yield 'end_of_options_ahoy_like_pattern' => [
      'tool subtool -- target --flag --setting=value',
      ['tool', 'subtool', '--', 'target', '--flag', '--setting=value'],
    ];
    // Error cases.
    yield 'empty_command' => [
      '',
      [],
      'Command cannot be empty.',
    ];
    yield 'whitespace_only_command' => [
      '   ',
      [],
      'Command cannot be empty.',
    ];
    yield 'unclosed_double_quote' => [
      'echo "hello world',
      [],
      'Unclosed quote in command string.',
    ];
    yield 'unclosed_single_quote' => [
      "echo 'hello world",
      [],
      'Unclosed quote in command string.',
    ];
    yield 'unclosed_quote_with_escape' => [
      'echo "hello world\\',
      [],
      'Unclosed quote in command string.',
    ];
    yield 'trailing_escape_character' => [
      'echo hello\\',
      [],
      'Trailing escape character in command string.',
    ];
  }

  public function testProcessStreamingCallbackReturnsCallable(): void {
    $callback = self::callProtectedMethod($this, 'processStreamingOutputCallback');

    $this->assertIsCallable($callback);

    // Empty strings avoid visible output.
    $callback(Process::OUT, '');
    $callback(Process::ERR, '');
  }

  #[DataProvider('dataProviderProcessStreamingOutputCallbackDimming')]
  public function testProcessStreamingOutputCallbackDimming(bool $should_dim, string $type, string $expected): void {
    $original = self::$processStreamingOutputShouldDim;
    self::$processStreamingOutputShouldDim = $should_dim;

    $callback = $this->processStreamingOutputCallback();

    try {
      $output = $this->captureStdout(static function () use ($callback, $type): void {
        $callback($type, "line one\n");
      });
    }
    finally {
      self::$processStreamingOutputShouldDim = $original;
    }

    $this->assertSame($expected, $output);
  }

  public static function dataProviderProcessStreamingOutputCallbackDimming(): \Iterator {
    yield 'stdout_dimmed' => [
      TRUE,
      Process::OUT,
      "\n\033[2m>> line one\033[22m\n\033[2m\033[22m",
    ];
    yield 'stdout_plain' => [
      FALSE,
      Process::OUT,
      "\n>> line one\n",
    ];
    yield 'stderr_dimmed' => [
      TRUE,
      Process::ERR,
      "\033[2mXX line one\033[22m\n\033[2m\033[22m",
    ];
    yield 'stderr_plain' => [
      FALSE,
      Process::ERR,
      "XX line one\n",
    ];
  }

  public function testProcessRunWithZeroArguments(): void {
    $this->processRun('echo', []);

    $this->assertProcessSuccessful();
  }

  public function testProcessRunWithEmptyStringArgument(): void {
    $this->processRun('echo', ['']);

    $this->assertProcessSuccessful();
  }

  public function testProcessRunWithMixedScalarArguments(): void {
    $this->processRun('echo', ['string', 123, TRUE, 45.67]);

    $this->assertProcessSuccessful();
  }

  public function testProcessRunWithMixedScalarEnvironmentVariables(): void {
    if (DIRECTORY_SEPARATOR === '\\') {
      $this->markTestSkipped('Requires POSIX utilities.');
    }

    $this->processRun('printenv', [], [], [
      'TEST_STRING' => 'value',
      'TEST_INT' => 123,
      'TEST_BOOL' => TRUE,
      'TEST_FLOAT' => 45.67,
    ]);

    $this->assertProcessSuccessful();
  }

  public function testProcessRunWithEnvironmentVariableUnsetting(): void {
    if (DIRECTORY_SEPARATOR === '\\') {
      $this->markTestSkipped('Requires POSIX utilities.');
    }

    putenv('TEST_UNSET_VAR=initial_value');

    try {
      $this->assertNotFalse(getenv('TEST_UNSET_VAR'));

      // A FALSE value unsets the variable.
      $this->processRun('printenv', [], [], [
        'TEST_UNSET_VAR' => FALSE,
        'TEST_KEEP_VAR' => 'keep_this',
      ]);

      $this->assertProcessSuccessful();

      $this->assertProcessOutputNotContains('TEST_UNSET_VAR=initial_value');

      $this->assertProcessOutputContains('TEST_KEEP_VAR=keep_this');
    }
    finally {
      putenv('TEST_UNSET_VAR');
    }
  }

  public function testProcessFormatOutputExitCodeDisplay(): void {
    if (DIRECTORY_SEPARATOR === '\\') {
      $this->markTestSkipped('Requires POSIX utilities.');
    }

    $this->processRun('sh', ['-c', 'exit 42']);

    $formatted_output = self::callProtectedMethod($this, 'processFormatOutput');

    $this->assertIsString($formatted_output);
    $this->assertStringContainsString('EXIT CODE: 42', $formatted_output);
  }

  public function testAssertProcessSuccessfulWithFailedProcess(): void {
    $this->processRun('sh', ['-c', 'exit 1']);

    $this->expectException(AssertionFailedError::class);
    $this->expectExceptionMessage('PROCESS FAILED');

    $this->assertProcessSuccessful();
  }

  public function testAssertProcessSuccessfulWithFailedProcessAndMessage(): void {
    $this->processRun('sh', ['-c', 'exit 1']);

    $this->expectException(AssertionFailedError::class);
    $this->expectExceptionMessage('PROCESS FAILED');
    $this->expectExceptionMessage('Message: Custom failure message');

    $this->assertProcessSuccessful('Custom failure message');
  }

  public function testAssertProcessFailedWithSuccessfulProcess(): void {
    $this->processRun('echo', ['success']);

    $this->expectException(AssertionFailedError::class);
    $this->expectExceptionMessage('PROCESS SUCCEEDED but failure was expected');

    $this->assertProcessFailed();
  }

  public function testAssertProcessFailedWithSuccessfulProcessAndMessage(): void {
    $this->processRun('echo', ['success']);

    $this->expectException(AssertionFailedError::class);
    $this->expectExceptionMessage('PROCESS SUCCEEDED but failure was expected');
    $this->expectExceptionMessage('Message: Expected process to fail');

    $this->assertProcessFailed('Expected process to fail');
  }

  public function testProcessStreamingOutputWithDimmingEnabled(): void {
    $this->processStreamingOutput = TRUE;
    self::$processStreamingOutputShouldDim = TRUE;

    try {
      $temp_file = tempnam(sys_get_temp_dir(), 'phpunit_stdout_test');

      $test_script = sprintf('
<?php
require_once "%s/vendor/autoload.php";
use AlexSkrypnyk\PhpunitHelpers\Tests\Fixtures\StreamCaptureFilter;
use AlexSkrypnyk\PhpunitHelpers\Traits\ProcessTrait;

class TestClass {
  use ProcessTrait;
  
  public function enableStreamingWithDimming() {
    $this->processStreamingOutput = true;
    static::$processStreamingOutputShouldDim = true;
  }
}

$test = new TestClass();
$test->enableStreamingWithDimming();
$test->processRun("echo", ["test output"]);
', getcwd());

      file_put_contents($temp_file . '.php', $test_script);
      $output = shell_exec('php ' . escapeshellarg($temp_file . '.php'));
      unlink($temp_file . '.php');
      unlink($temp_file);

      $this->assertNotNull($output);
      $output = (string) $output;
      $this->assertStringContainsString("\033[2m", $output, 'Should contain ANSI dim start code');
      $this->assertStringContainsString("\033[22m", $output, 'Should contain ANSI dim end code');
      $this->assertStringContainsString('test output', $output, 'Should contain actual text');
    }
    finally {
      $this->processStreamingOutput = FALSE;
    }
  }

  public function testProcessStreamingOutputWithDimmingDisabled(): void {
    $this->processStreamingOutput = TRUE;
    self::$processStreamingOutputShouldDim = FALSE;

    try {
      $temp_file = tempnam(sys_get_temp_dir(), 'phpunit_stdout_test');

      $test_script = sprintf('
<?php
require_once "%s/vendor/autoload.php";
use AlexSkrypnyk\PhpunitHelpers\Tests\Fixtures\StreamCaptureFilter;
use AlexSkrypnyk\PhpunitHelpers\Traits\ProcessTrait;

class TestClass {
  use ProcessTrait;
  
  public function enableStreamingWithoutDimming() {
    $this->processStreamingOutput = true;
    static::$processStreamingOutputShouldDim = false;
  }
}

$test = new TestClass();
$test->enableStreamingWithoutDimming();
$test->processRun("echo", ["test output"]);
', getcwd());

      file_put_contents($temp_file . '.php', $test_script);
      $output = shell_exec('php ' . escapeshellarg($temp_file . '.php'));
      unlink($temp_file . '.php');
      unlink($temp_file);

      $this->assertNotNull($output);
      $output = (string) $output;
      $this->assertStringNotContainsString("\033[2m", $output, 'Should not contain ANSI dim start code');
      $this->assertStringNotContainsString("\033[22m", $output, 'Should not contain ANSI dim end code');
      $this->assertStringContainsString('test output', $output, 'Should contain actual text');
    }
    finally {
      $this->processStreamingOutput = FALSE;
      self::$processStreamingOutputShouldDim = TRUE;
    }
  }

  public function testAssertProcessOutputContainsWithCustomMessage(): void {
    $this->processRun('echo', ['test output']);

    $custom_message = 'This is a custom failure message';

    $this->expectException(ExpectationFailedException::class);
    $this->expectExceptionMessage($custom_message);

    $this->assertProcessOutputContains('nonexistent', $custom_message);
  }

  #[DataProvider('dataProviderProcessColorDim')]
  public function testProcessColorDim(string $text, string $eol, string $expected): void {
    $this->assertSame($expected, self::processColorDim($text, $eol));
  }

  public static function dataProviderProcessColorDim(): \Iterator {
    yield 'empty' => [
      '',
      "\n",
      "\033[2m\033[22m",
    ];
    yield 'single_line' => [
      'test',
      "\n",
      "\033[2mtest\033[22m",
    ];
    yield 'multiple_lines' => [
      "one\ntwo",
      "\n",
      "\033[2mone\033[22m\n\033[2mtwo\033[22m",
    ];
    yield 'custom_eol' => [
      "one\r\ntwo",
      "\r\n",
      "\033[2mone\033[22m\r\n\033[2mtwo\033[22m",
    ];
    yield 'empty_eol_falls_back_to_newline' => [
      "one\ntwo",
      '',
      "\033[2mone\033[22m\n\033[2mtwo\033[22m",
    ];
  }

  /**
   * Runs a callback and returns what it wrote to STDOUT.
   *
   * @param callable $callback
   *   The callback to run.
   *
   * @return string
   *   Everything the callback wrote to STDOUT.
   */
  protected function captureStdout(callable $callback): string {
    $name = 'phpunit_helpers_stream_capture';

    if (!in_array($name, stream_get_filters(), TRUE)) {
      stream_filter_register($name, StreamCaptureFilter::class);
    }

    StreamCaptureFilter::$captured = '';

    $filter = stream_filter_append(STDOUT, $name, STREAM_FILTER_WRITE);
    if ($filter === FALSE) {
      throw new \RuntimeException('Unable to attach the capture filter to STDOUT.');
    }

    try {
      $callback();
    }
    finally {
      stream_filter_remove($filter);
    }

    return StreamCaptureFilter::$captured;
  }

  protected function makeStreamingCaptureCallback(string &$captured_output): callable {
    return function ($type, $buffer) use (&$captured_output): void {
      $prefix = $type === Process::ERR ? self::$processStreamingErrorOutputChars : self::$processStreamingStandardOutputChars;

      $parts = preg_split('/(\r\n|\n|\r)/', $buffer, -1, PREG_SPLIT_DELIM_CAPTURE);
      $count = is_array($parts) ? count($parts) : 0;

      for ($i = 0; $i < $count; $i += 2) {
        $line = $parts[$i] ?? '';
        $eol = $parts[$i + 1] ?? '';

        if ($line === '' && $eol === '') {
          continue;
        }

        $captured_output .= $prefix . $line . $eol;
      }
    };
  }

}
