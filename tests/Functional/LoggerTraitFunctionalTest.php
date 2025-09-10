<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Tests\Functional;

use AlexSkrypnyk\PhpunitHelpers\Traits\LoggerTrait;
use AlexSkrypnyk\PhpunitHelpers\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversTrait;

/**
 * Functional tests for LoggerTrait that output to real STDERR.
 */
#[CoversTrait(LoggerTrait::class)]
class LoggerTraitFunctionalTest extends UnitTestCase {

  use LoggerTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Reset verbose state for each test.
    static::loggerSetVerbose(FALSE);

    // Reset steps tracking array for each test.
    $reflection_class = new \ReflectionClass(static::class);
    $steps_property = $reflection_class->getProperty('loggerSteps');
    $steps_property->setAccessible(TRUE);
    $steps_property->setValue(NULL, []);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    // Reset output stream to default.
    static::loggerSetOutputStream(NULL);
    parent::tearDown();
  }

  /**
   * Functional test: Demonstrate basic logging to STDERR.
   *
   * This test outputs to real STDERR to show what the logging looks like.
   */
  public function testFunctionalBasicLogging(): void {
    // Reset to default STDERR output.
    static::loggerSetOutputStream(NULL);
    static::loggerSetVerbose(TRUE);

    static::log('This is a basic log message');
    static::logSection('TEST SECTION', 'This is a test section with content');

    $this->addToAssertionCount(1);
  }

  /**
   * Functional test: Demonstrate step workflow to STDERR.
   *
   * This test shows a complete step workflow with timing.
   */
  public function testFunctionalStepWorkflow(): void {
    // Reset to default STDERR output.
    static::loggerSetOutputStream(NULL);
    static::loggerSetVerbose(TRUE);

    static::logStepStart('Processing data');
    static::logSubstep('Loading configuration');
    static::logNote('Using default settings');
    static::logSubstep('Validating input');
    usleep(500000); // 0.5 second delay to show elapsed time.
    static::logStepFinish('Data processing complete');

    static::logStepStart('Generating output');
    static::logNote('Creating report format');
    usleep(200000); // 0.2 second delay.
    static::logStepFinish('Output generated successfully');

    static::logStepSummary('WORKFLOW SUMMARY');

    $this->addToAssertionCount(1);
  }

  /**
   * Functional test: Demonstrate section formatting variations.
   *
   * This test shows different section formatting options.
   */
  public function testFunctionalSectionFormatting(): void {
    // Reset to default STDERR output.
    static::loggerSetOutputStream(NULL);
    static::loggerSetVerbose(TRUE);

    static::logSection('STANDARD SECTION', 'This is a standard section with single border');
    static::logSection('DOUBLE BORDER SECTION', 'This section uses double border characters', TRUE);
    static::logSection('WIDE SECTION', 'This section has a wider minimum width', FALSE, 90);
    static::logSection('MULTI-LINE', "This section contains\nmultiple lines of content\nto demonstrate wrapping");

    $this->addToAssertionCount(1);
  }

  /**
   * Functional test: Demonstrate file logging to STDERR.
   *
   * This test shows file content logging.
   */
  public function testFunctionalFileLogging(): void {
    // Reset to default STDERR output.
    static::loggerSetOutputStream(NULL);
    static::loggerSetVerbose(TRUE);

    // Create a temporary test file.
    $tempFile = tempnam(sys_get_temp_dir(), 'logger_functional_test');
    file_put_contents($tempFile, "Sample file content\nLine 2\nLine 3\n");

    static::logFile($tempFile, 'Test configuration file');

    // Clean up.
    unlink($tempFile);

    $this->addToAssertionCount(1);
  }

}
