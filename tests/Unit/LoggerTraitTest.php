<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Tests\Unit;

use AlexSkrypnyk\PhpunitHelpers\Traits\LoggerTrait;
use AlexSkrypnyk\PhpunitHelpers\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;

#[CoversTrait(LoggerTrait::class)]
final class LoggerTraitTest extends UnitTestCase {

  use LoggerTrait;

  /**
   * @var resource
   */
  protected $logBuffer;

  protected function setUp(): void {
    parent::setUp();
    self::logSetVerbose(FALSE);

    $this->recreateLogBuffer();

    $reflection_class = new \ReflectionClass(self::class);
    $steps_property = $reflection_class->getProperty('logSteps');
    $steps_property->setValue(NULL, []);

    $stack_property = $reflection_class->getProperty('logStepStack');
    $stack_property->setValue(NULL, []);
  }

  protected function tearDown(): void {
    if (is_resource($this->logBuffer)) {
      fclose($this->logBuffer);
    }

    self::logSetOutputStream(NULL);

    parent::tearDown();
  }

  public function testVerboseMode(): void {
    self::logSetVerbose(TRUE);
    self::log('Verbose message');
    self::logSection('Verbose Section', 'Verbose content');

    $output = $this->getCapturedOutput();
    $this->assertStringContainsString('Verbose message', $output);
    $this->assertStringContainsString('Verbose Section', $output);
    $this->assertStringContainsString('Verbose content', $output);

    $this->recreateLogBuffer();

    self::logSetVerbose(FALSE);
    self::log('Silent message');
    self::logSection('Silent Section', 'Silent content');

    $silent_output = $this->getCapturedOutput();
    $this->assertSame('', $silent_output);
  }

  public function testLogSilentMode(): void {
    self::logSetVerbose(FALSE);

    self::log('Test message');

    $output = $this->getCapturedOutput();
    $this->assertSame('', $output);
  }

  public function testLogVerboseMode(): void {
    self::logSetVerbose(TRUE);

    self::log('Test message');

    $output = $this->getCapturedOutput();
    $this->assertSame("\nTest message\n", $output);
  }

  public function testLogSection(): void {
    self::logSetVerbose(TRUE);

    self::logSection('TEST TITLE');

    self::logSection('TEST TITLE', 'Test message content');

    // Double border.
    self::logSection('TEST TITLE', 'Test message', TRUE);

    // Custom width.
    self::logSection('TEST TITLE', NULL, FALSE, 80);

    $output = $this->getCapturedOutput();
    $this->assertStringContainsString('TEST TITLE', $output);
    $this->assertStringContainsString('Test message content', $output);
    $this->assertStringContainsString('Test message', $output);
    // Single border.
    $this->assertStringContainsString('---', $output);
    // Double border.
    $this->assertStringContainsString('===', $output);
  }

  #[DataProvider('dataProviderLogSectionWithInvalidMinWidth')]
  public function testLogSectionWithInvalidMinWidth(int $min_width): void {
    self::logSetVerbose(TRUE);

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Minimum width must be a positive integer.');

    self::logSection('TEST TITLE', NULL, FALSE, $min_width);
  }

  /**
   * @return \Iterator<string, array{int}>
   */
  public static function dataProviderLogSectionWithInvalidMinWidth(): \Iterator {
    yield 'zero' => [0];
    yield 'negative' => [-10];
  }

  #[DoesNotPerformAssertions]
  public function testLogFileWithExistingFile(): void {
    self::logSetVerbose(TRUE);

    $temp_file = tempnam(sys_get_temp_dir(), 'logger_test');
    file_put_contents($temp_file, 'Test file content');

    self::logFile($temp_file);
    self::logFile($temp_file, 'Custom message');

    unlink($temp_file);
  }

  public function testLogFileWithUnreadableFile(): void {
    self::logSetVerbose(TRUE);

    $temp_file = tempnam(sys_get_temp_dir(), 'logger_test');
    file_put_contents($temp_file, 'Test content');

    chmod($temp_file, 0000);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Failed to read file ' . $temp_file . '.');

    try {
      self::logFile($temp_file);
    }
    finally {
      chmod($temp_file, 0644);
      unlink($temp_file);
    }
  }

  public function testLogFileWithNonExistentFile(): void {
    self::logSetVerbose(TRUE);

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('File /non/existent/file does not exist.');

    self::logFile('/non/existent/file');
  }

  #[DoesNotPerformAssertions]
  public function testLogFileSilentMode(): void {
    self::logSetVerbose(FALSE);

    $temp_file = tempnam(sys_get_temp_dir(), 'logger_test');
    file_put_contents($temp_file, 'Test content');

    self::logFile($temp_file);

    unlink($temp_file);
  }

  #[DoesNotPerformAssertions]
  public function testLogSectionSilentMode(): void {
    self::logSetVerbose(FALSE);

    self::logSection('TEST TITLE', 'Test message');
  }

  #[DoesNotPerformAssertions]
  public function testSilentModeForAllMethods(): void {
    self::logSetVerbose(FALSE);

    self::log('Test message');
    self::logSection('TEST TITLE', 'Test message');

    $temp_file = tempnam(sys_get_temp_dir(), 'logger_test');
    file_put_contents($temp_file, 'Test content');
    self::logFile($temp_file);
    unlink($temp_file);
  }

  #[DoesNotPerformAssertions]
  public function testVerboseModePersistence(): void {
    self::logSetVerbose(TRUE);

    self::log('Message 1');

    self::logSetVerbose(FALSE);

    self::log('Message 2');
  }

  public function testLogStepStartVerboseMode(): void {
    self::logSetVerbose(TRUE);

    self::logStepStart();

    $output = $this->getCapturedOutput();
    $this->assertStringContainsString('STEP START | testLogStepStartVerboseMode', $output);
    $this->assertStringContainsString('---', $output);
  }

  public function testLogStepStartWithCustomPrefix(): void {
    self::logSetVerbose(TRUE);

    $original_prefix = self::$logStepMethodPrefix;
    self::$logStepMethodPrefix = 'process';

    try {
      $this->processTest();

      $output = $this->getCapturedOutput();
      $this->assertStringContainsString('PROCESS START | processTest', $output);
    }
    finally {
      self::$logStepMethodPrefix = $original_prefix;
    }
  }

  #[DoesNotPerformAssertions]
  public function testLogStepStartSilentMode(): void {
    self::logSetVerbose(FALSE);

    self::logStepStart();
    self::logStepStart('Silent step start');
  }

  public function testLogStepFinishVerboseMode(): void {
    self::logSetVerbose(TRUE);

    self::logStepStart();
    self::logStepFinish('Completed the test step');

    $output = $this->getCapturedOutput();
    $this->assertStringContainsString('STEP START | testLogStepFinishVerboseMode', $output);
    $this->assertStringContainsString('STEP DONE | testLogStepFinishVerboseMode | 0s', $output);
    $this->assertStringContainsString('Completed the test step', $output);
  }

  public function testLogStepFinishWithCustomPrefix(): void {
    self::logSetVerbose(TRUE);

    $original_prefix = self::$logStepMethodPrefix;
    self::$logStepMethodPrefix = 'process';

    try {
      $this->processFinishTest();

      $output = $this->getCapturedOutput();
      $this->assertStringContainsString('PROCESS START | processFinishTest', $output);
      $this->assertStringContainsString('PROCESS DONE | processFinishTest | 0s', $output);
    }
    finally {
      self::$logStepMethodPrefix = $original_prefix;
    }
  }

  #[DoesNotPerformAssertions]
  public function testLogStepFinishSilentMode(): void {
    self::logSetVerbose(FALSE);

    self::logStepFinish();
    self::logStepFinish('Silent step finish');
  }

  #[DoesNotPerformAssertions]
  public function testLogSubstepVerboseMode(): void {
    self::logSetVerbose(TRUE);

    self::logSubstep('Processing substep 1');
    self::logSubstep('Processing substep 2');
  }

  #[DoesNotPerformAssertions]
  public function testLogSubstepSilentMode(): void {
    self::logSetVerbose(FALSE);

    self::logSubstep('Silent substep');
  }

  #[DoesNotPerformAssertions]
  public function testLogNoteVerboseMode(): void {
    self::logSetVerbose(TRUE);

    self::logNote('Important note about the process');
    self::logNote('Another note with details');
  }

  #[DoesNotPerformAssertions]
  public function testLogNoteSilentMode(): void {
    self::logSetVerbose(FALSE);

    self::logNote('Silent note');
  }

  public function testLogStepWorkflow(): void {
    self::logSetVerbose(TRUE);

    self::logStepStart('Test workflow');
    self::logSubstep('Initializing');
    self::logNote('Setting up test data');
    self::logSubstep('Processing');
    self::logNote('Performing calculations');
    self::logStepFinish('Test workflow completed');

    $output = $this->getCapturedOutput();
    $this->assertStringContainsString('STEP START | testLogStepWorkflow', $output);
    $this->assertStringContainsString('STEP DONE | testLogStepWorkflow | 0s', $output);
    $this->assertStringContainsString('Test workflow', $output);
    $this->assertStringContainsString('Test workflow completed', $output);
    $this->assertStringContainsString('  --> Initializing', $output);
    $this->assertStringContainsString('  --> Processing', $output);
    $this->assertStringContainsString('    > Setting up test data', $output);
    $this->assertStringContainsString('    > Performing calculations', $output);
  }

  public function testLogStepMethodsRespectVerboseMode(): void {
    self::logSetVerbose(FALSE);
    self::logStepStart('Silent step');
    self::logSubstep('Silent substep');
    self::logNote('Silent note');
    self::logStepFinish('Silent step end');

    $silent_output = $this->getCapturedOutput();
    $this->assertSame('', $silent_output);

    $this->recreateLogBuffer();

    // Clear steps tracked by the silent calls to avoid interference.
    $reflection_class = new \ReflectionClass(self::class);
    $steps_property = $reflection_class->getProperty('logSteps');
    $steps_property->setValue(NULL, []);

    self::logSetVerbose(TRUE);
    self::logStepStart('Verbose step');
    self::logSubstep('Verbose substep');
    self::logNote('Verbose note');
    self::logStepFinish('Verbose step end');

    $verbose_output = $this->getCapturedOutput();
    $this->assertStringContainsString('STEP START | testLogStepMethodsRespectVerboseMode', $verbose_output);
    $this->assertStringContainsString('STEP DONE | testLogStepMethodsRespectVerboseMode | 0s', $verbose_output);
    $this->assertStringContainsString('Verbose step', $verbose_output);
    $this->assertStringContainsString('Verbose step end', $verbose_output);
    $this->assertStringContainsString('  --> Verbose substep', $verbose_output);
    $this->assertStringContainsString('    > Verbose note', $verbose_output);
  }

  #[DoesNotPerformAssertions]
  public function testLogStepElapsedTime(): void {
    self::logSetVerbose(TRUE);

    self::logStepStart('Timed step');
    // Delay long enough to show measurable elapsed time.
    usleep(1500000);
    self::logStepFinish('Timed step completed');
  }

  #[DoesNotPerformAssertions]
  public function testLogStepFinishWithoutStart(): void {
    self::logSetVerbose(TRUE);

    // A finish without a start shows no elapsed time and does not throw.
    self::logStepFinish('Orphan step');
  }

  #[DoesNotPerformAssertions]
  public function testLogStepRestart(): void {
    self::logSetVerbose(TRUE);

    self::logStepStart('First step');

    // A second start without a finish restarts the timer.
    self::logStepStart('Second step');
    usleep(10000);

    // The finish shows elapsed time for the second step.
    self::logStepFinish('Second step completed');
  }

  #[DoesNotPerformAssertions]
  public function testLogStepNameMismatch(): void {
    self::logSetVerbose(TRUE);

    $reflection_class = new \ReflectionClass(self::class);
    $steps_property = $reflection_class->getProperty('logSteps');

    $steps_property->setValue(NULL, [
      [
        'name' => 'differentMethodName',
        'start_time' => microtime(TRUE),
        'end_time' => NULL,
        'elapsed' => NULL,
      ],
    ]);

    // No elapsed time is shown because the method names do not match.
    self::logStepFinish('Current method');
  }

  #[DataProvider('dataProviderLogFormatElapsedTime')]
  public function testLogFormatElapsedTime(float $input_seconds, string $expected_output): void {
    $reflection_class = new \ReflectionClass(self::class);
    $method = $reflection_class->getMethod('logFormatElapsedTime');

    $result = $method->invoke(NULL, $input_seconds);
    $this->assertSame($expected_output, $result);
  }

  /**
   * @return \Iterator<string, array{float, string}>
   */
  public static function dataProviderLogFormatElapsedTime(): \Iterator {
    yield 'short_duration' => [5.3, '5s'];
    yield 'thirty_seconds' => [30.2, '30s'];
    yield 'almost_minute' => [59.4, '59s'];
    yield 'exact_minute' => [60.0, '1m'];
    yield 'two_minutes' => [120.0, '2m'];
    yield 'minute_with_seconds' => [65.3, '1m 5s'];
    yield 'longer_duration' => [150.2, '2m 30s'];
    yield 'complex_duration' => [345.4, '5m 45s'];
  }

  public function testLogStepSummarySilentMode(): void {
    self::logSetVerbose(FALSE);

    self::logStepStart('Test step');
    self::logStepFinish('Test step');

    $this->recreateLogBuffer();

    self::logStepSummary();

    $output = $this->getCapturedOutput();
    $this->assertSame('', $output);
  }

  public function testLogStepSummaryWithMixedSteps(): void {
    self::logStepStart('Completed step');
    // Delay long enough to show measurable time.
    usleep(1200000);
    self::logStepFinish('Completed step');

    self::logStepStart('Running step');

    $result = self::logStepSummary();

    $this->assertStringContainsString('| Step', $result);
    $this->assertStringContainsString('Complete', $result);
    $this->assertStringContainsString('Running', $result);
  }

  public function testLogStepSummaryWithCustomTitle(): void {
    self::logStepStart('Test step');
    self::logStepFinish('Test step');

    $result = self::logStepSummary();

    $this->assertStringContainsString('testLogStepSummaryWithCustomTitle', $result);
  }

  public function testLogStepMultipleTracking(): void {
    self::logStepStart('StepOne');
    self::logStepFinish('StepOne');

    self::logStepStart('StepTwo');
    self::logStepFinish('StepTwo');

    self::logStepStart('StepThree');
    // Leave StepThree running.
    $result = self::logStepSummary();

    $this->assertStringContainsString('testLogStepMultipleTracking', $result);
    $this->assertStringContainsString('Complete', $result);
    $this->assertStringContainsString('Running', $result);
  }

  public function testLogStepArrayTracking(): void {
    self::logSetVerbose(TRUE);

    $reflection_class = new \ReflectionClass(self::class);
    $steps_property = $reflection_class->getProperty('logSteps');

    $this->assertEmpty($steps_property->getValue());

    // The step name comes from the calling method, not the parameter.
    self::logStepStart('First step message');
    $steps = $steps_property->getValue();
    $this->assertIsArray($steps);
    $this->assertCount(1, $steps);
    // @phpstan-ignore-next-line offsetAccess.nonOffsetAccessible
    $this->assertSame('testLogStepArrayTracking', $steps[0]['name']);
    // @phpstan-ignore-next-line offsetAccess.nonOffsetAccessible
    $this->assertNull($steps[0]['end_time']);

    self::logStepFinish('First step completed');
    $steps = $steps_property->getValue();
    $this->assertIsArray($steps);
    // @phpstan-ignore-next-line offsetAccess.nonOffsetAccessible
    $this->assertNotNull($steps[0]['end_time']);
    // @phpstan-ignore-next-line offsetAccess.nonOffsetAccessible
    $this->assertNotNull($steps[0]['elapsed']);

    self::logStepStart('Second step message');
    $steps = $steps_property->getValue();
    $this->assertIsArray($steps);
    $this->assertCount(2, $steps);
    // @phpstan-ignore-next-line offsetAccess.nonOffsetAccessible
    $this->assertSame('testLogStepArrayTracking', $steps[1]['name']);
  }

  public function testLogSetOutputStream(): void {
    self::logSetVerbose(TRUE);

    $custom_buffer = fopen('php://memory', 'r+');
    if ($custom_buffer === FALSE) {
      throw new \RuntimeException('Failed to create custom buffer.');
    }
    self::logSetOutputStream($custom_buffer);

    self::log('Custom stream test');

    rewind($custom_buffer);
    $output = stream_get_contents($custom_buffer);
    $this->assertSame("\nCustom stream test\n", $output);

    fclose($custom_buffer);
  }

  public function testLogGetOutputStreamFallback(): void {
    self::logSetVerbose(TRUE);

    self::logSetOutputStream(NULL);

    $reflection_class = new \ReflectionClass(self::class);
    $method = $reflection_class->getMethod('logGetOutputStream');

    $stream = $method->invoke(NULL);
    $this->assertSame(STDERR, $stream);
  }

  #[DataProvider('dataProviderLogSetOutputStreamWithInvalidInput')]
  public function testLogSetOutputStreamWithInvalidInput(mixed $invalid_input): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Stream must be a valid resource or NULL.');

    // @phpstan-ignore-next-line argument.type
    self::logSetOutputStream($invalid_input);
  }

  /**
   * @return \Iterator<string, array{mixed}>
   */
  public static function dataProviderLogSetOutputStreamWithInvalidInput(): \Iterator {
    yield 'string' => ['invalid'];
    yield 'integer' => [123];
    yield 'array' => [[]];
    yield 'object' => [new \stdClass()];
    yield 'boolean' => [TRUE];
  }

  public function testLogSetOutputStreamWithValidResource(): void {
    $valid_resource = fopen('php://memory', 'r+');
    if ($valid_resource === FALSE) {
      throw new \RuntimeException('Failed to create test resource.');
    }

    self::logSetOutputStream($valid_resource);

    $reflection_class = new \ReflectionClass(self::class);
    $method = $reflection_class->getMethod('logGetOutputStream');

    $stream = $method->invoke(NULL);
    $this->assertSame($valid_resource, $stream);

    fclose($valid_resource);
  }

  public function testLogSubstepAndNoteOutput(): void {
    self::logSetVerbose(TRUE);

    self::logSubstep('Processing data');
    self::logNote('Important detail');

    $output = $this->getCapturedOutput();
    $this->assertStringContainsString('  --> Processing data', $output);
    $this->assertStringContainsString('    > Important detail', $output);
  }

  #[DataProvider('dataProviderLogMethodsVerboseMode')]
  public function testLogMethodsVerboseMode(bool $verbose_mode, string $description, callable $test_method): void {
    self::logSetVerbose($verbose_mode);

    $test_method(self::class);

    $output = $this->getCapturedOutput();

    if ($verbose_mode) {
      $this->assertNotEmpty($output, sprintf('Expected output for %s in verbose mode', $description));
    }
    else {
      $this->assertSame('', $output, sprintf('Expected no output for %s in silent mode', $description));
    }
  }

  /**
   * @return \Iterator<string, array{bool, string, callable}>
   */
  public static function dataProviderLogMethodsVerboseMode(): \Iterator {
    yield 'log_verbose' => [TRUE, 'log method', fn($self) => $self::log('Test message')];
    yield 'log_silent' => [FALSE, 'log method', fn($self) => $self::log('Test message')];
    yield 'log_substep_verbose' => [TRUE, 'logSubstep method', fn($self) => $self::logSubstep('Test substep')];
    yield 'log_substep_silent' => [FALSE, 'logSubstep method', fn($self) => $self::logSubstep('Test substep')];
    yield 'log_note_verbose' => [TRUE, 'logNote method', fn($self) => $self::logNote('Test note')];
    yield 'log_note_silent' => [FALSE, 'logNote method', fn($self) => $self::logNote('Test note')];
    yield 'log_step_start_verbose' => [TRUE, 'logStepStart method', fn($self) => $self::logStepStart('Test step')];
    yield 'log_step_start_silent' => [FALSE, 'logStepStart method', fn($self) => $self::logStepStart('Test step')];
    yield 'log_step_finish_verbose' => [TRUE, 'logStepFinish method', fn($self) => $self::logStepFinish('Test step')];
    yield 'log_step_finish_silent' => [FALSE, 'logStepFinish method', fn($self) => $self::logStepFinish('Test step')];
  }

  /**
   * @param array<string> $expected_output
   */
  #[DataProvider('dataProviderLogStepMethods')]
  public function testLogStepMethods(?string $message, array $expected_output): void {
    self::logSetVerbose(TRUE);

    if (str_contains($expected_output[0], 'START')) {
      self::logStepStart($message);
    }
    else {
      self::logStepStart('Initial step');
      self::logStepFinish($message);
    }

    $output = $this->getCapturedOutput();

    foreach ($expected_output as $expected_string) {
      $this->assertStringContainsString($expected_string, $output, sprintf("Expected to find '%s' in output", $expected_string));
    }
  }

  /**
   * @return \Iterator<string, array{(string|null), array<string>}>
   */
  public static function dataProviderLogStepMethods(): \Iterator {
    yield 'basic_step_start' => ['Starting process', ['STEP START | testLogStepMethods', 'Starting process']];
    yield 'step_finish_with_message' => ['Process completed', ['STEP DONE | testLogStepMethods', 'Process completed', '0s']];
    yield 'step_start_no_message' => [NULL, ['STEP START | testLogStepMethods']];
    yield 'step_finish_no_message' => [NULL, ['STEP DONE | testLogStepMethods', '0s']];
  }

  /**
   * @param array<string> $expected_strings
   */
  #[DataProvider('dataProviderLogSectionFormatting')]
  public function testLogSectionFormatting(string $title, ?string $message, bool $double_border, int $min_width, array $expected_strings): void {
    self::logSetVerbose(TRUE);

    $this->recreateLogBuffer();

    self::logSection($title, $message, $double_border, $min_width);

    $output = $this->getCapturedOutput();
    foreach ($expected_strings as $expected_string) {
      $this->assertStringContainsString($expected_string, $output, sprintf('Failed for title: %s', $title));
    }
  }

  /**
   * @return \Iterator<string, array{string, (string|null), bool, int, array<string>}>
   */
  public static function dataProviderLogSectionFormatting(): \Iterator {
    yield 'basic_title_only' => ['BASIC TITLE', NULL, FALSE, 60, ['BASIC TITLE', '---']];
    yield 'title_with_message' => ['TITLE', 'Message content', FALSE, 60, ['TITLE', 'Message content', '---']];
    yield 'double_border' => ['DOUBLE', 'Double message', TRUE, 60, ['DOUBLE', 'Double message', '===']];
    yield 'wide_section' => ['WIDE', NULL, FALSE, 100, ['WIDE', '---']];
  }

  public function testLogStepSummaryTableFormat(): void {
    self::logStepStart('Test step');
    self::logStepFinish('Test completed');

    self::logStepStart('Running step');

    $result = self::logStepSummary();

    $this->assertStringContainsString('| Step', $result);
    $this->assertStringContainsString('| Status', $result);
    $this->assertStringContainsString('| Elapsed', $result);
    $this->assertStringContainsString('Complete', $result);
    $this->assertStringContainsString('Running', $result);
  }

  public function testLogStepHierarchicalTracking(): void {
    self::logSetVerbose(TRUE);

    $reflection_class = new \ReflectionClass(self::class);
    $steps_property = $reflection_class->getProperty('logSteps');

    $stack_property = $reflection_class->getProperty('logStepStack');

    self::logStepStart('Level 1');
    $steps = $steps_property->getValue();
    $stack = $stack_property->getValue();

    // @phpstan-ignore-next-line argument.type
    $this->assertCount(1, $steps);
    // @phpstan-ignore-next-line offsetAccess.nonOffsetAccessible
    $this->assertEmpty($steps[0]['parent_stack']);
    $this->assertSame(['testLogStepHierarchicalTracking'], $stack);

    self::logStepStart('Level 2');
    $steps = $steps_property->getValue();
    $stack = $stack_property->getValue();

    // @phpstan-ignore-next-line argument.type
    $this->assertCount(2, $steps);
    // @phpstan-ignore-next-line offsetAccess.nonOffsetAccessible
    $this->assertSame(['testLogStepHierarchicalTracking'], $steps[1]['parent_stack']);
    $this->assertSame(['testLogStepHierarchicalTracking', 'testLogStepHierarchicalTracking'], $stack);

    self::logStepStart('Level 3');
    $steps = $steps_property->getValue();
    $stack = $stack_property->getValue();

    // @phpstan-ignore-next-line argument.type
    $this->assertCount(3, $steps);
    // @phpstan-ignore-next-line offsetAccess.nonOffsetAccessible
    $this->assertSame(['testLogStepHierarchicalTracking', 'testLogStepHierarchicalTracking'], $steps[2]['parent_stack']);
    $this->assertSame(['testLogStepHierarchicalTracking', 'testLogStepHierarchicalTracking', 'testLogStepHierarchicalTracking'], $stack);

    self::logStepFinish('Level 3 done');
    $stack = $stack_property->getValue();
    $this->assertSame(['testLogStepHierarchicalTracking', 'testLogStepHierarchicalTracking'], $stack);

    self::logStepFinish('Level 2 done');
    $stack = $stack_property->getValue();
    $this->assertSame(['testLogStepHierarchicalTracking'], $stack);

    self::logStepFinish('Level 1 done');
    $stack = $stack_property->getValue();
    $this->assertEmpty($stack);
  }

  public function testLogStepSummaryIndentation(): void {
    self::logStepStart('Parent step');
    self::logStepStart('Child step');
    self::logStepFinish('Child completed');
    self::logStepFinish('Parent completed');

    $result = self::logStepSummary('    ');
    $this->assertStringContainsString('testLogStepSummaryIndentation', $result);
    $this->assertStringContainsString('    testLogStepSummaryIndentation', $result);
  }

  public function testLogStepSummaryHierarchicalDisplay(): void {
    self::logStepStart('Main process');
    self::logStepStart('Sub process');
    self::logStepStart('Deep process');
    self::logStepFinish('Deep process done');
    self::logStepFinish('Sub process done');
    self::logStepFinish('Main process done');

    $result = self::logStepSummary();
    $this->assertStringContainsString('testLogStepSummaryHierarchicalDisplay', $result);
    $this->assertStringContainsString('  testLogStepSummaryHierarchicalDisplay', $result);
    $this->assertStringContainsString('    testLogStepSummaryHierarchicalDisplay', $result);
  }

  public function testLogStepSummaryReturn(): void {
    self::logStepStart('testReturnMode');
    self::logStepFinish('testReturnMode');

    $result = self::logStepSummary();

    $this->assertStringContainsString('testLogStepSummaryReturn', $result);
    $this->assertStringContainsString('Complete', $result);
  }

  public function testLogStepSummaryEmpty(): void {
    $result = self::logStepSummary();
    $this->assertSame('', $result);
  }

  public function testLogInfo(): void {
    self::logStepStart('TestStep');
    self::logStepFinish('TestStep');

    $info = $this->logInfo();

    $this->assertStringContainsString('STEP SUMMARY', $info);
    $this->assertStringContainsString('testLogInfo', $info);
    $this->assertStringContainsString('Complete', $info);
  }

  public function testLogInfoEmpty(): void {
    $info = $this->logInfo();

    $this->assertStringContainsString('STEP SUMMARY', $info);
    $this->assertSame("STEP SUMMARY\n", $info);
  }

  protected function recreateLogBuffer(): void {
    $buffer = fopen('php://memory', 'r+');

    if ($buffer === FALSE) {
      throw new \RuntimeException('Failed to create memory buffer.');
    }

    $this->logBuffer = $buffer;
    self::logSetOutputStream($this->logBuffer);
  }

  protected function getCapturedOutput(): string {
    rewind($this->logBuffer);
    return stream_get_contents($this->logBuffer);
  }

  /**
   * Helper method that starts with 'process' prefix for testing.
   */
  protected function processTest(): void {
    self::logStepStart('Testing process prefix');
    self::logStepFinish('Process prefix test completed');
  }

  /**
   * Helper method that starts with 'process' prefix for testing finish.
   */
  protected function processFinishTest(): void {
    self::logStepStart('Testing process finish');
    self::logStepFinish('Process finish test completed');
  }

}
