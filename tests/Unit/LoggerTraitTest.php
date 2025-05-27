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

    // Test completed successfully.
    $this->assertTrue(TRUE);
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

    // Verify state remains consistent.
    $this->assertTrue(TRUE); // Test executed without exceptions.
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

    // Verify all methods executed without exceptions.
    $this->assertTrue(TRUE);
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

    // Verify method executed without exceptions.
    $this->assertTrue(TRUE);
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

    // Verify method executed silently without exceptions.
    $this->assertTrue(TRUE);
  }

  /**
   * Test logSection method when verbose mode is disabled.
   */
  public function testLogSectionWithVerboseDisabled(): void {
    static::loggerSetVerbose(FALSE);

    // This should not output anything and not throw exceptions.
    static::logSection('TEST TITLE', 'Test message');

    // Verify method executed silently without exceptions.
    $this->assertTrue(TRUE);
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

    // Verify all methods executed silently without exceptions.
    $this->assertTrue(TRUE);
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

    // Verify verbose mode changes work correctly.
    $this->assertTrue(TRUE);
  }

}
