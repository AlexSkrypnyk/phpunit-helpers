<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Tests\Functional;

use AlexSkrypnyk\PhpunitHelpers\Traits\LoggerTrait;
use AlexSkrypnyk\PhpunitHelpers\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;

/**
 * Functional tests for LoggerTrait that output to real STDERR.
 */
#[CoversTrait(LoggerTrait::class)]
final class LoggerTraitFunctionalTest extends UnitTestCase {

  use LoggerTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    self::logSetOutputStream(NULL);
    self::logSetVerbose(TRUE);

    $reflection_class = new \ReflectionClass(self::class);
    $steps_property = $reflection_class->getProperty('logSteps');
    $steps_property->setValue(NULL, []);

    $stack_property = $reflection_class->getProperty('logStepStack');
    $stack_property->setValue(NULL, []);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    self::logSetOutputStream(NULL);
    parent::tearDown();
  }

  /**
   * Functional test: Demonstrate basic logging to STDERR.
   *
   * This test outputs to real STDERR to show what the logging looks like.
   */
  #[DoesNotPerformAssertions]
  public function testFunctionalBasicLogging(): void {
    self::log('This is a basic log message');
    self::logSection('TEST SECTION', 'This is a test section with content');
  }

  /**
   * Functional test: Demonstrate step workflow to STDERR.
   *
   * This test shows a complete step workflow with timing.
   */
  #[DoesNotPerformAssertions]
  public function testFunctionalStepWorkflow(): void {
    self::logStepStart('Processing data');
    self::logSubstep('Loading configuration');
    self::logNote('Using default settings');
    self::logSubstep('Validating input');
    // Delay long enough to show elapsed time.
    usleep(500000);
    self::logStepFinish('Data processing complete');

    self::logStepStart('Generating output');
    self::logNote('Creating report format');
    usleep(200000);
    self::logStepFinish('Output generated successfully');

    self::logStepSummary('WORKFLOW SUMMARY');
  }

  /**
   * Functional test: Demonstrate section formatting variations.
   *
   * This test shows different section formatting options.
   */
  #[DoesNotPerformAssertions]
  public function testFunctionalSectionFormatting(): void {
    self::logSection('STANDARD SECTION', 'This is a standard section with single border');
    self::logSection('DOUBLE BORDER SECTION', 'This section uses double border characters', TRUE);
    self::logSection('WIDE SECTION', 'This section has a wider minimum width', FALSE, 90);
    self::logSection('MULTI-LINE', "This section contains\nmultiple lines of content\nto demonstrate wrapping");
  }

  /**
   * Functional test: Demonstrate file logging to STDERR.
   *
   * This test shows file content logging.
   */
  #[DoesNotPerformAssertions]
  public function testFunctionalFileLogging(): void {
    $temp_file = tempnam(sys_get_temp_dir(), 'logger_functional_test');
    file_put_contents($temp_file, "Sample file content\nLine 2\nLine 3\n");

    self::logFile($temp_file, 'Test configuration file');

    unlink($temp_file);
  }

  /**
   * Functional test: Demonstrate hierarchical step logging to STDERR.
   *
   * This test shows nested step workflows with hierarchy visualization.
   */
  #[DoesNotPerformAssertions]
  public function testFunctionalHierarchicalSteps(): void {
    $this->stepDeploymentProcess();

    self::logStepSummary('DEPLOYMENT SUMMARY');
  }

  /**
   * Main deployment process step.
   */
  protected function stepDeploymentProcess(): void {
    self::logStepStart('Starting main deployment workflow');
    self::log('Initializing deployment environment');
    self::logSection('DEPLOYMENT CONFIGURATION', 'Production environment settings loaded');

    $this->stepDatabaseMigration();

    $this->stepApplicationDeployment();

    $this->stepHealthChecks();

    self::log('All deployment steps completed successfully');
    self::logStepFinish('Main deployment process completed');
  }

  /**
   * Database migration step.
   */
  protected function stepDatabaseMigration(): void {
    self::logStepStart('Preparing database migration');
    self::log('Connecting to production database');
    self::logNote('Using read-only backup connection');

    self::logSubstep('Backing up current database');
    self::log('Creating backup: prod_backup_2025_01_15.sql');
    sleep(1);

    self::logSubstep('Running migration scripts');
    self::logNote('Applying schema changes from v2.1 to v2.2');

    $temp_file = tempnam(sys_get_temp_dir(), 'migration_log');
    file_put_contents($temp_file, "Migration Log\n=============\n\n" .
      "2025-01-15 10:30:01 - Starting migration\n" .
      "2025-01-15 10:30:15 - Table users: Added column 'last_login'\n" .
      "2025-01-15 10:30:32 - Table orders: Modified index on 'created_at'\n" .
      "2025-01-15 10:30:45 - Migration completed successfully\n");

    self::logFile($temp_file, 'Database migration log');
    unlink($temp_file);

    sleep(2);
    self::log('Database schema updated successfully');
    self::logStepFinish('Database migration completed');
  }

  /**
   * Application deployment step.
   */
  protected function stepApplicationDeployment(): void {
    self::logStepStart('Deploying application to production');
    self::logSection('APPLICATION SERVER', 'Preparing production deployment', TRUE);
    self::logNote('Deploying to production server cluster');
    self::log('Uploading application files to web servers');
    sleep(1);

    $this->stepAssetCompilation();

    self::log('Restarting application services');
    self::logNote('All services restarted successfully');
    self::logStepFinish('Application deployment finished');
  }

  /**
   * Asset compilation step (deeply nested).
   */
  protected function stepAssetCompilation(): void {
    self::logStepStart('Compiling and optimizing assets');
    self::log('Initializing build environment');

    self::logSubstep('Compiling CSS files');
    self::logNote('Processing SCSS with node-sass compiler');
    self::log('Generated: dist/css/main.min.css (compressed, 45KB)');
    sleep(2);

    self::logSubstep('Minifying JavaScript');
    self::logNote('Using terser for JS optimization');

    $build_file = tempnam(sys_get_temp_dir(), 'build_log');
    file_put_contents($build_file, "Asset Build Report\n==================\n\n" .
      "CSS Files Processed:\n" .
      "  - styles/main.scss → dist/css/main.min.css (45KB)\n" .
      "  - styles/components.scss → dist/css/components.min.css (23KB)\n\n" .
      "JavaScript Files Processed:\n" .
      "  - src/app.js → dist/js/app.min.js (128KB)\n" .
      "  - src/utils.js → dist/js/utils.min.js (34KB)\n\n" .
      "Total savings: 67% reduction in file size\n");

    self::logFile($build_file, 'Asset compilation report');
    unlink($build_file);

    sleep(1);
    self::log('Asset optimization completed successfully');
    self::logStepFinish('Asset compilation completed');
  }

  /**
   * Health checks step.
   */
  protected function stepHealthChecks(): void {
    self::logStepStart('Running system health checks');
    self::logSection('POST-DEPLOYMENT VERIFICATION', 'Validating system functionality');

    self::logSubstep('Testing database connection');
    self::log('Connecting to production database cluster');
    self::logNote('Connection successful - latency: 2ms');
    sleep(1);

    self::logSubstep('Verifying API endpoints');
    self::log('Testing critical API endpoints:');
    self::logNote('  • GET /api/health → 200 OK');
    self::logNote('  • GET /api/users → 200 OK');
    self::logNote('  • POST /api/auth → 200 OK');

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

    self::logFile($health_file, 'System health check results');
    unlink($health_file);

    sleep(2);
    self::log('All health checks completed successfully');
    self::logStepFinish('Health checks passed');
  }

}
