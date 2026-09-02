<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Tests\Unit;

use AlexSkrypnyk\PhpunitHelpers\Traits\LoggerTrait;
use AlexSkrypnyk\PhpunitHelpers\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;

/**
 * Tests for LoggerTrait.
 */
#[CoversTrait(LoggerTrait::class)]
final class LoggerTraitTest extends UnitTestCase {

  use LoggerTrait;

  /**
   * Memory buffer for capturing log output.
   *
   * @var resource
   */
  protected $logBuffer;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Reset verbose state for each test.
    self::loggerSetVerbose(FALSE);

    // Create memory buffer for capturing output.
    $buffer = fopen('php://memory', 'r+');
    if ($buffer === FALSE) {
      throw new \RuntimeException('Failed to create memory buffer');
    }
    $this->logBuffer = $buffer;
    self::loggerSetOutputStream($this->logBuffer);

    // Reset steps tracking arrays for each test.
    $reflection_class = new \ReflectionClass(self::class);
    $steps_property = $reflection_class->getProperty('loggerSteps');
    $steps_property->setValue(NULL, []);

    $stack_property = $reflection_class->getProperty('loggerStepStack');
    $stack_property->setValue(NULL, []);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    // Close the memory buffer.
    if (is_resource($this->logBuffer)) {
      fclose($this->logBuffer);
    }

    // Reset output stream to default.
    self::loggerSetOutputStream(NULL);

    parent::tearDown();
  }

  /**
   * Gets the captured log output from the buffer.
   *
   * @return string
   *   The captured output.
   */
  protected function getCapturedOutput(): string {
    rewind($this->logBuffer);
    return stream_get_contents($this->logBuffer);
  }

  /**
   * Test verbose mode setter and getter.
   */
  public function testVerboseMode(): void {
    // Test verbose enabled.
    self::loggerSetVerbose(TRUE);
    self::log('Verbose message');
    self::logSection('Verbose Section', 'Verbose content');

    $output = $this->getCapturedOutput();
    $this->assertStringContainsString('Verbose message', $output);
    $this->assertStringContainsString('Verbose Section', $output);
    $this->assertStringContainsString('Verbose content', $output);

    // Reset buffer and test verbose disabled.
    $buffer = fopen('php://memory', 'r+');
    if ($buffer === FALSE) {
      throw new \RuntimeException('Failed to create memory buffer');
    }
    $this->logBuffer = $buffer;
    self::loggerSetOutputStream($this->logBuffer);

    self::loggerSetVerbose(FALSE);
    self::log('Silent message');
    self::logSection('Silent Section', 'Silent content');

    $silent_output = $this->getCapturedOutput();
    $this->assertEmpty($silent_output);
  }

  /**
   * Test log method with verbose mode disabled.
   */
  public function testLogSilentMode(): void {
    self::loggerSetVerbose(FALSE);

    self::log('Test message');

    // Should produce no output when verbose is disabled.
    $output = $this->getCapturedOutput();
    $this->assertEmpty($output);
  }

  /**
   * Test log method with verbose mode enabled.
   */
  public function testLogVerboseMode(): void {
    self::loggerSetVerbose(TRUE);

    self::log('Test message');

    // Should produce output when verbose is enabled.
    $output = $this->getCapturedOutput();
    $this->assertSame("\nTest message\n", $output);
  }

  /**
   * Test logSection method - with visual output for inspection.
   */
  public function testLogSection(): void {
    self::loggerSetVerbose(TRUE);

    // Test basic section.
    self::logSection('TEST TITLE');

    // Test section with message.
    self::logSection('TEST TITLE', 'Test message content');

    // Test section with double border.
    self::logSection('TEST TITLE', 'Test message', TRUE);

    // Test section with custom width.
    self::logSection('TEST TITLE', NULL, FALSE, 80);

    // Verify the output contains expected elements.
    $output = $this->getCapturedOutput();
    $this->assertStringContainsString('TEST TITLE', $output);
    $this->assertStringContainsString('Test message content', $output);
    $this->assertStringContainsString('Test message', $output);
    // Single border.
    $this->assertStringContainsString('---', $output);
    // Double border.
    $this->assertStringContainsString('===', $output);
  }

  /**
   * Test logSection method with invalid min_width parameter.
   */
  public function testLogSectionWithInvalidMinWidth(): void {
    self::loggerSetVerbose(TRUE);

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Minimum width must be a positive integer.');

    self::logSection('TEST TITLE', NULL, FALSE, 0);
  }

  /**
   * Test logSection method with negative min_width parameter.
   */
  public function testLogSectionWithNegativeMinWidth(): void {
    self::loggerSetVerbose(TRUE);

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Minimum width must be a positive integer.');

    self::logSection('TEST TITLE', NULL, FALSE, -10);
  }

  /**
   * Test logFile method with existing file.
   */
  #[DoesNotPerformAssertions]
  public function testLogFileWithExistingFile(): void {
    self::loggerSetVerbose(TRUE);

    // Create a temporary file.
    $temp_file = tempnam(sys_get_temp_dir(), 'logger_test');
    file_put_contents($temp_file, 'Test file content');

    // Test logging the file.
    self::logFile($temp_file);
    self::logFile($temp_file, 'Custom message');

    // Clean up.
    unlink($temp_file);
  }

  /**
   * Test logFile method with unreadable file.
   */
  public function testLogFileWithUnreadableFile(): void {
    self::loggerSetVerbose(TRUE);

    // Create a temporary file.
    $temp_file = tempnam(sys_get_temp_dir(), 'logger_test');
    file_put_contents($temp_file, 'Test content');

    // Change permissions to make file unreadable.
    chmod($temp_file, 0000);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Failed to read file ' . $temp_file . '.');

    try {
      self::logFile($temp_file);
    }
    finally {
      // Restore permissions and clean up.
      chmod($temp_file, 0644);
      unlink($temp_file);
    }
  }

  /**
   * Test logFile method with non-existent file.
   */
  public function testLogFileWithNonExistentFile(): void {
    self::loggerSetVerbose(TRUE);

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('File /non/existent/file does not exist.');

    self::logFile('/non/existent/file');
  }

  /**
   * Test logFile method when verbose mode is disabled.
   */
  #[DoesNotPerformAssertions]
  public function testLogFileWithVerboseDisabled(): void {
    self::loggerSetVerbose(FALSE);

    // Create a temporary file.
    $temp_file = tempnam(sys_get_temp_dir(), 'logger_test');
    file_put_contents($temp_file, 'Test content');

    // This should not output anything and not throw exceptions.
    self::logFile($temp_file);

    // Clean up.
    unlink($temp_file);
  }

  /**
   * Test logSection method when verbose mode is disabled.
   */
  #[DoesNotPerformAssertions]
  public function testLogSectionWithVerboseDisabled(): void {
    self::loggerSetVerbose(FALSE);

    // This should not output anything and not throw exceptions.
    self::logSection('TEST TITLE', 'Test message');
  }

  /**
   * Test that methods are silent when verbose mode is disabled.
   */
  #[DoesNotPerformAssertions]
  public function testSilentModeForAllMethods(): void {
    self::loggerSetVerbose(FALSE);

    // All these should execute without output.
    self::log('Test message');
    self::logSection('TEST TITLE', 'Test message');

    // Create temp file for logFile test.
    $temp_file = tempnam(sys_get_temp_dir(), 'logger_test');
    file_put_contents($temp_file, 'Test content');
    self::logFile($temp_file);
    unlink($temp_file);
  }

  /**
   * Test verbose mode persistence across method calls.
   */
  #[DoesNotPerformAssertions]
  public function testVerboseModePersistence(): void {
    // Set verbose mode.
    self::loggerSetVerbose(TRUE);

    // Call various methods.
    self::log('Message 1');

    // Disable verbose mode.
    self::loggerSetVerbose(FALSE);

    // Call methods again.
    self::log('Message 2');
  }

  /**
   * Test logStepStart method with verbose mode enabled.
   */
  public function testLogStepStartVerboseMode(): void {
    self::loggerSetVerbose(TRUE);

    // Test step start without message.
    self::logStepStart();

    $output = $this->getCapturedOutput();
    $this->assertStringContainsString('STEP START | testLogStepStartVerboseMode', $output);
    $this->assertStringContainsString('---', $output);
  }

  /**
   * Test logStepStart method with custom step method prefix.
   */
  public function testLogStepStartWithCustomPrefix(): void {
    self::loggerSetVerbose(TRUE);

    // Set custom prefix.
    $original_prefix = self::$loggerStepMethodPrefix;
    self::$loggerStepMethodPrefix = 'process';

    try {
      $this->processTest();

      $output = $this->getCapturedOutput();
      $this->assertStringContainsString('PROCESS START | processTest', $output);
    }
    finally {
      // Restore original prefix.
      self::$loggerStepMethodPrefix = $original_prefix;
    }
  }

  /**
   * Test logStepStart method with verbose mode disabled.
   */
  #[DoesNotPerformAssertions]
  public function testLogStepStartSilentMode(): void {
    self::loggerSetVerbose(FALSE);

    // This should not output anything and not throw exceptions.
    self::logStepStart();
    self::logStepStart('Silent step start');
  }

  /**
   * Test logStepFinish method with verbose mode enabled.
   */
  public function testLogStepFinishVerboseMode(): void {
    self::loggerSetVerbose(TRUE);

    // Start a step first, then finish it to test elapsed time.
    self::logStepStart();
    self::logStepFinish('Completed the test step');

    $output = $this->getCapturedOutput();
    $this->assertStringContainsString('STEP START | testLogStepFinishVerboseMode', $output);
    $this->assertStringContainsString('STEP DONE | testLogStepFinishVerboseMode | 0s', $output);
    $this->assertStringContainsString('Completed the test step', $output);
  }

  /**
   * Test logStepFinish method with custom step method prefix.
   */
  public function testLogStepFinishWithCustomPrefix(): void {
    self::loggerSetVerbose(TRUE);

    // Set custom prefix.
    $original_prefix = self::$loggerStepMethodPrefix;
    self::$loggerStepMethodPrefix = 'process';

    try {
      $this->processFinishTest();

      $output = $this->getCapturedOutput();
      $this->assertStringContainsString('PROCESS START | processFinishTest', $output);
      $this->assertStringContainsString('PROCESS DONE | processFinishTest | 0s', $output);
    }
    finally {
      // Restore original prefix.
      self::$loggerStepMethodPrefix = $original_prefix;
    }
  }

  /**
   * Test logStepFinish method with verbose mode disabled.
   */
  #[DoesNotPerformAssertions]
  public function testLogStepFinishSilentMode(): void {
    self::loggerSetVerbose(FALSE);

    // This should not output anything and not throw exceptions.
    self::logStepFinish();
    self::logStepFinish('Silent step finish');
  }

  /**
   * Test logSubstep method with verbose mode enabled.
   */
  #[DoesNotPerformAssertions]
  public function testLogSubstepVerboseMode(): void {
    self::loggerSetVerbose(TRUE);

    // Test substep logging.
    self::logSubstep('Processing substep 1');
    self::logSubstep('Processing substep 2');
  }

  /**
   * Test logSubstep method with verbose mode disabled.
   */
  #[DoesNotPerformAssertions]
  public function testLogSubstepSilentMode(): void {
    self::loggerSetVerbose(FALSE);

    // This should not output anything and not throw exceptions.
    self::logSubstep('Silent substep');
  }

  /**
   * Test logNote method with verbose mode enabled.
   */
  #[DoesNotPerformAssertions]
  public function testLogNoteVerboseMode(): void {
    self::loggerSetVerbose(TRUE);

    // Test note logging.
    self::logNote('Important note about the process');
    self::logNote('Another note with details');
  }

  /**
   * Test logNote method with verbose mode disabled.
   */
  #[DoesNotPerformAssertions]
  public function testLogNoteSilentMode(): void {
    self::loggerSetVerbose(FALSE);

    // This should not output anything and not throw exceptions.
    self::logNote('Silent note');
  }

  /**
   * Test step logging workflow - with visual output for inspection.
   */
  public function testStepLoggingWorkflow(): void {
    self::loggerSetVerbose(TRUE);

    // Test a complete step workflow.
    self::logStepStart('Test workflow');
    self::logSubstep('Initializing');
    self::logNote('Setting up test data');
    self::logSubstep('Processing');
    self::logNote('Performing calculations');
    self::logStepFinish('Test workflow completed');

    // Verify the output contains all expected elements.
    $output = $this->getCapturedOutput();
    $this->assertStringContainsString('STEP START | testStepLoggingWorkflow', $output);
    $this->assertStringContainsString('STEP DONE | testStepLoggingWorkflow | 0s', $output);
    $this->assertStringContainsString('Test workflow', $output);
    $this->assertStringContainsString('Test workflow completed', $output);
    $this->assertStringContainsString('  --> Initializing', $output);
    $this->assertStringContainsString('  --> Processing', $output);
    $this->assertStringContainsString('    > Setting up test data', $output);
    $this->assertStringContainsString('    > Performing calculations', $output);
  }

  /**
   * Test that step methods respect verbose mode.
   *
   * With visual output for inspection.
   */
  public function testStepMethodsRespectVerboseMode(): void {
    // Test with verbose disabled - should be silent.
    self::loggerSetVerbose(FALSE);
    self::logStepStart('Silent step');
    self::logSubstep('Silent substep');
    self::logNote('Silent note');
    self::logStepFinish('Silent step end');

    $silent_output = $this->getCapturedOutput();
    $this->assertEmpty($silent_output);

    // Reset buffer and steps tracking, then test with verbose enabled.
    $buffer = fopen('php://memory', 'r+');
    if ($buffer === FALSE) {
      throw new \RuntimeException('Failed to create memory buffer');
    }
    $this->logBuffer = $buffer;
    self::loggerSetOutputStream($this->logBuffer);

    // Clear steps array to prevent interference from silent calls.
    $reflection_class = new \ReflectionClass(self::class);
    $steps_property = $reflection_class->getProperty('loggerSteps');
    $steps_property->setValue(NULL, []);

    self::loggerSetVerbose(TRUE);
    self::logStepStart('Verbose step');
    self::logSubstep('Verbose substep');
    self::logNote('Verbose note');
    self::logStepFinish('Verbose step end');

    // Verify verbose output contains expected content.
    $verbose_output = $this->getCapturedOutput();
    $this->assertStringContainsString('STEP START | testStepMethodsRespectVerboseMode', $verbose_output);
    $this->assertStringContainsString('STEP DONE | testStepMethodsRespectVerboseMode | 0s', $verbose_output);
    $this->assertStringContainsString('Verbose step', $verbose_output);
    $this->assertStringContainsString('Verbose step end', $verbose_output);
    $this->assertStringContainsString('  --> Verbose substep', $verbose_output);
    $this->assertStringContainsString('    > Verbose note', $verbose_output);
  }

  /**
   * Test elapsed time calculation and formatting.
   */
  #[DoesNotPerformAssertions]
  public function testElapsedTimeCalculation(): void {
    self::loggerSetVerbose(TRUE);

    // Test step with elapsed time.
    self::logStepStart('Timed step');
    // Sleep for 1.5 seconds to show measurable elapsed time.
    usleep(1500000);
    self::logStepFinish('Timed step completed');
  }

  /**
   * Test logStepFinish without corresponding logStepStart.
   */
  #[DoesNotPerformAssertions]
  public function testLogStepFinishWithoutStart(): void {
    self::loggerSetVerbose(TRUE);

    // This should not show elapsed time and not throw exceptions.
    self::logStepFinish('Orphan step');
  }

  /**
   * Test step restart behavior.
   */
  #[DoesNotPerformAssertions]
  public function testStepRestart(): void {
    self::loggerSetVerbose(TRUE);

    // Start first step.
    self::logStepStart('First step');

    // Start second step without finishing first (should restart timer).
    self::logStepStart('Second step');
    // Sleep for 10ms.
    usleep(10000);

    // Finish second step (should show elapsed time for second step).
    self::logStepFinish('Second step completed');
  }

  /**
   * Test step name mismatch behavior.
   */
  #[DoesNotPerformAssertions]
  public function testStepNameMismatch(): void {
    self::loggerSetVerbose(TRUE);

    // Manually add a step with different method name to the tracking array.
    $reflection_class = new \ReflectionClass(self::class);
    $steps_property = $reflection_class->getProperty('loggerSteps');

    // Add a step with a different name that won't match the current method.
    $steps_property->setValue(NULL, [
      [
        'name' => 'differentMethodName',
        'start_time' => microtime(TRUE),
        'end_time' => NULL,
        'elapsed' => NULL,
      ],
    ]);

    // This should not show elapsed time since method names don't match.
    self::logStepFinish('Current method');
  }

  /**
   * Test formatElapsedTime method with various durations.
   */
  #[DataProvider('dataProviderFormatElapsedTime')]
  public function testFormatElapsedTime(float $input_seconds, string $expected_output): void {
    $reflection_class = new \ReflectionClass(self::class);
    $method = $reflection_class->getMethod('formatElapsedTime');

    $result = $method->invoke(NULL, $input_seconds);
    $this->assertSame($expected_output, $result);
  }

  /**
   * Provides test data for formatElapsedTime method.
   *
   * @return \Iterator<string, array{float, string}>
   *   Test cases: [input_seconds, expected_output]
   */
  public static function dataProviderFormatElapsedTime(): \Iterator {
    yield 'short_duration' => [5.3, '5s'];
    yield 'thirty_seconds' => [30.2, '30s'];
    yield 'almost_minute' => [59.4, '59s'];
    yield 'exact_minute' => [60.0, '1m'];
    yield 'two_minutes' => [120.0, '2m'];
    yield 'minute_with_seconds' => [65.3, '1m 5s'];
    yield 'longer_duration' => [150.2, '2m 30s'];
    yield 'complex_duration' => [345.4, '5m 45s'];
  }

  /**
   * Test logStepSummary with no steps tracked.
   */
  public function testLogStepSummaryWithNoSteps(): void {
    self::loggerSetVerbose(TRUE);

    // Should produce no output when no steps tracked.
    self::logStepSummary();

    $output = $this->getCapturedOutput();
    $this->assertEmpty($output);
  }

  /**
   * Test logStepSummary with verbose mode disabled.
   */
  public function testLogStepSummaryWithVerboseDisabled(): void {
    self::loggerSetVerbose(FALSE);

    // Add some steps to tracking array - these should be silent.
    self::logStepStart('Test step');
    self::logStepFinish('Test step');

    // Clear output from any potential leakage.
    $this->getCapturedOutput();

    // Reset buffer.
    $buffer = fopen('php://memory', 'r+');
    if ($buffer === FALSE) {
      throw new \RuntimeException('Failed to create memory buffer');
    }
    $this->logBuffer = $buffer;
    self::loggerSetOutputStream($this->logBuffer);

    // Should not output anything.
    self::logStepSummary();

    $output = $this->getCapturedOutput();
    $this->assertEmpty($output);
  }

  /**
   * Test logStepSummary with completed and running steps.
   */
  public function testLogStepSummaryWithMixedSteps(): void {
    self::logStepStart('Completed step');
    // 1.2 second delay to show measurable time.
    usleep(1200000);
    self::logStepFinish('Completed step');

    self::logStepStart('Running step');

    $result = self::logStepSummary();

    $this->assertStringContainsString('| Step', $result);
    $this->assertStringContainsString('Complete', $result);
    $this->assertStringContainsString('Running', $result);
  }

  /**
   * Test logStepSummary with custom title.
   */
  public function testLogStepSummaryWithCustomTitle(): void {
    self::logStepStart('Test step');
    self::logStepFinish('Test step');

    $result = self::logStepSummary();

    $this->assertStringContainsString('testLogStepSummaryWithCustomTitle', $result);
  }

  /**
   * Test multiple step tracking and summary.
   */
  public function testMultipleStepTracking(): void {
    self::logStepStart('StepOne');
    self::logStepFinish('StepOne');

    self::logStepStart('StepTwo');
    self::logStepFinish('StepTwo');

    self::logStepStart('StepThree');
    // Leave StepThree running.
    $result = self::logStepSummary();

    $this->assertStringContainsString('testMultipleStepTracking', $result);
    $this->assertStringContainsString('Complete', $result);
    $this->assertStringContainsString('Running', $result);
  }

  /**
   * Test that all steps are tracked in the array.
   */
  public function testStepArrayTracking(): void {
    self::loggerSetVerbose(TRUE);

    // Access the steps array via reflection.
    $reflection_class = new \ReflectionClass(self::class);
    $steps_property = $reflection_class->getProperty('loggerSteps');

    // Initially should be empty.
    $this->assertEmpty($steps_property->getValue());

    // Add first step (name comes from method name, not parameter).
    self::logStepStart('First step message');
    $steps = $steps_property->getValue();
    $this->assertIsArray($steps);
    $this->assertCount(1, $steps);
    // @phpstan-ignore-next-line offsetAccess.nonOffsetAccessible
    $this->assertSame('testStepArrayTracking', $steps[0]['name']);
    // @phpstan-ignore-next-line offsetAccess.nonOffsetAccessible
    $this->assertNull($steps[0]['end_time']);

    // Finish first step.
    self::logStepFinish('First step completed');
    $steps = $steps_property->getValue();
    $this->assertIsArray($steps);
    // @phpstan-ignore-next-line offsetAccess.nonOffsetAccessible
    $this->assertNotNull($steps[0]['end_time']);
    // @phpstan-ignore-next-line offsetAccess.nonOffsetAccessible
    $this->assertNotNull($steps[0]['elapsed']);

    // Add second step.
    self::logStepStart('Second step message');
    $steps = $steps_property->getValue();
    $this->assertIsArray($steps);
    $this->assertCount(2, $steps);
    // @phpstan-ignore-next-line offsetAccess.nonOffsetAccessible
    $this->assertSame('testStepArrayTracking', $steps[1]['name']);
  }

  /**
   * Test loggerSetOutputStream method.
   */
  public function testLoggerSetOutputStream(): void {
    self::loggerSetVerbose(TRUE);

    // Create a custom buffer.
    $custom_buffer = fopen('php://memory', 'r+');
    if ($custom_buffer === FALSE) {
      throw new \RuntimeException('Failed to create custom buffer');
    }
    self::loggerSetOutputStream($custom_buffer);

    self::log('Custom stream test');

    rewind($custom_buffer);
    $output = stream_get_contents($custom_buffer);
    $this->assertSame("\nCustom stream test\n", $output);

    fclose($custom_buffer);
  }

  /**
   * Test output stream fallback to STDERR when set to NULL.
   */
  public function testLoggerOutputStreamFallback(): void {
    self::loggerSetVerbose(TRUE);

    // Set stream to NULL (should fallback to STDERR).
    self::loggerSetOutputStream(NULL);

    // Use reflection to test the getOutputStream method.
    $reflection_class = new \ReflectionClass(self::class);
    $method = $reflection_class->getMethod('getOutputStream');

    $stream = $method->invoke(NULL);
    $this->assertSame(STDERR, $stream);
  }

  /**
   * Test loggerSetOutputStream validation with invalid input.
   */
  public function testLoggerSetOutputStreamWithInvalidInput(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Stream must be a valid resource or NULL.');

    // Try to set an invalid stream (string instead of resource).
    // @phpstan-ignore-next-line argument.type
    self::loggerSetOutputStream('invalid_stream');
  }

  /**
   * Test loggerSetOutputStream validation with various invalid types.
   */
  public function testLoggerSetOutputStreamWithVariousInvalidTypes(): void {
    $invalid_inputs = [
      'string' => 'invalid',
      'integer' => 123,
      'array' => [],
      'object' => new \stdClass(),
      'boolean' => TRUE,
    ];

    foreach ($invalid_inputs as $type => $invalid_input) {
      try {
        // @phpstan-ignore-next-line argument.type
        self::loggerSetOutputStream($invalid_input);
        $this->fail(sprintf('Expected InvalidArgumentException for %s input', $type));
      }
      catch (\InvalidArgumentException $e) {
        $this->assertSame('Stream must be a valid resource or NULL.', $e->getMessage());
      }
    }
  }

  /**
   * Test loggerSetOutputStream accepts valid resource.
   */
  public function testLoggerSetOutputStreamWithValidResource(): void {
    $valid_resource = fopen('php://memory', 'r+');
    if ($valid_resource === FALSE) {
      throw new \RuntimeException('Failed to create test resource');
    }

    // Should not throw an exception.
    self::loggerSetOutputStream($valid_resource);

    // Verify it was set correctly.
    $reflection_class = new \ReflectionClass(self::class);
    $method = $reflection_class->getMethod('getOutputStream');

    $stream = $method->invoke(NULL);
    $this->assertSame($valid_resource, $stream);

    fclose($valid_resource);
  }

  /**
   * Test substep and note output formatting.
   */
  public function testSubstepAndNoteOutput(): void {
    self::loggerSetVerbose(TRUE);

    self::logSubstep('Processing data');
    self::logNote('Important detail');

    $output = $this->getCapturedOutput();
    $this->assertStringContainsString('  --> Processing data', $output);
    $this->assertStringContainsString('    > Important detail', $output);
  }

  /**
   * Test various logger methods in verbose and silent modes.
   */
  #[DataProvider('dataProviderLoggerMethodsVerboseMode')]
  public function testLoggerMethodsVerboseMode(bool $verbose_mode, string $description, callable $test_method): void {
    self::loggerSetVerbose($verbose_mode);

    $test_method(self::class);

    $output = $this->getCapturedOutput();

    if ($verbose_mode) {
      $this->assertNotEmpty($output, sprintf('Expected output for %s in verbose mode', $description));
    }
    else {
      $this->assertEmpty($output, sprintf('Expected no output for %s in silent mode', $description));
    }
  }

  /**
   * Provides test data for verbose mode testing.
   *
   * @return \Iterator<string, array{bool, string, callable}>
   *   Test cases: [verbose_mode, test_description, test_method]
   */
  public static function dataProviderLoggerMethodsVerboseMode(): \Iterator {
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
   * Test step methods with various parameters.
   *
   * @param array<string> $expected_output
   */
  #[DataProvider('dataProviderStepMethods')]
  public function testStepMethods(string $step_name, ?string $message, array $expected_output): void {
    self::loggerSetVerbose(TRUE);

    // Test both start and finish for completeness.
    if (str_contains($expected_output[0], 'START')) {
      self::logStepStart($message);
    }
    else {
      // First start a step, then finish it.
      self::logStepStart('Initial step');
      self::logStepFinish($message);
    }

    $output = $this->getCapturedOutput();

    foreach ($expected_output as $expected_string) {
      $this->assertStringContainsString($expected_string, $output, sprintf("Expected to find '%s' in output", $expected_string));
    }
  }

  /**
   * Provides test data for step method workflow testing.
   *
   * @return \Iterator<string, array{string, (string|null), array<string>}>
   *   Test cases: [step_name, message, expected_output_contains]
   */
  public static function dataProviderStepMethods(): \Iterator {
    yield 'basic_step_start' => ['testStep', 'Starting process', ['STEP START | testStepMethods', 'Starting process']];
    yield 'step_finish_with_message' => ['testStep', 'Process completed', ['STEP DONE | testStepMethods', 'Process completed', '0s']];
    yield 'step_start_no_message' => ['testStep', NULL, ['STEP START | testStepMethods']];
    yield 'step_finish_no_message' => ['testStep', NULL, ['STEP DONE | testStepMethods', '0s']];
  }

  /**
   * Test section formatting with various parameters.
   *
   * @param array<string> $expected_strings
   */
  #[DataProvider('dataProviderSectionFormatting')]
  public function testSectionFormatting(string $title, ?string $message, bool $double_border, int $min_width, array $expected_strings): void {
    self::loggerSetVerbose(TRUE);

    // Reset buffer for each test case.
    $buffer = fopen('php://memory', 'r+');
    if ($buffer === FALSE) {
      throw new \RuntimeException('Failed to create memory buffer');
    }
    $this->logBuffer = $buffer;
    self::loggerSetOutputStream($this->logBuffer);

    self::logSection($title, $message, $double_border, $min_width);

    $output = $this->getCapturedOutput();
    foreach ($expected_strings as $expected_string) {
      $this->assertStringContainsString($expected_string, $output, sprintf('Failed for title: %s', $title));
    }
  }

  /**
   * Provides test data for section formatting.
   *
   * @return \Iterator<string, array{string, (string|null), bool, int, array<string>}>
   *   Test cases: [title, message, double_border, min_width, expected_strings]
   */
  public static function dataProviderSectionFormatting(): \Iterator {
    yield 'basic_title_only' => ['BASIC TITLE', NULL, FALSE, 60, ['BASIC TITLE', '---']];
    yield 'title_with_message' => ['TITLE', 'Message content', FALSE, 60, ['TITLE', 'Message content', '---']];
    yield 'double_border' => ['DOUBLE', 'Double message', TRUE, 60, ['DOUBLE', 'Double message', '===']];
    yield 'wide_section' => ['WIDE', NULL, FALSE, 100, ['WIDE', '---']];
  }

  /**
   * Test step summary table output format.
   */
  public function testStepSummaryTableFormat(): void {
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

  /**
   * Test hierarchical step tracking with parent stack.
   */
  public function testHierarchicalStepTracking(): void {
    self::loggerSetVerbose(TRUE);

    // Access the steps array via reflection.
    $reflection_class = new \ReflectionClass(self::class);
    $steps_property = $reflection_class->getProperty('loggerSteps');

    $stack_property = $reflection_class->getProperty('loggerStepStack');

    // Test nested steps.
    self::logStepStart('Level 1');
    $steps = $steps_property->getValue();
    $stack = $stack_property->getValue();

    // @phpstan-ignore-next-line argument.type
    $this->assertCount(1, $steps);
    // @phpstan-ignore-next-line offsetAccess.nonOffsetAccessible
    $this->assertEmpty($steps[0]['parent_stack']);
    $this->assertSame(['testHierarchicalStepTracking'], $stack);

    // Start nested step.
    self::logStepStart('Level 2');
    $steps = $steps_property->getValue();
    $stack = $stack_property->getValue();

    // @phpstan-ignore-next-line argument.type
    $this->assertCount(2, $steps);
    // @phpstan-ignore-next-line offsetAccess.nonOffsetAccessible
    $this->assertSame(['testHierarchicalStepTracking'], $steps[1]['parent_stack']);
    $this->assertSame(['testHierarchicalStepTracking', 'testHierarchicalStepTracking'], $stack);

    // Start deeply nested step.
    self::logStepStart('Level 3');
    $steps = $steps_property->getValue();
    $stack = $stack_property->getValue();

    // @phpstan-ignore-next-line argument.type
    $this->assertCount(3, $steps);
    // @phpstan-ignore-next-line offsetAccess.nonOffsetAccessible
    $this->assertSame(['testHierarchicalStepTracking', 'testHierarchicalStepTracking'], $steps[2]['parent_stack']);
    $this->assertSame(['testHierarchicalStepTracking', 'testHierarchicalStepTracking', 'testHierarchicalStepTracking'], $stack);

    // Finish level 3.
    self::logStepFinish('Level 3 done');
    $stack = $stack_property->getValue();
    $this->assertSame(['testHierarchicalStepTracking', 'testHierarchicalStepTracking'], $stack);

    // Finish level 2.
    self::logStepFinish('Level 2 done');
    $stack = $stack_property->getValue();
    $this->assertSame(['testHierarchicalStepTracking'], $stack);

    // Finish level 1.
    self::logStepFinish('Level 1 done');
    $stack = $stack_property->getValue();
    $this->assertEmpty($stack);
  }

  /**
   * Test configurable step indentation.
   */
  public function testConfigurableStepIndentation(): void {
    // Create nested steps.
    self::logStepStart('Parent step');
    self::logStepStart('Child step');
    self::logStepFinish('Child completed');
    self::logStepFinish('Parent completed');

    $result = self::logStepSummary('    ');
    $this->assertStringContainsString('testConfigurableStepIndentation', $result);
    $this->assertStringContainsString('    testConfigurableStepIndentation', $result);
  }

  /**
   * Test step summary with hierarchical indentation display.
   */
  public function testStepSummaryHierarchicalDisplay(): void {
    // Create a hierarchy of steps.
    self::logStepStart('Main process');
    self::logStepStart('Sub process');
    self::logStepStart('Deep process');
    self::logStepFinish('Deep process done');
    self::logStepFinish('Sub process done');
    self::logStepFinish('Main process done');

    $result = self::logStepSummary();
    $this->assertStringContainsString('testStepSummaryHierarchicalDisplay', $result);
    $this->assertStringContainsString('  testStepSummaryHierarchicalDisplay', $result);
    $this->assertStringContainsString('    testStepSummaryHierarchicalDisplay', $result);
  }

  /**
   * Test logStepSummary returns string.
   */
  public function testLogStepSummaryReturn(): void {
    self::logStepStart('testReturnMode');
    self::logStepFinish('testReturnMode');

    $result = self::logStepSummary();

    $this->assertStringContainsString('testLogStepSummaryReturn', $result);
    $this->assertStringContainsString('Complete', $result);
  }

  /**
   * Test logStepSummary with no steps.
   */
  public function testLogStepSummaryEmpty(): void {
    // Should return empty string when no steps.
    $result = self::logStepSummary();
    $this->assertSame('', $result);
  }

  /**
   * Test loggerInfo method.
   */
  public function testLoggerInfo(): void {
    self::logStepStart('TestStep');
    self::logStepFinish('TestStep');

    $info = $this->loggerInfo();

    $this->assertStringContainsString('STEP SUMMARY', $info);
    $this->assertStringContainsString('testLoggerInfo', $info);
    $this->assertStringContainsString('Complete', $info);
  }

  /**
   * Test loggerInfo with no steps.
   */
  public function testLoggerInfoEmpty(): void {
    $info = $this->loggerInfo();

    $this->assertStringContainsString('STEP SUMMARY', $info);
    $this->assertSame("STEP SUMMARY\n", $info);
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
