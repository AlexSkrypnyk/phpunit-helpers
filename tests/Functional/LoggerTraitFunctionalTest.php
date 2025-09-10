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

  /**
   * Functional test: Demonstrate hierarchical step logging to STDERR.
   *
   * This test shows nested step workflows with hierarchy visualization.
   */
  public function testFunctionalHierarchicalSteps(): void {
    // Reset to default STDERR output.
    static::loggerSetOutputStream(NULL);
    static::loggerSetVerbose(TRUE);

    // Start the main deployment process.
    $this->stepDeploymentProcess();

    // Show hierarchical summary with default indentation.
    static::logStepSummary('DEPLOYMENT SUMMARY');

    $this->addToAssertionCount(1);
  }

  /**
   * Main deployment process step.
   */
  private function stepDeploymentProcess(): void {
    static::logStepStart('Starting main deployment workflow');
    static::log('Initializing deployment environment');
    static::logSection('DEPLOYMENT CONFIGURATION', 'Production environment settings loaded');

    // Run database migration.
    $this->stepDatabaseMigration();

    // Run application deployment.
    $this->stepApplicationDeployment();

    // Run health checks.
    $this->stepHealthChecks();

    static::log('All deployment steps completed successfully');
    static::logStepFinish('Main deployment process completed');
  }

  /**
   * Database migration step.
   */
  private function stepDatabaseMigration(): void {
    static::logStepStart('Preparing database migration');
    static::log('Connecting to production database');
    static::logNote('Using read-only backup connection');

    static::logSubstep('Backing up current database');
    static::log('Creating backup: prod_backup_2025_01_15.sql');
    sleep(1); // 1 second delay.

    static::logSubstep('Running migration scripts');
    static::logNote('Applying schema changes from v2.1 to v2.2');

    // Create a temporary migration log file to demonstrate logFile.
    $temp_file = tempnam(sys_get_temp_dir(), 'migration_log');
    file_put_contents($temp_file, "Migration Log\n=============\n\n" .
      "2025-01-15 10:30:01 - Starting migration\n" .
      "2025-01-15 10:30:15 - Table users: Added column 'last_login'\n" .
      "2025-01-15 10:30:32 - Table orders: Modified index on 'created_at'\n" .
      "2025-01-15 10:30:45 - Migration completed successfully\n");

    static::logFile($temp_file, 'Database migration log');
    unlink($temp_file); // Clean up.

    sleep(2); // 2 second delay.
    static::log('Database schema updated successfully');
    static::logStepFinish('Database migration completed');
  }

  /**
   * Application deployment step.
   */
  private function stepApplicationDeployment(): void {
    static::logStepStart('Deploying application to production');
    static::logSection('APPLICATION SERVER', 'Preparing production deployment', TRUE);
    static::logNote('Deploying to production server cluster');
    static::log('Uploading application files to web servers');
    sleep(1); // 1 second delay.

    // Run asset compilation as a nested step.
    $this->stepAssetCompilation();

    static::log('Restarting application services');
    static::logNote('All services restarted successfully');
    static::logStepFinish('Application deployment finished');
  }

  /**
   * Asset compilation step (deeply nested).
   */
  private function stepAssetCompilation(): void {
    static::logStepStart('Compiling and optimizing assets');
    static::log('Initializing build environment');

    static::logSubstep('Compiling CSS files');
    static::logNote('Processing SCSS with node-sass compiler');
    static::log('Generated: dist/css/main.min.css (compressed, 45KB)');
    sleep(2); // 2 second delay.

    static::logSubstep('Minifying JavaScript');
    static::logNote('Using terser for JS optimization');

    // Create a temporary build log file.
    $build_file = tempnam(sys_get_temp_dir(), 'build_log');
    file_put_contents($build_file, "Asset Build Report\n==================\n\n" .
      "CSS Files Processed:\n" .
      "  - styles/main.scss → dist/css/main.min.css (45KB)\n" .
      "  - styles/components.scss → dist/css/components.min.css (23KB)\n\n" .
      "JavaScript Files Processed:\n" .
      "  - src/app.js → dist/js/app.min.js (128KB)\n" .
      "  - src/utils.js → dist/js/utils.min.js (34KB)\n\n" .
      "Total savings: 67% reduction in file size\n");

    static::logFile($build_file, 'Asset compilation report');
    unlink($build_file); // Clean up.

    sleep(1); // 1 second delay.
    static::log('Asset optimization completed successfully');
    static::logStepFinish('Asset compilation completed');
  }

  /**
   * Health checks step.
   */
  private function stepHealthChecks(): void {
    static::logStepStart('Running system health checks');
    static::logSection('POST-DEPLOYMENT VERIFICATION', 'Validating system functionality');

    static::logSubstep('Testing database connection');
    static::log('Connecting to production database cluster');
    static::logNote('Connection successful - latency: 2ms');
    sleep(1); // 1 second delay.

    static::logSubstep('Verifying API endpoints');
    static::log('Testing critical API endpoints:');
    static::logNote('  • GET /api/health → 200 OK');
    static::logNote('  • GET /api/users → 200 OK');
    static::logNote('  • POST /api/auth → 200 OK');

    // Create a health check results file.
    $health_file = tempnam(sys_get_temp_dir(), 'health_check');
    file_put_contents($health_file, "System Health Check Results\n" .
      "===========================\n\n" .
      "Database Status: ✓ HEALTHY\n" .
      "  - Connection: OK (2ms latency)\n" .
      "  - Active connections: 23/100\n" .
      "  - Query performance: Normal\n\n" .
      "API Status: ✓ HEALTHY\n" .
      "  - /api/health: 200 OK (15ms)\n" .
      "  - /api/users: 200 OK (32ms)\n" .
      "  - /api/auth: 200 OK (28ms)\n\n" .
      "Memory Usage: 67% (Normal)\n" .
      "CPU Usage: 23% (Normal)\n" .
      "Disk Usage: 45% (Normal)\n\n" .
      "Overall Status: ✓ ALL SYSTEMS OPERATIONAL\n");

    static::logFile($health_file, 'System health check results');
    unlink($health_file); // Clean up.

    sleep(2); // 2 second delay.
    static::log('All health checks completed successfully');
    static::logStepFinish('Health checks passed');
  }

}
