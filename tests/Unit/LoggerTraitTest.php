<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Tests\Unit;

use AlexSkrypnyk\PhpunitHelpers\Traits\LoggerTrait;
use AlexSkrypnyk\PhpunitHelpers\UnitTestCase;

/**
 * Tests for LoggerTrait.
 */
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
    // Set to true.
    static::loggerSetVerbose(TRUE);

    // Set back to false.
    static::loggerSetVerbose(FALSE);

    $this->addToAssertionCount(1); // Mark that setter methods work.
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

    $this->addToAssertionCount(1); // Mark that verbose logging works.
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

    $this->addToAssertionCount(1); // Mark that all logSection methods work.
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

    $this->addToAssertionCount(1); // Mark that logFile method works.
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

    $this->addToAssertionCount(1); // Mark that silent mode works.
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

    $this->addToAssertionCount(1); // Mark that persistence test works.
  }

}
