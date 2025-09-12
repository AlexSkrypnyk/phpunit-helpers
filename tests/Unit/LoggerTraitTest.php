<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Tests\Unit;

use AlexSkrypnyk\PhpunitHelpers\Traits\LoggerTrait;
use AlexSkrypnyk\PhpunitHelpers\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for LoggerTrait.
 */
#[CoversTrait(LoggerTrait::class)]
class LoggerTraitTest extends UnitTestCase {

  use LoggerTrait;

  /**
   * Memory buffer for capturing log output.
   *
   * @var resource
   */
  private $logBuffer;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Reset verbose state for each test.
    static::loggerSetVerbose(FALSE);

    // Create memory buffer for capturing output.
    $buffer = fopen('php://memory', 'r+');
    if ($buffer === FALSE) {
      throw new \RuntimeException('Failed to create memory buffer');
    }
    $this->logBuffer = $buffer;
    static::loggerSetOutputStream($this->logBuffer);

    // Reset steps tracking arrays for each test.
    $reflection_class = new \ReflectionClass(static::class);
    $steps_property = $reflection_class->getProperty('loggerSteps');
    $steps_property->setAccessible(TRUE);
    $steps_property->setValue(NULL, []);

    $stack_property = $reflection_class->getProperty('loggerStepStack');
    $stack_property->setAccessible(TRUE);
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
    static::loggerSetOutputStream(NULL);

    parent::tearDown();
  }

  /**
   * Gets the captured log output from the buffer.
   *
   * @return string
   *   The captured output.
   */
  private function getCapturedOutput(): string {
    rewind($this->logBuffer);
    return stream_get_contents($this->logBuffer);
  }

  /**
   * Test verbose mode setter and getter.
   */
  public function testVerboseMode(): void {
    // Test verbose enabled.
    static::loggerSetVerbose(TRUE);
    static::log('Verbose message');
    static::logSection('Verbose Section', 'Verbose content');

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
    static::loggerSetOutputStream($this->logBuffer);

    static::loggerSetVerbose(FALSE);
    static::log('Silent message');
    static::logSection('Silent Section', 'Silent content');

    $silentOutput = $this->getCapturedOutput();
    $this->assertEmpty($silentOutput);
  }

  /**
   * Test log method with verbose mode disabled.
   */
  public function testLogSilentMode(): void {
    static::loggerSetVerbose(FALSE);

    static::log('Test message');

    // Should produce no output when verbose is disabled.
    $output = $this->getCapturedOutput();
    $this->assertEmpty($output);
  }

  /**
   * Test log method with verbose mode enabled.
   */
  public function testLogVerboseMode(): void {
    static::loggerSetVerbose(TRUE);

    static::log('Test message');

    // Should produce output when verbose is enabled.
    $output = $this->getCapturedOutput();
    $this->assertEquals("\nTest message\n", $output);
  }

  /**
   * Test logSection method - with visual output for inspection.
   */
  public function testLogSection(): void {
    static::loggerSetVerbose(TRUE);

    // Test basic section.
    static::logSection('TEST TITLE');

    // Test section with message.
    static::logSection('TEST TITLE', 'Test message content');

    // Test section with double border.
    static::logSection('TEST TITLE', 'Test message', TRUE);

    // Test section with custom width.
    static::logSection('TEST TITLE', NULL, FALSE, 80);

    // Verify the output contains expected elements.
    $output = $this->getCapturedOutput();
    $this->assertStringContainsString('TEST TITLE', $output);
    $this->assertStringContainsString('Test message content', $output);
    $this->assertStringContainsString('Test message', $output);
    $this->assertStringContainsString('---', $output); // Single border.
    $this->assertStringContainsString('===', $output); // Double border.
  }

  /**
   * Test logSection method with invalid min_width parameter.
   */
  public function testLogSectionWithInvalidMinWidth(): void {
    static::loggerSetVerbose(TRUE);

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Minimum width must be a positive integer.');

    static::logSection('TEST TITLE', NULL, FALSE, 0);
  }

  /**
   * Test logSection method with negative min_width parameter.
   */
  public function testLogSectionWithNegativeMinWidth(): void {
    static::loggerSetVerbose(TRUE);

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Minimum width must be a positive integer.');

    static::logSection('TEST TITLE', NULL, FALSE, -10);
  }

  /**
   * Test logFile method with existing file.
   */
  public function testLogFileWithExistingFile(): void {
    static::loggerSetVerbose(TRUE);

    // Create a temporary file.
    $temp_file = tempnam(sys_get_temp_dir(), 'logger_test');
    file_put_contents($temp_file, 'Test file content');

    // Test logging the file.
    static::logFile($temp_file);
    static::logFile($temp_file, 'Custom message');

    // Clean up.
    unlink($temp_file);

    $this->addToAssertionCount(1); // Method executed without exceptions.
  }

  /**
   * Test logFile method with unreadable file.
   */
  public function testLogFileWithUnreadableFile(): void {
    static::loggerSetVerbose(TRUE);

    // Create a temporary file.
    $temp_file = tempnam(sys_get_temp_dir(), 'logger_test');
    file_put_contents($temp_file, 'Test content');

    // Change permissions to make file unreadable.
    chmod($temp_file, 0000);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Failed to read file ' . $temp_file . '.');

    try {
      static::logFile($temp_file);
    } finally {
      // Restore permissions and clean up.
      chmod($temp_file, 0644);
      unlink($temp_file);
    }
  }

  /**
   * Test logFile method with non-existent file.
   */
  public function testLogFileWithNonExistentFile(): void {
    static::loggerSetVerbose(TRUE);

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('File /non/existent/file does not exist.');

    static::logFile('/non/existent/file');
  }

  /**
   * Test logFile method when verbose mode is disabled.
   */
  public function testLogFileWithVerboseDisabled(): void {
    static::loggerSetVerbose(FALSE);

    // Create a temporary file.
    $temp_file = tempnam(sys_get_temp_dir(), 'logger_test');
    file_put_contents($temp_file, 'Test content');

    // This should not output anything and not throw exceptions.
    static::logFile($temp_file);

    // Clean up.
    unlink($temp_file);

    // Method executed silently without exceptions.
    $this->addToAssertionCount(1);
  }

  /**
   * Test logSection method when verbose mode is disabled.
   */
  public function testLogSectionWithVerboseDisabled(): void {
    static::loggerSetVerbose(FALSE);

    // This should not output anything and not throw exceptions.
    static::logSection('TEST TITLE', 'Test message');

    // Method executed silently without exceptions.
    $this->addToAssertionCount(1);
  }

  /**
   * Test that methods are silent when verbose mode is disabled.
   */
  public function testSilentModeForAllMethods(): void {
    static::loggerSetVerbose(FALSE);

    // All these should execute without output.
    static::log('Test message');
    static::logSection('TEST TITLE', 'Test message');

    // Create temp file for logFile test.
    $temp_file = tempnam(sys_get_temp_dir(), 'logger_test');
    file_put_contents($temp_file, 'Test content');
    static::logFile($temp_file);
    unlink($temp_file);

    // All methods executed silently without exceptions.
    $this->addToAssertionCount(1);
  }

  /**
   * Test verbose mode persistence across method calls.
   */
  public function testVerboseModePersistence(): void {
    // Set verbose mode.
    static::loggerSetVerbose(TRUE);

    // Call various methods.
    static::log('Message 1');

    // Disable verbose mode.
    static::loggerSetVerbose(FALSE);

    // Call methods again.
    static::log('Message 2');

    $this->addToAssertionCount(1); // Verbose mode changes work correctly.
  }

  /**
   * Test logStepStart method with verbose mode enabled.
   */
  public function testLogStepStartVerboseMode(): void {
    static::loggerSetVerbose(TRUE);

    // Test step start without message.
    static::logStepStart();

    $output = $this->getCapturedOutput();
    $this->assertStringContainsString('STEP START | testLogStepStartVerboseMode', $output);
    $this->assertStringContainsString('---', $output);
  }

  /**
   * Test logStepStart method with verbose mode disabled.
   */
  public function testLogStepStartSilentMode(): void {
    static::loggerSetVerbose(FALSE);

    // This should not output anything and not throw exceptions.
    static::logStepStart();
    static::logStepStart('Silent step start');

    // Method executed silently without exceptions.
    $this->addToAssertionCount(1);
  }

  /**
   * Test logStepFinish method with verbose mode enabled.
   */
  public function testLogStepFinishVerboseMode(): void {
    static::loggerSetVerbose(TRUE);

    // Start a step first, then finish it to test elapsed time.
    static::logStepStart();
    static::logStepFinish('Completed the test step');

    $output = $this->getCapturedOutput();
    $this->assertStringContainsString('STEP START | testLogStepFinishVerboseMode', $output);
    $this->assertStringContainsString('STEP DONE | testLogStepFinishVerboseMode | 0s', $output);
    $this->assertStringContainsString('Completed the test step', $output);
  }

  /**
   * Test logStepFinish method with verbose mode disabled.
   */
  public function testLogStepFinishSilentMode(): void {
    static::loggerSetVerbose(FALSE);

    // This should not output anything and not throw exceptions.
    static::logStepFinish();
    static::logStepFinish('Silent step finish');

    // Method executed silently without exceptions.
    $this->addToAssertionCount(1);
  }

  /**
   * Test logSubstep method with verbose mode enabled.
   */
  public function testLogSubstepVerboseMode(): void {
    static::loggerSetVerbose(TRUE);

    // Test substep logging.
    static::logSubstep('Processing substep 1');
    static::logSubstep('Processing substep 2');

    $this->addToAssertionCount(1); // Method executed without exceptions.
  }

  /**
   * Test logSubstep method with verbose mode disabled.
   */
  public function testLogSubstepSilentMode(): void {
    static::loggerSetVerbose(FALSE);

    // This should not output anything and not throw exceptions.
    static::logSubstep('Silent substep');

    // Method executed silently without exceptions.
    $this->addToAssertionCount(1);
  }

  /**
   * Test logNote method with verbose mode enabled.
   */
  public function testLogNoteVerboseMode(): void {
    static::loggerSetVerbose(TRUE);

    // Test note logging.
    static::logNote('Important note about the process');
    static::logNote('Another note with details');

    $this->addToAssertionCount(1); // Method executed without exceptions.
  }

  /**
   * Test logNote method with verbose mode disabled.
   */
  public function testLogNoteSilentMode(): void {
    static::loggerSetVerbose(FALSE);

    // This should not output anything and not throw exceptions.
    static::logNote('Silent note');

    // Method executed silently without exceptions.
    $this->addToAssertionCount(1);
  }

  /**
   * Test step logging workflow - with visual output for inspection.
   */
  public function testStepLoggingWorkflow(): void {
    static::loggerSetVerbose(TRUE);

    // Test a complete step workflow.
    static::logStepStart('Test workflow');
    static::logSubstep('Initializing');
    static::logNote('Setting up test data');
    static::logSubstep('Processing');
    static::logNote('Performing calculations');
    static::logStepFinish('Test workflow completed');

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
    static::loggerSetVerbose(FALSE);
    static::logStepStart('Silent step');
    static::logSubstep('Silent substep');
    static::logNote('Silent note');
    static::logStepFinish('Silent step end');

    $silentOutput = $this->getCapturedOutput();
    if (!empty($silentOutput)) {
      echo "DEBUG: Unexpected output: " . json_encode($silentOutput) . "\n";
    }
    $this->assertEmpty($silentOutput);

    // Reset buffer and steps tracking, then test with verbose enabled.
    $buffer = fopen('php://memory', 'r+');
    if ($buffer === FALSE) {
      throw new \RuntimeException('Failed to create memory buffer');
    }
    $this->logBuffer = $buffer;
    static::loggerSetOutputStream($this->logBuffer);

    // Clear steps array to prevent interference from silent calls.
    $reflection_class = new \ReflectionClass(static::class);
    $steps_property = $reflection_class->getProperty('loggerSteps');
    $steps_property->setAccessible(TRUE);
    $steps_property->setValue(NULL, []);

    static::loggerSetVerbose(TRUE);
    static::logStepStart('Verbose step');
    static::logSubstep('Verbose substep');
    static::logNote('Verbose note');
    static::logStepFinish('Verbose step end');

    // Verify verbose output contains expected content.
    $verboseOutput = $this->getCapturedOutput();
    $this->assertStringContainsString('STEP START | testStepMethodsRespectVerboseMode', $verboseOutput);
    $this->assertStringContainsString('STEP DONE | testStepMethodsRespectVerboseMode | 0s', $verboseOutput);
    $this->assertStringContainsString('Verbose step', $verboseOutput);
    $this->assertStringContainsString('Verbose step end', $verboseOutput);
    $this->assertStringContainsString('  --> Verbose substep', $verboseOutput);
    $this->assertStringContainsString('    > Verbose note', $verboseOutput);
  }

  /**
   * Test elapsed time calculation and formatting.
   */
  public function testElapsedTimeCalculation(): void {
    static::loggerSetVerbose(TRUE);

    // Test step with elapsed time.
    static::logStepStart('Timed step');
    usleep(1500000); // Sleep for 1.5 seconds to show measurable elapsed time.
    static::logStepFinish('Timed step completed');

    // Test completed without exceptions.
    $this->addToAssertionCount(1);
  }

  /**
   * Test logStepFinish without corresponding logStepStart.
   */
  public function testLogStepFinishWithoutStart(): void {
    static::loggerSetVerbose(TRUE);

    // This should not show elapsed time and not throw exceptions.
    static::logStepFinish('Orphan step');

    // Method executed without exceptions.
    $this->addToAssertionCount(1);
  }

  /**
   * Test step restart behavior.
   */
  public function testStepRestart(): void {
    static::loggerSetVerbose(TRUE);

    // Start first step.
    static::logStepStart('First step');

    // Start second step without finishing first (should restart timer).
    static::logStepStart('Second step');
    usleep(10000); // Sleep for 10ms.

    // Finish second step (should show elapsed time for second step).
    static::logStepFinish('Second step completed');

    // Test completed without exceptions.
    $this->addToAssertionCount(1);
  }

  /**
   * Test step name mismatch behavior.
   */
  public function testStepNameMismatch(): void {
    static::loggerSetVerbose(TRUE);

    // Manually add a step with different method name to the tracking array.
    $reflection_class = new \ReflectionClass(static::class);
    $steps_property = $reflection_class->getProperty('loggerSteps');
    $steps_property->setAccessible(TRUE);

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
    static::logStepFinish('Current method');

    // Method executed without exceptions.
    $this->addToAssertionCount(1);
  }

  /**
   * Provides test data for formatElapsedTime method.
   *
   * @return array<string, array{float, string}>
   *   Test cases: [input_seconds, expected_output]
   */
  public static function formatElapsedTimeProvider(): array {
    return [
      'short duration' => [5.3, '5s'],
      'thirty seconds' => [30.2, '30s'],
      'almost minute' => [59.4, '59s'],
      'exact minute' => [60.0, '1m'],
      'two minutes' => [120.0, '2m'],
      'minute with seconds' => [65.3, '1m 5s'],
      'longer duration' => [150.2, '2m 30s'],
      'complex duration' => [345.4, '5m 45s'],
    ];
  }

  /**
   * Test formatElapsedTime method with various durations.
   */
  #[DataProvider('formatElapsedTimeProvider')]
  public function testFormatElapsedTime(float $inputSeconds, string $expectedOutput): void {
    $reflection_class = new \ReflectionClass(static::class);
    $method = $reflection_class->getMethod('formatElapsedTime');
    $method->setAccessible(TRUE);

    $result = $method->invoke(NULL, $inputSeconds);
    $this->assertEquals($expectedOutput, $result);
  }

  /**
   * Test logStepSummary with no steps tracked.
   */
  public function testLogStepSummaryWithNoSteps(): void {
    static::loggerSetVerbose(TRUE);

    // Should produce no output when no steps tracked.
    static::logStepSummary();

    $output = $this->getCapturedOutput();
    $this->assertEmpty($output);
  }

  /**
   * Test logStepSummary with verbose mode disabled.
   */
  public function testLogStepSummaryWithVerboseDisabled(): void {
    static::loggerSetVerbose(FALSE);

    // Add some steps to tracking array - these should be silent.
    static::logStepStart('Test step');
    static::logStepFinish('Test step');

    // Clear output from any potential leakage.
    $this->getCapturedOutput();

    // Reset buffer.
    $buffer = fopen('php://memory', 'r+');
    if ($buffer === FALSE) {
      throw new \RuntimeException('Failed to create memory buffer');
    }
    $this->logBuffer = $buffer;
    static::loggerSetOutputStream($this->logBuffer);

    // Should not output anything.
    static::logStepSummary();

    $output = $this->getCapturedOutput();
    $this->assertEmpty($output);
  }

  /**
   * Test logStepSummary with completed and running steps.
   */
  public function testLogStepSummaryWithMixedSteps(): void {
    static::logStepStart('Completed step');
    usleep(1200000); // 1.2 second delay to show measurable time.
    static::logStepFinish('Completed step');

    static::logStepStart('Running step');

    $result = static::logStepSummary();

    $this->assertStringContainsString('| Step', $result);
    $this->assertStringContainsString('Complete', $result);
    $this->assertStringContainsString('Running', $result);
  }

  /**
   * Test logStepSummary with custom title.
   */
  public function testLogStepSummaryWithCustomTitle(): void {
    static::logStepStart('Test step');
    static::logStepFinish('Test step');

    $result = static::logStepSummary();

    $this->assertStringContainsString('testLogStepSummaryWithCustomTitle', $result);
  }

  /**
   * Test multiple step tracking and summary.
   */
  public function testMultipleStepTracking(): void {
    static::logStepStart('StepOne');
    static::logStepFinish('StepOne');

    static::logStepStart('StepTwo');
    static::logStepFinish('StepTwo');

    static::logStepStart('StepThree');
    // Leave StepThree running.

    $result = static::logStepSummary();

    $this->assertStringContainsString('testMultipleStepTracking', $result);
    $this->assertStringContainsString('Complete', $result);
    $this->assertStringContainsString('Running', $result);
  }

  /**
   * Test that all steps are tracked in the array.
   */
  public function testStepArrayTracking(): void {
    static::loggerSetVerbose(TRUE);

    // Access the steps array via reflection.
    $reflection_class = new \ReflectionClass(static::class);
    $steps_property = $reflection_class->getProperty('loggerSteps');
    $steps_property->setAccessible(TRUE);

    // Initially should be empty.
    $this->assertEmpty($steps_property->getValue(NULL));

    // Add first step (name comes from method name, not parameter).
    static::logStepStart('First step message');
    $steps = $steps_property->getValue(NULL);
    $this->assertIsArray($steps);
    $this->assertCount(1, $steps);
    // @phpstan-ignore-next-line offsetAccess.nonOffsetAccessible
    $this->assertEquals('testStepArrayTracking', $steps[0]['name']);
    // @phpstan-ignore-next-line offsetAccess.nonOffsetAccessible
    $this->assertNull($steps[0]['end_time']);

    // Finish first step.
    static::logStepFinish('First step completed');
    $steps = $steps_property->getValue(NULL);
    $this->assertIsArray($steps);
    // @phpstan-ignore-next-line offsetAccess.nonOffsetAccessible
    $this->assertNotNull($steps[0]['end_time']);
    // @phpstan-ignore-next-line offsetAccess.nonOffsetAccessible
    $this->assertNotNull($steps[0]['elapsed']);

    // Add second step.
    static::logStepStart('Second step message');
    $steps = $steps_property->getValue(NULL);
    $this->assertIsArray($steps);
    $this->assertCount(2, $steps);
    // @phpstan-ignore-next-line offsetAccess.nonOffsetAccessible
    $this->assertEquals('testStepArrayTracking', $steps[1]['name']);
  }

  /**
   * Test loggerSetOutputStream method.
   */
  public function testLoggerSetOutputStream(): void {
    static::loggerSetVerbose(TRUE);

    // Create a custom buffer.
    $customBuffer = fopen('php://memory', 'r+');
    if ($customBuffer === FALSE) {
      throw new \RuntimeException('Failed to create custom buffer');
    }
    static::loggerSetOutputStream($customBuffer);

    static::log('Custom stream test');

    rewind($customBuffer);
    $output = stream_get_contents($customBuffer);
    $this->assertEquals("\nCustom stream test\n", $output);

    fclose($customBuffer);
  }

  /**
   * Test output stream fallback to STDERR when set to NULL.
   */
  public function testLoggerOutputStreamFallback(): void {
    static::loggerSetVerbose(TRUE);

    // Set stream to NULL (should fallback to STDERR).
    static::loggerSetOutputStream(NULL);

    // Use reflection to test the getOutputStream method.
    $reflection_class = new \ReflectionClass(static::class);
    $method = $reflection_class->getMethod('getOutputStream');
    $method->setAccessible(TRUE);

    $stream = $method->invoke(NULL);
    $this->assertEquals(STDERR, $stream);
  }

  /**
   * Test loggerSetOutputStream validation with invalid input.
   */
  public function testLoggerSetOutputStreamWithInvalidInput(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Stream must be a valid resource or NULL.');

    // Try to set an invalid stream (string instead of resource).
    // @phpstan-ignore-next-line argument.type
    static::loggerSetOutputStream('invalid_stream');
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
        static::loggerSetOutputStream($invalid_input);
        $this->fail(sprintf('Expected InvalidArgumentException for %s input', $type));
      }
      catch (\InvalidArgumentException $e) {
        $this->assertEquals('Stream must be a valid resource or NULL.', $e->getMessage());
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
    static::loggerSetOutputStream($valid_resource);

    // Verify it was set correctly.
    $reflection_class = new \ReflectionClass(static::class);
    $method = $reflection_class->getMethod('getOutputStream');
    $method->setAccessible(TRUE);

    $stream = $method->invoke(NULL);
    $this->assertEquals($valid_resource, $stream);

    fclose($valid_resource);
  }

  /**
   * Test substep and note output formatting.
   */
  public function testSubstepAndNoteOutput(): void {
    static::loggerSetVerbose(TRUE);

    static::logSubstep('Processing data');
    static::logNote('Important detail');

    $output = $this->getCapturedOutput();
    $this->assertStringContainsString('  --> Processing data', $output);
    $this->assertStringContainsString('    > Important detail', $output);
  }

  /**
   * Provides test data for verbose mode testing.
   *
   * @return array<string, array{bool, string, callable}>
   *   Test cases: [verbose_mode, test_description, test_method]
   */
  public static function verboseModeProvider(): array {
    return [
      'log verbose' => [TRUE, 'log method', fn($self) => $self::log('Test message')],
      'log silent' => [FALSE, 'log method', fn($self) => $self::log('Test message')],
      'logSubstep verbose' => [TRUE, 'logSubstep method', fn($self) => $self::logSubstep('Test substep')],
      'logSubstep silent' => [FALSE, 'logSubstep method', fn($self) => $self::logSubstep('Test substep')],
      'logNote verbose' => [TRUE, 'logNote method', fn($self) => $self::logNote('Test note')],
      'logNote silent' => [FALSE, 'logNote method', fn($self) => $self::logNote('Test note')],
      'logStepStart verbose' => [TRUE, 'logStepStart method', fn($self) => $self::logStepStart('Test step')],
      'logStepStart silent' => [FALSE, 'logStepStart method', fn($self) => $self::logStepStart('Test step')],
      'logStepFinish verbose' => [TRUE, 'logStepFinish method', fn($self) => $self::logStepFinish('Test step')],
      'logStepFinish silent' => [FALSE, 'logStepFinish method', fn($self) => $self::logStepFinish('Test step')],
    ];
  }

  /**
   * Test various logger methods in verbose and silent modes.
   */
  #[DataProvider('verboseModeProvider')]
  public function testLoggerMethodsVerboseMode(bool $verboseMode, string $description, callable $testMethod): void {
    static::loggerSetVerbose($verboseMode);

    $testMethod(static::class);

    $output = $this->getCapturedOutput();

    if ($verboseMode) {
      $this->assertNotEmpty($output, sprintf('Expected output for %s in verbose mode', $description));
    }
    else {
      $this->assertEmpty($output, sprintf('Expected no output for %s in silent mode', $description));
    }
  }

  /**
   * Provides test data for step method workflow testing.
   *
   * @return array<string, array{string, string|null, array<string>}>
   *   Test cases: [step_name, message, expected_output_contains]
   */
  public static function stepMethodProvider(): array {
    return [
      'basic step start' => ['testStep', 'Starting process', ['STEP START | testStepMethods', 'Starting process']],
      'step finish with message' => ['testStep', 'Process completed', ['STEP DONE | testStepMethods', 'Process completed', '0s']],
      'step start no message' => ['testStep', NULL, ['STEP START | testStepMethods']],
      'step finish no message' => ['testStep', NULL, ['STEP DONE | testStepMethods', '0s']],
    ];
  }

  /**
   * Test step methods with various parameters.
   *
   * @param array<string> $expectedOutput
   */
  #[DataProvider('stepMethodProvider')]
  public function testStepMethods(string $stepName, ?string $message, array $expectedOutput): void {
    static::loggerSetVerbose(TRUE);

    // Test both start and finish for completeness.
    if (str_contains($expectedOutput[0], 'START')) {
      static::logStepStart($message);
    }
    else {
      // First start a step, then finish it.
      static::logStepStart('Initial step');
      static::logStepFinish($message);
    }

    $output = $this->getCapturedOutput();

    foreach ($expectedOutput as $expectedString) {
      $this->assertStringContainsString($expectedString, $output, sprintf("Expected to find '%s' in output", $expectedString));
    }
  }

  /**
   * Provides test data for section formatting.
   *
   * @return array<string, array{string, string|null, bool, int, array<string>}>
   *   Test cases: [title, message, doubleBorder, minWidth, expectedStrings]
   */
  public static function sectionFormattingProvider(): array {
    return [
      'basic title only' => ['BASIC TITLE', NULL, FALSE, 60, ['BASIC TITLE', '---']],
      'title with message' => ['TITLE', 'Message content', FALSE, 60, ['TITLE', 'Message content', '---']],
      'double border' => ['DOUBLE', 'Double message', TRUE, 60, ['DOUBLE', 'Double message', '===']],
      'wide section' => ['WIDE', NULL, FALSE, 100, ['WIDE', '---']],
    ];
  }

  /**
   * Test section formatting with various parameters.
   *
   * @param array<string> $expectedStrings
   */
  #[DataProvider('sectionFormattingProvider')]
  public function testSectionFormatting(string $title, ?string $message, bool $doubleBorder, int $minWidth, array $expectedStrings): void {
    static::loggerSetVerbose(TRUE);

    // Reset buffer for each test case.
    $buffer = fopen('php://memory', 'r+');
    if ($buffer === FALSE) {
      throw new \RuntimeException('Failed to create memory buffer');
    }
    $this->logBuffer = $buffer;
    static::loggerSetOutputStream($this->logBuffer);

    static::logSection($title, $message, $doubleBorder, $minWidth);

    $output = $this->getCapturedOutput();
    foreach ($expectedStrings as $expectedString) {
      $this->assertStringContainsString($expectedString, $output, 'Failed for title: ' . $title);
    }
  }

  /**
   * Test step summary table output format.
   */
  public function testStepSummaryTableFormat(): void {
    static::logStepStart('Test step');
    static::logStepFinish('Test completed');

    static::logStepStart('Running step');

    $result = static::logStepSummary();

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
    static::loggerSetVerbose(TRUE);

    // Access the steps array via reflection.
    $reflection_class = new \ReflectionClass(static::class);
    $steps_property = $reflection_class->getProperty('loggerSteps');
    $steps_property->setAccessible(TRUE);

    $stack_property = $reflection_class->getProperty('loggerStepStack');
    $stack_property->setAccessible(TRUE);

    // Test nested steps.
    static::logStepStart('Level 1');
    $steps = $steps_property->getValue(NULL);
    $stack = $stack_property->getValue(NULL);

    // @phpstan-ignore-next-line argument.type
    $this->assertCount(1, $steps);
    // @phpstan-ignore-next-line offsetAccess.nonOffsetAccessible
    $this->assertEmpty($steps[0]['parent_stack']);
    $this->assertEquals(['testHierarchicalStepTracking'], $stack);

    // Start nested step.
    static::logStepStart('Level 2');
    $steps = $steps_property->getValue(NULL);
    $stack = $stack_property->getValue(NULL);

    // @phpstan-ignore-next-line argument.type
    $this->assertCount(2, $steps);
    // @phpstan-ignore-next-line offsetAccess.nonOffsetAccessible
    $this->assertEquals(['testHierarchicalStepTracking'], $steps[1]['parent_stack']);
    $this->assertEquals(['testHierarchicalStepTracking', 'testHierarchicalStepTracking'], $stack);

    // Start deeply nested step.
    static::logStepStart('Level 3');
    $steps = $steps_property->getValue(NULL);
    $stack = $stack_property->getValue(NULL);

    // @phpstan-ignore-next-line argument.type
    $this->assertCount(3, $steps);
    // @phpstan-ignore-next-line offsetAccess.nonOffsetAccessible
    $this->assertEquals(['testHierarchicalStepTracking', 'testHierarchicalStepTracking'], $steps[2]['parent_stack']);
    $this->assertEquals(['testHierarchicalStepTracking', 'testHierarchicalStepTracking', 'testHierarchicalStepTracking'], $stack);

    // Finish level 3.
    static::logStepFinish('Level 3 done');
    $stack = $stack_property->getValue(NULL);
    $this->assertEquals(['testHierarchicalStepTracking', 'testHierarchicalStepTracking'], $stack);

    // Finish level 2.
    static::logStepFinish('Level 2 done');
    $stack = $stack_property->getValue(NULL);
    $this->assertEquals(['testHierarchicalStepTracking'], $stack);

    // Finish level 1.
    static::logStepFinish('Level 1 done');
    $stack = $stack_property->getValue(NULL);
    $this->assertEmpty($stack);
  }

  /**
   * Test configurable step indentation.
   */
  public function testConfigurableStepIndentation(): void {
    // Create nested steps.
    static::logStepStart('Parent step');
    static::logStepStart('Child step');
    static::logStepFinish('Child completed');
    static::logStepFinish('Parent completed');

    $result = static::logStepSummary('    ');
    $this->assertStringContainsString('testConfigurableStepIndentation', $result);
    $this->assertStringContainsString('    testConfigurableStepIndentation', $result);
  }

  /**
   * Test step summary with hierarchical indentation display.
   */
  public function testStepSummaryHierarchicalDisplay(): void {
    // Create a hierarchy of steps.
    static::logStepStart('Main process');
    static::logStepStart('Sub process');
    static::logStepStart('Deep process');
    static::logStepFinish('Deep process done');
    static::logStepFinish('Sub process done');
    static::logStepFinish('Main process done');

    $result = static::logStepSummary();
    $this->assertStringContainsString('testStepSummaryHierarchicalDisplay', $result);
    $this->assertStringContainsString('  testStepSummaryHierarchicalDisplay', $result);
    $this->assertStringContainsString('    testStepSummaryHierarchicalDisplay', $result);
  }

  /**
   * Test logStepSummary returns string.
   */
  public function testLogStepSummaryReturn(): void {
    static::logStepStart('testReturnMode');
    static::logStepFinish('testReturnMode');

    $result = static::logStepSummary();

    $this->addToAssertionCount(1);
    $this->assertStringContainsString('testLogStepSummaryReturn', $result);
    $this->assertStringContainsString('Complete', $result);
  }

  /**
   * Test logStepSummary with no steps.
   */
  public function testLogStepSummaryEmpty(): void {
    // Should return empty string when no steps.
    $result = static::logStepSummary();
    $this->assertSame('', $result);
  }

  /**
   * Test loggerInfo method.
   */
  public function testLoggerInfo(): void {
    static::logStepStart('TestStep');
    static::logStepFinish('TestStep');

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

}
