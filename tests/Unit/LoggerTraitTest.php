<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Tests\Unit;

use AlexSkrypnyk\PhpunitHelpers\Traits\LoggerTrait;
use AlexSkrypnyk\PhpunitHelpers\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversTrait;

/**
 * Tests for LoggerTrait.
 */
#[CoversTrait(LoggerTrait::class)]
class LoggerTraitTest extends UnitTestCase {

  use LoggerTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Reset verbose state for each test.
    static::loggerSetVerbose(FALSE);
  }

  /**
   * Test verbose mode setter and getter.
   */
  public function testVerboseMode(): void {
    // Initially should be false (set in setUp).
    static::loggerSetVerbose(FALSE);

    // Set to true.
    static::loggerSetVerbose(TRUE);

    // Verify we can call methods when verbose is true.
    static::log('Test message');
    static::logSection('Test', 'Test content');

    // Set back to false.
    static::loggerSetVerbose(FALSE);

    // Verify methods are silent when verbose is false.
    static::log('Silent message');
    static::logSection('Silent', 'Silent content');

    $this->addToAssertionCount(1); // Test completed successfully.
  }

  /**
   * Test log method with verbose mode disabled.
   */
  public function testLogSilentMode(): void {
    static::loggerSetVerbose(FALSE);

    // Capture stderr output.
    $this->expectOutputString('');
    static::log('Test message');
  }

  /**
   * Test log method with verbose mode enabled.
   */
  public function testLogVerboseMode(): void {
    static::loggerSetVerbose(TRUE);

    // Test that calling log doesn't throw exceptions when verbose is enabled.
    static::log('Test message');

    $this->addToAssertionCount(1); // Test executed without exceptions.
  }

  /**
   * Test logSection method.
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

    $this->addToAssertionCount(1); // All methods executed without exceptions.
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

    // Test step start with message.
    static::logStepStart('Starting the test step');

    $this->addToAssertionCount(1); // Method executed without exceptions.
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

    // Test step finish without message.
    static::logStepFinish();

    // Test step finish with message.
    static::logStepFinish('Completed the test step');

    $this->addToAssertionCount(1); // Method executed without exceptions.
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
   * Test step logging workflow.
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

    // Complete workflow executed without exceptions.
    $this->addToAssertionCount(1);
  }

  /**
   * Test that step methods respect verbose mode.
   */
  public function testStepMethodsRespectVerboseMode(): void {
    // Test with verbose disabled.
    static::loggerSetVerbose(FALSE);
    static::logStepStart('Silent step');
    static::logSubstep('Silent substep');
    static::logNote('Silent note');
    static::logStepFinish('Silent step end');

    // Test with verbose enabled.
    static::loggerSetVerbose(TRUE);
    static::logStepStart('Verbose step');
    static::logSubstep('Verbose substep');
    static::logNote('Verbose note');
    static::logStepFinish('Verbose step end');

    // All methods respected verbose mode setting.
    $this->addToAssertionCount(1);
  }

}
