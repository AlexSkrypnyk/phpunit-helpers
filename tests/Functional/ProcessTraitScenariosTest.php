<?php

declare(strict_types=1);

namespace AlexSkrypnyk\PhpunitHelpers\Tests\Functional;

use AlexSkrypnyk\PhpunitHelpers\Traits\ProcessTrait;
use AlexSkrypnyk\PhpunitHelpers\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;

/**
 * Standalone test scenarios for ProcessTrait functionality.
 *
 * This test can be run manually to observe streaming behavior and assertion
 * suffix output.
 * Run with: ./vendor/bin/phpunit --group=manual
 */
#[Group('manual')]
#[CoversNothing]
class ProcessTraitScenariosTest extends UnitTestCase {

  use ProcessTrait;

  protected function setUp(): void {
    parent::setUp();
    $this->processStreamOutput = static::isDebug();
  }

  protected function tearDown(): void {
    parent::tearDown();
    $this->processTearDown();
  }

  // Test 1: assertProcessSuccessful() with successful process - should PASS
  public function testSuccessfulProcessWithSuccessAssertion(): void {
    $this->processRun('sh', ['-c', 'echo "Success stdout"; echo "Success stderr" >&2; exit 0']);
    $this->assertProcessSuccessful();
  }

  // Test 2: assertProcessSuccessful() with failed process - should FAIL
  public function testFailedProcessWithSuccessAssertion(): void {
    $this->processRun('sh', ['-c', 'echo "Failed stdout"; echo "Failed stderr" >&2; exit 1']);
    $this->assertProcessSuccessful();
  }

  // Test 3: assertProcessFailed() with failed process - should PASS
  public function testFailedProcessWithFailAssertion(): void {
    $this->processRun('sh', ['-c', 'echo "Failed stdout"; echo "Failed stderr" >&2; exit 1']);
    $this->assertProcessFailed();
  }

  // Test 4: assertProcessFailed() with successful process - should FAIL
  public function testSuccessfulProcessWithFailAssertion(): void {
    $this->processRun('sh', ['-c', 'echo "Success stdout"; echo "Success stderr" >&2; exit 0']);
    $this->assertProcessFailed();
  }

}
